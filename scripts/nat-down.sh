#!/usr/bin/env bash
# Убирает временный NAT Gateway, поднятый scripts/nat-up.sh:
# удаляет маршрут, сам шлюз и освобождает его Elastic IP.
# Запуск из корня репозитория: ./scripts/nat-down.sh (AWS CloudShell или Git Bash с AWS CLI).
set -euo pipefail

AWS_REGION=eu-central-1
ROUTE_TABLE_ID=rtb-08ea5451d9d24ed7f     # timecapsule-dev-private-rt
STATE_FILE="$(dirname "$0")/.nat-state"  # создан nat-up.sh

export AWS_REGION

if [[ ! -f "$STATE_FILE" ]]; then
  echo "Нет файла $STATE_FILE: удалять нечего (или шлюз создавался не через nat-up.sh)." >&2
  exit 1
fi
# shellcheck source=/dev/null
source "$STATE_FILE"
: "${NAT_ID:?в $STATE_FILE нет NAT_ID}" "${ALLOCATION_ID:?в $STATE_FILE нет ALLOCATION_ID}"

echo "==> 1/4 Удаляем маршрут 0.0.0.0/0 из private-rt"
if aws ec2 delete-route --route-table-id "$ROUTE_TABLE_ID" --destination-cidr-block 0.0.0.0/0 2>/dev/null; then
  echo "    маршрут удалён"
else
  echo "    маршрута уже нет"
fi

echo "==> 2/4 Удаляем NAT Gateway $NAT_ID"
aws ec2 delete-nat-gateway --nat-gateway-id "$NAT_ID" >/dev/null

echo "==> 3/4 Ждём состояния Deleted (обычно около минуты)"
aws ec2 wait nat-gateway-deleted --nat-gateway-ids "$NAT_ID"

echo "==> 4/4 Освобождаем Elastic IP $ALLOCATION_ID"
# Шлюз отпускает адрес не сразу после Deleted, поэтому несколько попыток с паузой
for attempt in 1 2 3 4 5 6; do
  if aws ec2 release-address --allocation-id "$ALLOCATION_ID" 2>/dev/null; then
    echo "    адрес освобождён (попытка $attempt)"
    rm -f "$STATE_FILE"
    echo "Готово: NAT Gateway удалён, Elastic IP освобождён."
    exit 0
  fi
  echo "    адрес ещё привязан к шлюзу, ждём 20 с (попытка $attempt из 6)"
  sleep 20
done

echo "Не удалось освободить $ALLOCATION_ID. Проверьте Elastic IPs в консоли и освободите адрес вручную." >&2
exit 1
