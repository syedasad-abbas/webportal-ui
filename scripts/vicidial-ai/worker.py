#!/usr/bin/env python3
"""Local VICIdial MeetMe observer. Python standard library + MariaDB CLI only."""
import argparse
import configparser
import fcntl
import ipaddress
import json
import os
from pathlib import Path
import re
import socket
import subprocess
import time
import urllib.request
import uuid


class AMI:
    def __init__(self, cfg):
        self.sock = socket.create_connection(('127.0.0.1', int(cfg.get('ami_port', '5038'))), 4)
        self.stream = self.sock.makefile('rb')
        self.stream.readline()  # greeting
        try:
            self.action('Login', Username=cfg['ami_user'], Secret=cfg['ami_secret'], Events='off')
        except Exception:
            self.close()
            raise

    def close(self):
        self.stream.close()
        self.sock.close()

    def packet(self):
        packet = {}
        for _ in range(100):
            line = self.stream.readline(4096)
            if not line:
                raise RuntimeError('AMI disconnected')
            if line == b'\r\n':
                return packet
            key, sep, value = line.decode('utf-8', 'replace').strip().partition(': ')
            if sep:
                packet[key] = value
        raise RuntimeError('AMI packet too large')

    def action(self, name, complete=None, **fields):
        action_id = uuid.uuid4().hex
        data = {'Action': name, 'ActionID': action_id, **fields}
        if any('\r' in str(v) or '\n' in str(v) for v in data.values()):
            raise ValueError('Invalid AMI field')
        self.sock.sendall(('\r\n'.join(f'{k}: {v}' for k, v in data.items()) + '\r\n\r\n').encode())
        events = []
        deadline = time.monotonic() + 8
        while time.monotonic() < deadline:
            packet = self.packet()
            if packet.get('ActionID') != action_id:
                continue
            if packet.get('Response') == 'Error':
                if name == 'MeetmeList' and packet.get('Message') == 'No active conferences.':
                    return []
                # Don't log raw AMI responses: they can contain SIP capabilities.
                raise RuntimeError(f'AMI {name} rejected')
            if not complete and packet.get('Response') == 'Success':
                return packet
            if packet.get('Event') == complete:
                return events
            if packet.get('Event'):
                events.append(packet)
        raise RuntimeError(f'AMI {name} timed out')


def read_calls(cfg):
    # Agent INCALL alone is not enough: verify an inbound auto-call with the
    # same customer identity and then its actual MeetMe membership below.
    server = str(ipaddress.IPv4Address(cfg['server_ip']))
    query = f"""
      SELECT DISTINCT l.user, l.conf_exten, a.uniqueid, a.channel
      FROM vicidial_live_agents l JOIN vicidial_auto_calls a
        ON a.lead_id=l.lead_id AND a.server_ip=l.server_ip
        AND ((a.uniqueid<>'' AND a.uniqueid=l.uniqueid)
          OR (a.callerid<>'' AND a.callerid=l.callerid))
      WHERE l.server_ip='{server}' AND l.status='INCALL'
        AND a.call_type='IN' AND a.uniqueid<>'' AND a.channel<>'';
    """
    result = subprocess.run(['mariadb', '--defaults-file=' + cfg['db_defaults_file'],
                             '--batch', '--raw', '--skip-column-names', 'asterisk'],
                            input=query, text=True, capture_output=True, timeout=5)
    if result.returncode:
        raise RuntimeError('MariaDB snapshot failed; check worker DB credentials')
    rows = []
    for line in result.stdout.splitlines():
        parts = line.split('\t')
        if len(parts) != 4:
            raise RuntimeError('Unexpected snapshot format')
        agent, conference, call_id, channel = parts
        if not (re.fullmatch(r'[a-zA-Z0-9_-]{1,20}', agent)
                and re.fullmatch(r'\d{1,20}', conference)
                and re.fullmatch(r'[a-zA-Z0-9_.-]{1,80}', call_id)):
            continue
        rows.append({'agent': agent, 'conference': conference, 'callId': call_id, 'channel': channel})
    return rows


