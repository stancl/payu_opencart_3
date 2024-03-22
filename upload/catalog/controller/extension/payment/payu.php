<?php

/*
* ver. 3.2.4
* PayU Payment Modules
*
* @copyright  Copyright 2016 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
*/

class ControllerExtensionPaymentPayU extends Controller
{
    const PAY_BUTTON = 'https://static.payu.com/pl/standard/partners/buttons/payu_account_button_01.png';

    const VERSION = '3.3.2';

    private $ocr = array();
    private $totalWithoutDiscount = 0;

    //loading PayU SDK
    private function loadLibConfig()
    {
        require_once(DIR_SYSTEM . 'library/sdk_v21/openpayu.php');

        OpenPayU_Configuration::setMerchantPosId($this->config->get('payment_payu_merchantposid'));
        OpenPayU_Configuration::setSignatureKey($this->config->get('payment_payu_signaturekey'));
        OpenPayU_Configuration::setOauthClientId($this->config->get('payment_payu_oauth_client_id'));
        OpenPayU_Configuration::setOauthClientSecret($this->config->get('payment_payu_oauth_client_secret'));
        OpenPayU_Configuration::setEnvironment($this->config->get('payment_payu_environment'));
        OpenPayU_Configuration::setSender('OpenCart ver ' . VERSION . ' / Plugin ver ' . self::VERSION);

        if (! isset($this->logger)) {
            $this->logger = new Log('payu.log');
        }
    }

    public function index()
    {
        $data['payu_button'] = self::PAY_BUTTON;
        $data['action'] = $this->url->link('extension/payment/payu/pay','', true);

        return $this->load->view('extension/payment/payu', $data);
    }

    public function retry()
    {
        $this->language->load('extension/payment/payu');

        $this->response->addHeader('Content-Type: text/html');

        $orderId = isset($_GET['orderId']) ? $_GET['orderId'] : null;
        $email = isset($_GET['email']) ? $_GET['email'] : null;
        $total = (float) (isset($_GET['total']) ? $_GET['total'] : 0);

        if ((! $orderId) || (! $email) || (! $total)) {
            $this->response->setOutput($this->language->get('retry_not_found'));

            return;
        }

        $this->load->model('checkout/order');

        $order = $this->model_checkout_order->getOrder($orderId);

        $orderTotal = (float) $order['total'];
        if (($email !== $order['email']) || ($total !== $orderTotal)) {
            $this->response->setOutput($this->language->get('retry_not_found'));

            return;
        }

        if ($order['payment_code'] !== 'payu') {
            $this->response->setOutput($this->language->get('retry_not_found'));

            return;
        }

        $this->load->model('extension/payment/payu');
        $sessions = $this->model_extension_payment_payu->getSessionsForOrder($order['order_id']);

        foreach ($sessions as $session) {
            if ($session['status'] === 'COMPLETED') {
                $this->response->setOutput($this->language->get('retry_already_paid'));

                return;
            }

            if ($session['status'] === 'WAITING_FOR_CONFIRMATION') {
                $this->response->setOutput($this->language->get('retry_already_paid'));

                return;
            }

            // If the status is 'PENDING', that means:
            //    1. The order was paid and we're still awaiting a payment. This would likely only be in a period of a few seconds at the very most.
            //       So it's assumed that it's impossible that the customer would be visiting this page after paying (but before our server is notified).
            //    2. The customer left the payment page. PayU doesn't seem to send a CANCELED notification to our app, so we create a new payment.
        }

        $payuOrder = $this->buildOrder($order['order_id']);

        try {
            $response = OpenPayU_Order::create($payuOrder);
            $status_desc = OpenPayU_Util::statusDesc($response->getStatus());

            if ($response->getStatus() == 'SUCCESS') {
                $this->session->data['payuSessionId'] = $response->getResponse()->orderId;
                $this->model_extension_payment_payu->bindOrderIdAndSessionId(
                    $order['order_id'],
                    $this->session->data['payuSessionId']
                );
                $this->model_checkout_order->addOrderHistory(
                    $order['order_id'],
                    $this->config->get('payment_payu_new_status')
                );

                $paymentUrl = $response->getResponse()->redirectUri . '&lang=' . substr($this->session->data['language'], 0, 2);

                $this->response->addHeader('Location: ' . $paymentUrl);
                $this->response->setOutput("<a href='$paymentUrl'>Zaplatit</a>");
            } else {
                $this->logger->write('OCR: ' . serialize($payuOrder));
                $this->logger->write($response->getError() . ' [request: ' . serialize($response) . ']');
                $this->response->setOutput(
                    'ERROR: ' . $this->language->get('text_error_message') . '(' . $response->getStatus() . ': ' . $status_desc . ')'
                );
            }
        } catch (OpenPayU_Exception $e) {
            $this->logger->write('OCR: ' . serialize($payuOrder));
            $this->logger->write('OCR Exception: ' . $e->getMessage());
            $this->response->setOutput('ERROR: ' . $this->language->get('text_error_message'));
        }
    }

