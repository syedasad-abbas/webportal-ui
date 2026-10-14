import importlib.util
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('worker', Path(__file__).with_name('worker.py'))
worker = importlib.util.module_from_spec(spec)
spec.loader.exec_module(worker)


class WorkerTests(unittest.TestCase):
    def test_requires_actual_customer_membership(self):
        row = dict(agent='6666', conference='8600051', callId='123.4', channel='SIP/customer-123')
        self.assertEqual(worker.verified_calls([row], []), [])
        agent = dict(Event='MeetmeList', Conference='8600051', Channel='SIP/gs102-123')
        self.assertEqual(worker.verified_calls([row], [agent]), [])
        customer = dict(agent, Channel='SIP/customer-123')
        self.assertEqual(worker.verified_calls([row], [agent, customer]),
                         [dict(agent='6666', conference='8600051', callId='123.4')])
        self.assertEqual(worker.verified_calls([row, row], [customer]), [])

    def test_sql_gates_inbound_and_incall(self):
        class Result:
            returncode = 0
            stdout = '6666\t8600051\t123.4\tSIP/customer-123\n'
        with patch.object(worker.subprocess, 'run', return_value=Result()) as run:
            rows = worker.read_calls(dict(server_ip='172.16.253.142', db_defaults_file='/test'))
            sql = run.call_args.kwargs['input']
            self.assertIn("l.status='INCALL'", sql)
            self.assertIn("a.call_type='IN'", sql)
            self.assertEqual(rows[0]['channel'], 'SIP/customer-123')
            self.assertEqual(run.call_args.kwargs['timeout'], 5)

    def test_restart_does_not_originate_again(self):
        calls = [dict(agent='6666', conference='8600051', callId='123.4')]
        class FakeAMI:
            origins = 0
            def __init__(self, cfg): pass
            def close(self): pass
            def action(self, action, **kwargs):
                if action == 'Originate':
                    self.__class__.origins += 1
                    raise RuntimeError('ambiguous network timeout')
                return []
        with tempfile.TemporaryDirectory() as directory:
            cfg = dict(state_file=directory + '/state.json', sync_token='s' * 32)
            with patch.object(worker, 'AMI', FakeAMI), patch.object(worker, 'read_calls', return_value=[]), \
                 patch.object(worker, 'verified_calls', return_value=calls), \
                 patch.object(worker, 'sync', return_value=[dict(calls[0], token='a' * 64)]):
                with self.assertRaises(SystemExit):
                    worker.run(cfg, once=True)
                worker.run(cfg, once=True)
                self.assertEqual(FakeAMI.origins, 1)


if __name__ == '__main__':
    unittest.main()
