<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 2017/11/27
 * Time: 下午2:53
 */

class ControllerPaymentPChomePay extends Controller
{
    const BASE_URL = "https://api.pchomepay.com.tw/v1";
    const SB_BASE_URL = "https://sandbox-api.pchomepay.com.tw/v1";

    protected $userAuth;
    protected $tokenStorage;
    protected $token;

    public function index()
    {
        $this->load->language('payment/pchomepay');

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['testmode'] = $this->config->get('pchomepay_test');
        $data['action'] = $this->url->link('payment/pchomepay/redirect', '', 'SSL');

        return $this->load->view('payment/pchomepay', $data);
    }

    public function redirect()
    {
        $this->load->model('checkout/order');
        $this->load->model('payment/pchomepay');

        $invoke_result = $this->model_payment_pchomepay->invokePChomePayModule();

        if (!$invoke_result) {
            throw new Exception($this->language->get('error_module_miss'));
        }

        $order_id = $this->session->data['order_id'];

        $postPaymentData = $this->getPChomepayPaymentData($order_id);

        if ($postPaymentData) {
            try {
                // 建立訂單
                $result = $this->model_payment_pchomepay->postPayment($postPaymentData);

                if (!$result) {
                    $this->ocLog("交易失敗：伺服器端未知錯誤，請聯絡 PChomePay支付連。");
                    throw new Exception("嘗試使用付款閘道 API 建立訂單時發生錯誤，請聯絡網站管理員。");
                }
            } catch (Exception $exception) {
                $this->ocLog($exception->getMessage());
            }
        }

        # Update order status and comments
        $this->model_checkout_order->addOrderHistory($order_id, pchomepayOrderStatusEnum::PENDING);

        # Clean the cart
        $this->cart->clear();

        # Add to activity log
        $this->load->model('account/activity');
        if ($this->customer->isLogged()) {
            $activity_data = array(
                'customer_id' => $this->customer->getId(),
                'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
                'order_id'    => $order_id
            );

            $this->model_account_activity->addActivity('order_account', $activity_data);
        } else {
            $activity_data = array(
                'name'     => $this->session->data['guest']['firstname'] . ' ' . $this->session->data['guest']['lastname'],
                'order_id' => $order_id
            );

            $this->model_account_activity->addActivity('order_guest', $activity_data);
        }

        # Clean the session
        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);
        unset($this->session->data['guest']);
        unset($this->session->data['comment']);
        unset($this->session->data['order_id']);
        unset($this->session->data['coupon']);
        unset($this->session->data['reward']);
        unset($this->session->data['voucher']);
        unset($this->session->data['vouchers']);
        unset($this->session->data['totals']);