    public function pay()
    {
        if ($this->session->data['payment_method']['code'] == 'payu') {
            $this->language->load('extension/payment/payu');
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/payu');

            //OCR
            $this->loadLibConfig();
            $order = $this->buildOrder();

            try {
                $response = OpenPayU_Order::create($order);
                $status_desc = OpenPayU_Util::statusDesc($response->getStatus());

                if ($response->getStatus() == 'SUCCESS') {
                    $this->session->data['payuSessionId'] = $response->getResponse()->orderId;
                    $this->model_extension_payment_payu->bindOrderIdAndSessionId(
                        $this->session->data['order_id'],
                        $this->session->data['payuSessionId']
                    );
                    $this->model_checkout_order->addOrderHistory(
                        $this->session->data['order_id'],
                        $this->config->get('payment_payu_new_status')
                    );

                    $return['status'] = 'SUCCESS';

                    $return['redirectUri'] = $response->getResponse()->redirectUri . '&lang=' . substr($this->session->data['language'], 0, 2);

                } else {
                    $return['status'] = 'ERROR';

                    $data['text_error'] = $this->language->get('text_error_message');
                    $this->logger->write('OCR: ' . serialize($order));
                    $this->logger->write(
                        $response->getError() . ' [request: ' . serialize($response) . ']'
                    );
                    $return['message'] = $this->language->get('text_error_message') .
                        '(' . $response->getStatus() . ': ' . $status_desc . ')';
                }
            } catch (OpenPayU_Exception $e) {
                $this->logger->write('OCR: ' . serialize($order));
                $this->logger->write('OCR Exception: ' . $e->getMessage());
                $return['status'] = 'ERROR';
                $return['message'] = $this->language->get('text_error_message');
            }
            echo json_encode($return);
            exit();
        }
    }

