#!/usr/bin/env bash
# Поднимает временный NAT Gateway для приватных подсетей timecapsule-dev-vpc,
# чтобы db-server мог скачать обновления. После работы обязательно запустите nat-down.sh.
# Запуск из корня репозитория: ./scripts/nat-up.sh (AWS CloudShell или Git Bash с AWS CLI).
set -euo pipefail

AWS_REGION=eu-central-1
SUBNET_ID=subnet-0595c1c8b4f76ee32       # timecapsule-dev-public-a: NAT Gateway ставится в публичную подсеть
ROUTE_TABLE_ID=rtb-08ea5451d9d24ed7f     # timecapsule-dev-private-rt
NAT_NAME=timecapsule-dev-nat-a
EIP_NAME=timecapsule-dev-nat-a-eip
STATE_FILE="$(dirname "$0")/.nat-state"  # сюда сохраняются ID для nat-down.sh (файл в .gitignore)

export AWS_REGION

if [[ -f "$STATE_FILE" ]]; then
  echo "Файл $STATE_FILE уже есть: NAT Gateway, похоже, уже поднят. Сначала запустите nat-down.sh." >&2
  exit 1
fi

# Обязательные теги по соглашению из ЛР3; Name передаётся аргументом
tags() {
  echo "{Key=Name,Value=$1},{Key=Project,Value=timecapsule},{Key=Environment,Value=dev},{Key=Owner,Value=danielmihai},{Key=Lab,Value=lab3}"
}

echo "==> 1/4 Выделяем Elastic IP"
ALLOCATION_ID=$(aws ec2 allocate-address --domain vpc \
  --tag-specifications "ResourceType=elastic-ip,Tags=[$(tags "$EIP_NAME")]" \
  --query AllocationId --output text)
echo "    $ALLOCATION_ID"

echo "==> 2/4 Создаём NAT Gateway в public-a"
NAT_ID=$(aws ec2 create-nat-gateway \
  --subnet-id "$SUBNET_ID" \
  --allocation-id "$ALLOCATION_ID" \
  --connectivity-type public \
  --tag-specifications "ResourceType=natgateway,Tags=[$(tags "$NAT_NAME")]" \
  --query NatGateway.NatGatewayId --output text)
echo "    $NAT_ID"

# ID сохраняем сразу: если следующий шаг упадёт, nat-down.sh всё равно найдёт, что удалять
printf 'NAT_ID=%s\nALLOCATION_ID=%s\n' "$NAT_ID" "$ALLOCATION_ID" > "$STATE_FILE"

echo "==> 3/4 Ждём состояния Available (обычно 1-3 минуты)"
aws ec2 wait nat-gateway-available --nat-gateway-ids "$NAT_ID"

echo "==> 4/4 Добавляем маршрут 0.0.0.0/0 -> $NAT_ID в private-rt"
if ! aws ec2 create-route --route-table-id "$ROUTE_TABLE_ID" \
      --destination-cidr-block 0.0.0.0/0 --nat-gateway-id "$NAT_ID" >/dev/null 2>&1; then
  # маршрут остался от прошлого шлюза (Blackhole): заменяем его
  aws ec2 replace-route --route-table-id "$ROUTE_TABLE_ID" \
    --destination-cidr-block 0.0.0.0/0 --nat-gateway-id "$NAT_ID"
fi

PUBLIC_IP=$(aws ec2 describe-addresses --allocation-ids "$ALLOCATION_ID" \
  --query 'Addresses[0].PublicIp' --output text)
echo "Готово: $NAT_NAME ($NAT_ID), внешний адрес $PUBLIC_IP. ID сохранены в $STATE_FILE."
echo "NAT Gateway оплачивается почасово: после установки пакетов запустите ./scripts/nat-down.sh"
