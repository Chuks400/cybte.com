#!/bin/bash
# Deploy script for payment system updates

# Server details
SERVER="root@178.104.139.94"
REMOTE_PATH="/var/www/cybte.com"

# Deploy Payment files
echo "Deploying Alipay.php..."
scp src/Payment/Alipay.php $SERVER:$REMOTE_PATH/src/Payment/

echo "Deploying WeChatPay.php..."
scp src/Payment/WeChatPay.php $SERVER:$REMOTE_PATH/src/Payment/

echo "Deploying PaymentFactory.php..."
scp src/Payment/PaymentFactory.php $SERVER:$REMOTE_PATH/src/Payment/

echo "Deploying PaymentInterface.php..."
scp src/Payment/PaymentInterface.php $SERVER:$REMOTE_PATH/src/Payment/

# Deploy API endpoints
echo "Deploying create.php..."
scp public/api/payment/create.php $SERVER:$REMOTE_PATH/public/api/payment/

echo "Deploying status.php..."
scp public/api/payment/status.php $SERVER:$REMOTE_PATH/public/api/payment/

echo "Deploying retry.php..."
scp public/api/payment/retry.php $SERVER:$REMOTE_PATH/public/api/payment/

echo "Deploying refund.php..."
scp public/api/payment/refund.php $SERVER:$REMOTE_PATH/public/api/payment/

echo "Deploying webhook.php..."
scp public/api/payment/webhook.php $SERVER:$REMOTE_PATH/public/api/payment/

echo "Deploying .env..."
scp .env $SERVER:$REMOTE_PATH/

echo "Done!"
