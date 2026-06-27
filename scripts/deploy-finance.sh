#!/usr/bin/env bash
# Deploy do módulo FinanceManagement (Fleti admin/API).
# Uso local (no servidor ou dev):
#   ./scripts/deploy-finance.sh
# Uso remoto (SSH na produção Hostinger):
#   ./scripts/deploy-finance.sh --remote
#
# Opções:
#   --remote          Executa via SSH em fleti.com.br (requer DEPLOYMENT_CREDENTIALS.local.md)
#   --maintenance     Ativa modo manutenção durante migrate/seed
#   --skip-seed       Não roda o seeder do módulo
#   --skip-tests      Não roda phpunit --testsuite Finance
#   --skip-composer   Não roda composer install
#   --dry-run         Apenas exibe os comandos

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ADMIN="$ROOT/fleti-admin-new-install-3.2"
CREDS="$ROOT/DEPLOYMENT_CREDENTIALS.local.md"
SEEDER='Modules\\FinanceManagement\\Database\\Seeders\\FinanceManagementDatabaseSeeder'

REMOTE=false
MAINTENANCE=false
SKIP_SEED=false
SKIP_TESTS=false
SKIP_COMPOSER=false
DRY_RUN=false

for arg in "$@"; do
  case "$arg" in
    --remote) REMOTE=true ;;
    --maintenance) MAINTENANCE=true ;;
    --skip-seed) SKIP_SEED=true ;;
    --skip-tests) SKIP_TESTS=true ;;
    --skip-composer) SKIP_COMPOSER=true ;;
    --dry-run) DRY_RUN=true ;;
    -h|--help)
      sed -n '2,12p' "$0" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *)
      echo "Opção desconhecida: $arg (use --help)"
      exit 1
      ;;
  esac
done

run_cmd() {
  if [[ "$DRY_RUN" == true ]]; then
    echo "[dry-run] $*"
  else
    echo ">> $*"
    eval "$@"
  fi
}

build_deploy_script() {
  cat <<'DEPLOY_EOF'
set -euo pipefail

ADMIN_DIR="${ADMIN_DIR:?}"
MAINTENANCE="${MAINTENANCE:-false}"
SKIP_SEED="${SKIP_SEED:-false}"
SKIP_TESTS="${SKIP_TESTS:-false}"
SKIP_COMPOSER="${SKIP_COMPOSER:-false}"
SEEDER="${SEEDER:?}"

cd "$ADMIN_DIR"

if [[ "$MAINTENANCE" == true ]]; then
  php artisan down --retry=60 2>/dev/null || true
fi

if [[ "$SKIP_COMPOSER" != true ]]; then
  composer install --no-dev --optimize-autoloader --no-interaction
  composer dump-autoload -o --no-interaction
fi

php artisan migrate --force

if [[ "$SKIP_SEED" != true ]]; then
  php artisan db:seed --class="$SEEDER" --force
fi

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
mkdir -p Modules/FinanceManagement/Resources/views
mkdir -p Modules/AiModule/Resources/views
php artisan view:cache
php artisan queue:restart 2>/dev/null || true

if [[ "$SKIP_TESTS" != true ]] && [[ -x ./vendor/bin/phpunit ]]; then
  ./vendor/bin/phpunit --testsuite Finance
fi

if [[ "$MAINTENANCE" == true ]]; then
  php artisan up
fi

echo ""
echo "=== Deploy financeiro concluído ==="
php artisan migrate:status | grep -i finance || true
echo ""
echo "Próximos passos:"
echo "  1. Admin → Financeiro → Configurações"
echo "  2. Configurar webhooks PIX"
echo "  3. Rodar smoke test: docs/finance_smoke_test_checklist.md"
DEPLOY_EOF
}

if [[ "$REMOTE" == true ]]; then
  PYTHON_DEPLOY="$ROOT/scripts/deploy_finance_remote.py"
  if [[ -f "$PYTHON_DEPLOY" ]]; then
    PY_ARGS=(python3 "$PYTHON_DEPLOY")
    [[ "$DRY_RUN" == true ]] && PY_ARGS+=(--dry-run)
    [[ "$SKIP_SEED" == true ]] && PY_ARGS+=(--skip-seed)
    [[ "$SKIP_COMPOSER" == true ]] && PY_ARGS+=(--skip-composer)
    [[ "$MAINTENANCE" != true ]] && PY_ARGS+=(--no-maintenance)
    "${PY_ARGS[@]}"
    exit $?
  fi

  if [[ ! -f "$CREDS" ]]; then
    echo "Arquivo de credenciais não encontrado: $CREDS"
    exit 1
  fi

  SSH_PORT="$(awk '/^- Porta:/{print $3; exit}' "$CREDS")"
  SSH_USER="$(awk '/^- Usuário:/{print $3; exit}' "$CREDS")"
  SSH_HOST="$(awk '/^- Host:/{print $3; exit}' "$CREDS")"
  SSH_PASS="$(awk '/^- Senha:/{print $3; exit}' "$CREDS")"
  REMOTE_ROOT="/home/${SSH_USER}/domains/fleti.com.br/public_html"

  if [[ "$DRY_RUN" == true ]]; then
    echo "[dry-run] SSH ${SSH_USER}@${SSH_HOST}:${SSH_PORT} → ${REMOTE_ROOT}"
    ADMIN_DIR="$REMOTE_ROOT" MAINTENANCE="$MAINTENANCE" SKIP_SEED="$SKIP_SEED" \
      SKIP_TESTS="$SKIP_TESTS" SKIP_COMPOSER="$SKIP_COMPOSER" SEEDER="$SEEDER" \
      build_deploy_script
    exit 0
  fi

  export SSHPASS="$SSH_PASS"
  ADMIN_DIR="$REMOTE_ROOT" MAINTENANCE="$MAINTENANCE" SKIP_SEED="$SKIP_SEED" \
    SKIP_TESTS="$SKIP_TESTS" SKIP_COMPOSER="$SKIP_COMPOSER" SEEDER="$SEEDER" \
    build_deploy_script | sshpass -e ssh -o StrictHostKeyChecking=no -p "$SSH_PORT" \
    "${SSH_USER}@${SSH_HOST}" bash -s

  echo "Deploy remoto concluído."
  exit 0
fi

if [[ ! -d "$ADMIN" ]]; then
  echo "Diretório admin não encontrado: $ADMIN"
  exit 1
fi

if [[ "$DRY_RUN" == true ]]; then
  ADMIN_DIR="$ADMIN" MAINTENANCE="$MAINTENANCE" SKIP_SEED="$SKIP_SEED" \
    SKIP_TESTS="$SKIP_TESTS" SKIP_COMPOSER="$SKIP_COMPOSER" SEEDER="$SEEDER" \
    build_deploy_script
  exit 0
fi

export ADMIN_DIR="$ADMIN"
export MAINTENANCE SKIP_SEED SKIP_TESTS SKIP_COMPOSER SEEDER
build_deploy_script | bash -s

echo "Deploy local concluído."
