set -e
UUID=$(cat /proc/sys/kernel/random/uuid)
SECRET=$(head -c 48 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 48)
VZ=$(command -v vzdump || echo /usr/bin/vzdump)
QM=$(command -v qm || echo /usr/sbin/qm)
QMR=$(command -v qmrestore || echo /usr/sbin/qmrestore)
mkdir -p /etc/anchor
cat > /etc/anchor/anchor.toml <<TOML
mode = "agent"
listen_addr = "127.0.0.1:2115"
installation_id = "$UUID"
secret = "$SECRET"
panel_url = "https://convoy.ddev.site"
public_url = "https://us-southwest-2.banded-trench.ts.net:8443"

[agent]
qm_path = "$QM"
qemu_config_dir = "/etc/pve/qemu-server"
qemu_run_dir = "/var/run/qemu-server"
qmrestore_path = "$QMR"
pve_dir = "/etc/pve"
template_temp_dir = "/var/lib/vz/dump/anchor-tmp"
templates_enabled = true
template_concurrency = 2
vzdump_path = "$VZ"
exports_enabled = true
export_dir = "/var/lib/vz/dump/anchor-exports"
export_ttl_secs = 86400
TOML
chmod 600 /etc/anchor/anchor.toml
anchor validate --config /etc/anchor/anchor.toml >/dev/null 2>&1 && echo "VALIDATE_OK" || { echo "VALIDATE_FAILED"; anchor validate --config /etc/anchor/anchor.toml 2>&1 | sed 's/secret = \\"[^\\"]*\\"/secret = REDACTED/g' | head -3; exit 1; }
systemctl restart anchor
sleep 4
systemctl is-active anchor
ss -lntp 2>/dev/null | grep ':2115' | wc -l