def verified_calls(rows, members):
    membership = {(m.get('Conference'), m.get('Channel')) for m in members if m.get('Event') == 'MeetmeList'}
    verified = [r for r in rows if (r['conference'], r['channel']) in membership]
    # Ambiguous room assignments fail closed.
    return [{k: r[k] for k in ('agent', 'conference', 'callId')} for r in verified
            if sum(x['conference'] == r['conference'] for x in verified) == 1]


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        raise RuntimeError('Backend redirects are not allowed')


def sync(cfg, calls):
    request = urllib.request.Request(cfg['backend_url'].rstrip('/') + '/vicidial-ai/sync',
                                    data=json.dumps({'calls': calls}).encode(),
                                    headers={'Content-Type': 'application/json',
                                             'X-Vicidial-AI-Token': cfg['sync_token']})
    # No ambient proxy or redirect may receive the sync secret.
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect())
    with opener.open(request, timeout=5) as response:
        payload = json.loads(response.read(131072))
    if payload.get('ok') is not True:
        raise RuntimeError('Backend rejected snapshot')
    return payload['joins']


def call_key(call):
    return ':'.join(call[k] for k in ('agent', 'conference', 'callId'))


def persist(path, attempts):
    temporary = path.with_suffix('.tmp')
    with temporary.open('w') as stream:
        json.dump(attempts, stream)
        stream.flush()
        os.fsync(stream.fileno())
    temporary.replace(path)


def run(cfg, once=False):
    state = Path(cfg.get('state_file', '/var/lib/vicidial-ai/attempts.json'))
    state.parent.mkdir(parents=True, exist_ok=True)
    with state.with_suffix('.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)  # one observer per VM
        return run_locked(cfg, state, once)


def run_locked(cfg, state, once):
    attempts = json.loads(state.read_text()) if state.exists() else {}
    peer = cfg.get('sip_peer', 'webportal-ai')
    if not re.fullmatch(r'[a-zA-Z0-9_-]+', peer) or len(cfg['sync_token']) < 32:
        raise ValueError('Invalid SIP peer or sync token (minimum 32 characters)')
    while True:
        ami = None
        try:
            rows = read_calls(cfg)
            ami = AMI(cfg)
            calls = verified_calls(rows, ami.action('MeetmeList', complete='MeetmeListComplete'))
            joins = sync(cfg, calls)
            current = {call_key(c) for c in calls}
            attempts = {k: t for k, t in attempts.items() if k in current or time.time() - t < 86400}
            for join in joins:
                key = call_key(join)
                if key not in current or key in attempts or not re.fullmatch(r'[a-f0-9]{64}', join['token']):
                    continue
                # Recheck immediately before joining, including actual customer
                # membership. The backend lease also revokes late answers.
                fresh = verified_calls(read_calls(cfg), ami.action('MeetmeList', complete='MeetmeListComplete'))
                if key not in {call_key(c) for c in fresh}:
                    continue
                attempts[key] = time.time()
                persist(state, attempts)  # at-most-once even on restart/timeout
                ami.action('Originate', Channel=f"SIP/{peer}/vici-ai-{join['token']}",
                           Application='MeetMe', Data=join['conference'] + ',q',
                           CallerID='AI Assistant <9900>', Timeout='10000', Async='true')
                print(f"AI leg requested for agent {join['agent']}, conference {join['conference']}", flush=True)
            persist(state, attempts)
            if once:
                print(f'Worker check passed; {len(calls)} verified inbound customer call(s).', flush=True)
                return
        except Exception as exc:
            # No heartbeat on failure: existing AI legs expire safely.
            print(f'Worker unavailable: {type(exc).__name__}: {exc}', flush=True)
            if once:
                raise SystemExit(1)
        finally:
            if ami:
                ami.close()
        time.sleep(2)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--config', default='/etc/vicidial-ai-worker.ini')
    parser.add_argument('--once', action='store_true', help='One poll; may originate AI for eligible live calls')
    args = parser.parse_args()
    cfg = configparser.ConfigParser(interpolation=None)
    if not cfg.read(args.config):
        raise SystemExit('Worker config not found')
    run(cfg['worker'], args.once)