    //Notification
    public function ordernotify()
    {
        $this->loadLibConfig();
        $this->load->model('extension/payment/payu');
        $this->load->model('checkout/order');

        $body = file_get_contents('php://input');
        $data = trim($body);

        $notification_data = json_decode($data, true);

        $extOrderId = null;
        if (isset($notification_data['extOrderId'])) {
            $extOrderId = $notification_data['extOrderId'];
        } elseif (isset($notification_data['order'])) {
            if (isset($notification_data['order']['extOrderId'])) {
                $extOrderId = $notification_data['order']['extOrderId'];
            }
        }

        if ($extOrderId) {
            // Not sure if this is just the sandbox but the $extOrderId seems to be suffixed with -randomCharacters
            // So when that's the case, we trim the suffix
            if ($pos = strpos($extOrderId, '-')) {
                $extOrderId = substr($extOrderId, 0, $pos);
            }

            $this->setSecondaryKeysIfNeeded(
                $this->model_checkout_order->getOrder($extOrderId)
            );
        }

        try {
            if (!empty($data)) {
                $result = OpenPayU_Order::consumeNotification($data);
            }

            if ($session_id = $result->getResponse()->order->orderId) {
                $orderInfo = $this->model_extension_payment_payu->getOrderInfoBySessionId($session_id);
                $orderRetrive = OpenPayU_Order::retrieve($session_id);

                if ($orderRetrive->getStatus() != 'SUCCESS') {
                    $this->logger->write(
                        $orderRetrive->getError() . ' [response: ' . serialize($orderRetrive->getResponse()) . ']'
                    );
                } else {
                    $payuOrderStatus = $orderRetrive->getResponse()->orders[0]->status;
                    $order = $this->model_checkout_order->getOrder($orderInfo['order_id']);

                    if ($orderInfo['status'] != OpenPayuOrderStatus::STATUS_COMPLETED) {
                        $newstatus = $this->getPaymentStatusId($payuOrderStatus);
                        $comment = $this->getPaymentStatusEmail($payuOrderStatus);

                        $notify = false;

                        if ($comment) {
                            $notify = true;

                            $payuOrderTotal = (string) ((float) $order['total']);
                            $retryUrl = $this->url->link('extension/payment/payu/retry') . "&orderId={$order['order_id']}&email={$order['email']}&total={$payuOrderTotal}";
                            $comment = str_replace('%retry%', $retryUrl, $comment);
                        } else {
                            $comment = 'PayU Notification'; // displayed in the admin panel
                        }

                        if ($newstatus && $newstatus != $order['order_status']) {
                            $this->model_extension_payment_payu->updateSatatus($session_id, $payuOrderStatus);
                            $this->model_checkout_order->addOrderHistory($orderInfo['order_id'], $newstatus, $comment, $notify);
                        }
                    }
                }
            }

        } catch (OpenPayU_Exception $e) {
            $this->logger->write('OCR Notification: ' . $e->getMessage());
        }

    }

    //Getting system status
    private function getPaymentStatusId($paymentStatus)
    {
        $this->load->model('extension/payment/payu');
        if (!empty($paymentStatus)) {

            switch ($paymentStatus) {
                case OpenPayuOrderStatus::STATUS_CANCELED :
                    return $this->config->get('payment_payu_cancelled_status');
                case OpenPayuOrderStatus::STATUS_PENDING :
                    return $this->config->get('payment_payu_pending_status');
                case OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION :
                    return $this->config->get('payment_payu_waiting_for_confirmation_status');
                case OpenPayuOrderStatus::STATUS_COMPLETED :
                    return $this->config->get('payment_payu_complete_status');
                default:
                    return false;
            }
        }

        return false;
    }

    private function getPaymentStatusEmail($paymentStatus)
    {
        $this->load->model('extension/payment/payu');
        if (!empty($paymentStatus)) {

            switch ($paymentStatus) {
                case OpenPayuOrderStatus::STATUS_CANCELED:
                    return $this->config->get('payment_payu_cancelled_status_email');
                case OpenPayuOrderStatus::STATUS_PENDING:
                    return $this->config->get('payment_payu_pending_status_email');
                case OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION:
                    return $this->config->get('payment_payu_waiting_for_confirmation_status_email');
                case OpenPayuOrderStatus::STATUS_COMPLETED:
                    return $this->config->get('payment_payu_complete_status_email');
                default:
                    return '';
            }
        }

        return '';
    }