        $this->response->redirect($result->payment_url);
        exit();
    }

    private function getPChomepayPaymentData($order_id)
    {
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            $order_id = 'AO' . date('Ymd') . $order_info['order_id'];
            $payment_methods = $this->config->get('pchomepay_payment_methods');
            $amount = $this->model_payment_pchomepay->formatOrderTotal($order_info['total']);
            $return_url = $this->url->link('checkout/success');
            $notify_url = $this->url->link('payment/pchomepay/callback', '', true);
            $buyer_email = $order_info['email'];

            $atm_expiredate = $this->config->get('pchomepay_atm_expiredate');

            if (isset($atm_expiredate) && (!preg_match('/^\d*$/', $atm_expiredate) || $atm_expiredate < 1 || $atm_expiredate > 5)) {
                $atm_expiredate = 5;
            }

            $pay_type = [];

            foreach ($payment_methods as $method) {
                $pay_type[] = $method;
            }

            $atm_info = (object)['expire_days' => (int)$atm_expiredate];

            $card_installment = $this->config->get('pchomepay_card_installment');
            $card_info = [];

            foreach ($card_installment as $items) {
                switch ($items) {
                    case 'CARD_3':
                        $card_installment['installment'] = 3;
                        break;
                    case 'CARD_6':
                        $card_installment['installment'] = 6;
                        break;
                    case 'CARD_12':
                        $card_installment['installment'] = 12;
                        break;
                    default :
                        unset($card_installment);
                        break;
                }
                if (isset($card_installment)) {
                    $card_info[] = (object)$card_installment;
                }
            }

            $items = [];

            foreach ($this->cart->getProducts() as $item) {
                $product = [];
                $product['name'] = $item['name'];
                $product['url'] = $this->url->link('product/product', 'product_id=' . $item['product_id']);;

                $items[] = (object)$product;
            }

            $pchomepay_args = [
                'order_id' => $order_id,
                'pay_type' => $pay_type,
                'amount' => $amount,
                'return_url' => $return_url,
                'notify_url' => $notify_url,
                'items' => $items,
                'buyer_email' => $buyer_email,
                'atm_info' => $atm_info,
            ];

            if ($card_info) $pchomepay_args['card_info'] = $card_info;

            $this->ocLog(json_encode($pchomepay_args));

            return json_encode($pchomepay_args);
        }

        return null;
    }

    public function callback()
    {
        $this->load->language('payment/allpay');
        $this->load->model('payment/pchomepay');
        $this->load->model('checkout/order');

        if (!isset($_REQUEST['notify_type']) || !isset($_REQUEST['notify_message'])) {
            http_response_code(404);
            exit;
        }

        $invoke_result = $this->model_payment_pchomepay->invokePChomePayModule();

        if (!$invoke_result) {
            throw new Exception($this->language->get('error_module_miss'));
        }

        $notify_type = $_REQUEST['notify_type'];
        $notify_message = $_REQUEST['notify_message'];

        $order_data = json_decode(str_replace('\"', '"', $notify_message));

        $this->ocLog('OpenCart order id : ' . substr($order_data->order_id, 10));

        $order_id = substr($order_data->order_id, 10);

        $this->ocLog($notify_type);
        $this->ocLog($order_data);

        $pay_type_note = '平台訂單編號: ' . $order_data->order_id . '<br>';

        # 紀錄訂單付款方式
        switch ($order_data->pay_type) {
            case 'ATM':
                $pay_type_note .= '付款方式 : ATM';
                $pay_type_note .= '<br>ATM虛擬帳號: ' . $order_data->payment_info->bank_code . ' - ' . $order_data->payment_info->virtual_account;
                break;
            case 'CARD':
                if ($order_data->payment_info->installment == 1) {
                    $pay_type_note .= '付款方式 : 信用卡 (一次付清)';
                } else {
                    $pay_type_note .= '付款方式 : 信用卡 分期付款 (' . $order_data->payment_info->installment . '期)';
                }

                if ($this->config->get('pchomepay_card_last_number')) $pay_type_note .= '<br>末四碼: ' . $order_data->payment_info->card_last_number;
                break;
            case 'ACCT':
                $pay_type_note .= '付款方式 : 支付連餘額';
                break;
            case 'EACH':
                $pay_type_note .= '付款方式 : 銀行支付';
                break;
            default:
                $pay_type_note .= $order_data->pay_type . '付款';
        }

        //  order status
        //       1        PENDING       訂單剛剛創建,等待處理.
        //       2        PROCESSING    當客戶付款完成,訂單狀態即為處理中.
        //       3        SHIPPED       當訂單已發出,訂單狀態請設為Shipped.
        //       5        COMPLETE      客戶已確認收貨,訂單狀態請設為Complete.
        //       7        CANCELLED     出於某些原因,訂單取消.請將訂單狀態設為Cancelled.
        //      10        FAILED        訂單失敗
        //      11        REFUNDED      如客戶退貨或退款.訂單狀態請設為Refunded.
        //      14        EXPIRED       訂單逾期

        if ($notify_type == 'order_audit') {
            $comment = sprintf('訂單交易等待中。<br>error code : %1$s<br>message : %2$s', $order_data->status_code, pchomepayOrderStatusEnum::getErrMsg($order_data->status_code));
            $this->model_checkout_order->addOrderHistory($order_id, pchomepayOrderStatusEnum::PENDING, $comment);
        } elseif ($notify_type == 'order_expired') {
            if ($order_data->status_code) {
                $comment = $pay_type_note . '<br>' . sprintf('訂單已失敗。<br>error code : %1$s<br>message : %2$s', $order_data->status_code, pchomepayOrderStatusEnum::getErrMsg($order_data->status_code));
                $this->model_checkout_order->addOrderHistory($order_id, pchomepayOrderStatusEnum::FAILED, $comment);
            } else {
                $this->model_checkout_order->addOrderHistory($order_id, pchomepayOrderStatusEnum::FAILED, '訂單已失敗。');
            }
        } elseif ($notify_type == 'order_confirm') {
            $this->model_checkout_order->addOrderHistory($order_id, pchomepayOrderStatusEnum::PROCESSING, $pay_type_note . '<br>訂單已成功。');
        }

        echo 'success';
        exit();
    }


    public function ocLog($message)
    {
        $message = json_encode($message);
        $today = date('Ymd');
        $log = new Log("PChomePay-{$today}.log");
        $log->write('class ' . get_class() . ' : ' . $message . "\n");
    }
}