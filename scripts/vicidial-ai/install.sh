#!/usr/bin/env bash
# Installs the prepared private lab configuration. Does not reload/restart services.
set -euo pipefail
if [[ $EUID -ne 0 ]]; then
  echo 'Run this installer with sudo.' >&2
  exit 1
fi
vici_source_dir=$(cd -- "$(dirname -- "$0")" && pwd)
vici_config_dir=${1:?Usage: sudo bash install.sh /path/to/vicidial-ai-private}
for vici_command in mariadb python3 install getent useradd stat; do
  command -v "$vici_command" >/dev/null || { echo "Missing command: $vici_command" >&2; exit 1; }
done
for vici_file in worker.ini db.cnf manager-webportal-ai.conf sip-webportal-ai.conf database.sql; do
  [[ -f "$vici_config_dir/$vici_file" ]] || { echo "Missing prepared file: $vici_file" >&2; exit 1; }
done
for vici_target in /etc/vicidial-ai-worker.ini /etc/vicidial-ai-db.cnf \
  /etc/asterisk/manager-webportal-ai.conf /etc/asterisk/sip-webportal-ai.conf \
  /etc/asterisk/extensions-webportal-ai.conf /etc/systemd/system/vicidial-ai.service; do
  [[ ! -e "$vici_target" ]] || { echo "Already exists: $vici_target. Review the existing installation first." >&2; exit 1; }
done
for vici_target in /etc/asterisk/manager.conf /etc/asterisk/sip.conf /etc/asterisk/extensions.conf; do
  [[ -f "$vici_target" ]] || { echo "Missing Asterisk configuration: $vici_target" >&2; exit 1; }
done
vici_account_count=$(mariadb --batch --skip-column-names -e "SELECT COUNT(*) FROM mysql.user WHERE User='webportal_ai' AND Host='localhost';")
[[ "$vici_account_count" == 0 ]] || { echo 'Database account webportal_ai already exists; review credentials before installing.' >&2; exit 1; }

# Validate configuration before making changes. Never print credentials.
python3 - "$vici_config_dir" <<'PY'
import configparser
import ipaddress
from pathlib import Path
import sys
root = Path(sys.argv[1])
cfg = configparser.ConfigParser(interpolation=None)
cfg.read(root / 'worker.ini')
c = cfg['worker']
ipaddress.IPv4Address(c['server_ip'])
for key in ('sync_token', 'ami_secret'):
    if len(c[key]) < 32 or 'REPLACE_' in c[key]:
        raise SystemExit('Worker credentials are not prepared.')
db = configparser.ConfigParser(interpolation=None)
db.read(root / 'db.cnf')
if 'REPLACE_' in db['client']['password']:
    raise SystemExit('Database credentials are not prepared.')
PY

if ! getent passwd vicidial-ai >/dev/null; then
  useradd --system --home /var/lib/vicidial-ai --shell /usr/sbin/nologin vicidial-ai
fi
install -d -m 0755 /opt/vicidial-ai
install -d -o vicidial-ai -g vicidial-ai -m 0700 /var/lib/vicidial-ai
install -m 0755 "$vici_source_dir/worker.py" /opt/vicidial-ai/worker.py
install -o root -g vicidial-ai -m 0640 "$vici_config_dir/worker.ini" /etc/vicidial-ai-worker.ini
install -o root -g vicidial-ai -m 0640 "$vici_config_dir/db.cnf" /etc/vicidial-ai-db.cnf
vici_asterisk_group=$(stat -c '%G' /etc/asterisk/sip.conf)
install -o root -g "$vici_asterisk_group" -m 0640 "$vici_config_dir/manager-webportal-ai.conf" /etc/asterisk/manager-webportal-ai.conf
install -o root -g "$vici_asterisk_group" -m 0640 "$vici_config_dir/sip-webportal-ai.conf" /etc/asterisk/sip-webportal-ai.conf
install -o root -g "$vici_asterisk_group" -m 0640 /dev/null /etc/asterisk/extensions-webportal-ai.conf
cat > /etc/asterisk/extensions-webportal-ai.conf <<'EOF'
[webportal-ai-reject]
exten => _.,1,Hangup()
EOF
vici_backup_suffix=$(date +%Y%m%d%H%M%S)
for vici_base in manager sip extensions; do
  cp -p "/etc/asterisk/$vici_base.conf" "/etc/asterisk/$vici_base.conf.before-webportal-ai-$vici_backup_suffix"
  printf '\n#include %s-webportal-ai.conf\n' "$vici_base" >> "/etc/asterisk/$vici_base.conf"
done
mariadb < "$vici_config_dir/database.sql"
install -m 0644 "$vici_source_dir/vicidial-ai.service" /etc/systemd/system/vicidial-ai.service
echo 'Installation complete. No services were started, restarted, or reloaded.'
echo 'Next: deploy the portal app, reload FreeSWITCH XML and Asterisk configuration, then start vicidial-ai as documented.'