    private function buildOrder($orderId = null)
    {
        $this->language->load('extension/payment/payu');
        $this->load->model('extension/payment/payu');
        $this->loadLibConfig();

        //get order info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($orderId ?: $this->session->data['order_id']);

        $this->setSecondaryKeysIfNeeded($order_info);

        //OCR basic data
        $this->ocr['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $this->ocr['description'] = $this->language->get('text_payu_order') . ' #' . $order_info['order_id'];
        $this->ocr['customerIp'] = $this->getIP($order_info['ip']);
        $this->ocr['notifyUrl'] = $this->url->link('extension/payment/payu/ordernotify', '', true);
        $this->ocr['continueUrl'] = $this->url->link('checkout/success', '', true);
        $this->ocr['currencyCode'] = $order_info['currency_code'];
        $this->ocr['totalAmount'] = $this->toAmount(
            $this->currencyFormat($order_info['total'], $order_info['currency_code'])
        );
        $this->ocr['extOrderId'] = uniqid($order_info['order_id'] . '-', true);
        $this->ocr['settings']['invoiceDisabled'] = true;

        //OCR customer data
        $this->buildCustomerInOrder($order_info);

        //OCR products
        $this->buildProductsInOrder($this->model_checkout_order->getOrderProducts($order_info['order_id']), $order_info['currency_code']);

        //OCR shipping
        if ($order_info['shipping_code']) {
            $totals = $this->model_checkout_order->getOrderTotals($order_info['order_id']);
            $shipping = array_values(array_filter($totals, function ($total) {
                return $total['code'] === 'shipping';
            }))[0];

            $this->buildShippingInOrder(['title' => $shipping['title'], 'cost' => $shipping['value']], $order_info['currency_code']);
        }

        if ($this->ocr['totalAmount'] < $this->totalWithoutDiscount) {
            $this->buildDiscountInOrder($this->ocr['totalAmount']);
        }

        return $this->ocr;

    }

    /**
     * @param array $order_info
     */
    private function setSecondaryKeysIfNeeded($order_info)
    {
        $secondary_geo_zone = $this->config->get('payment_payu_secondary_geo_zone_id');
        if ($secondary_geo_zone && $this->model_extension_payment_payu->matchGeoZone($order_info['payment_zone_id'], $secondary_geo_zone)) {
            OpenPayU_Configuration::setMerchantPosId($this->config->get('payment_payu_secondary_merchantposid'));
            OpenPayU_Configuration::setSignatureKey($this->config->get('payment_payu_secondary_signaturekey'));
            OpenPayU_Configuration::setOauthClientId($this->config->get('payment_payu_secondary_oauth_client_id'));
            OpenPayU_Configuration::setOauthClientSecret($this->config->get('payment_payu_secondary_oauth_client_secret'));
        }
    }

    /**
     * @param array $order_info
     */
    private function buildCustomerInOrder($order_info)
    {
        if (!empty($order_info['email'])) {
            $this->ocr['buyer'] = array(
                'email' => $order_info['email'],
                'firstName' => $order_info['firstname'],
                'lastName' => $order_info['lastname'],
                'phone' => $order_info['telephone'],
                'language' => substr($this->session->data['language'], 0, 2),
            );
        }
    }

    /**
     * @param array $products
     */
    private function buildProductsInOrder($products)
    {
        foreach ($products as $product) {
            $this->ocr['products'][] = array(
                'quantity' => (int) $product['quantity'],
                'name' => substr($product['name'], 0, 250),
                'unitPrice' => $this->toAmount($product['price']),
            );

            $this->totalWithoutDiscount += $this->toAmount($product['total']);
        }
    }


    /**
     * @param int $total
     */
    private function buildDiscountInOrder($total)
    {
        $this->ocr['products'][] = array(
            'quantity' => 1,
            'name' => $this->language->get('text_payu_discount'),
            'unitPrice' => $total - $this->totalWithoutDiscount
        );

    }

    /**
     * @param array $shippingMethod
     */
    private function buildShippingInOrder($shippingMethod, $currencyCode)
    {
        $itemGross = $this->toAmount($this->currencyFormat($shippingMethod['cost'], $currencyCode));
        $this->ocr['products'][] = array(
            'quantity' => 1,
            'name' => $shippingMethod['title'],
            'unitPrice' => $itemGross
        );
        $this->totalWithoutDiscount += $itemGross;
    }

    /**
     * Convert to amount
     *
     * @param $value
     * @return int
     */
    private function toAmount($value)
    {
        return number_format($value * 100, 0, '', '');
    }

    /**
     * Currency format
     *
     * @param float $value
     * @return float
     */
    private function currencyFormat($value, $currencyCode)
    {
        return $this->currency->format($value, $currencyCode, '', false);
    }

    private function getIP($orderIP)
    {
        return $orderIP == "::1"
        || $orderIP == "::"
        || !preg_match(
            "/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m",
            $orderIP
        )
            ? '127.0.0.1' : $orderIP;
    }

}
