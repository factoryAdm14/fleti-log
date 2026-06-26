#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ADMIN="$ROOT/fleti-admin-new-install-3.2"
CREDS="$ROOT/DEPLOYMENT_CREDENTIALS.local.md"

if [[ ! -f "$CREDS" ]]; then
  echo "Missing $CREDS"
  exit 1
fi

FTP_USER="$(awk '/^### FTP/{f=1} f && /^- User:/{print $3; exit}' "$CREDS")"
FTP_PASS="$(awk '/^### FTP/{f=1} f && /^- Password:/{print $3; exit}' "$CREDS")"
FTP_HOST="$(awk '/^### FTP/{f=1} f && /^- Host:/{print $3; exit}' "$CREDS")"
SSH_PORT="$(awk '/^- Porta:/{print $3; exit}' "$CREDS")"
SSH_USER="$(awk '/^- Usuário:/{print $3; exit}' "$CREDS")"
SSH_HOST="$(awk '/^- Host:/{print $3; exit}' "$CREDS")"
SSH_PASS="$(awk '/^- Senha:/{print $3; exit}' "$CREDS")"
REMOTE_ROOT="/home/${SSH_USER}/domains/fleti.com.br/public_html"

upload() {
  local local_path="$1"
  local remote_path="$2"
  curl -sS --ftp-create-dirs -T "$local_path" \
    -u "${FTP_USER}:${FTP_PASS}" \
    "ftp://${FTP_HOST}/${remote_path}"
  echo "uploaded: ${remote_path}"
}

FILES=(
  "config/app.php"
  "public/assets/admin-module/css/fleti-admin-modern.css"
  "Modules/AdminModule/Resources/views/partials/_footer.blade.php"
  "Modules/AuthManagement/Resources/views/login.blade.php"
  "resources/views/update/update-software.blade.php"
)

for rel in "${FILES[@]}"; do
  upload "$ADMIN/$rel" "$rel"
done

export SSHPASS="$SSH_PASS"
sshpass -e ssh -o StrictHostKeyChecking=no -p "$SSH_PORT" "${SSH_USER}@${SSH_HOST}" bash -s <<EOF
set -e
cd "$REMOTE_ROOT"
if ! grep -q '^SOFTWARE_VERSION=' .env 2>/dev/null; then
  echo 'SOFTWARE_VERSION=3.2' >> .env
  echo 'added SOFTWARE_VERSION to .env'
else
  sed -i 's/^SOFTWARE_VERSION=.*/SOFTWARE_VERSION=3.2/' .env
  echo 'updated SOFTWARE_VERSION in .env'
fi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
mkdir -p Modules/AiModule/Resources/views
php artisan view:cache
echo 'deploy post-steps done'
EOF

echo "Deploy complete."
