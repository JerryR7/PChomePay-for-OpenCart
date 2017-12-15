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
        $data['action'] = $this->url->link('payment/pchomepay/redirect', '', 'SSL');;

        return $this->load->view('payment/pchomepay', $data);
    }

    public function redirect()
    {
        $this->load->language('payment/pchomepay');

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['testmode'] = $this->config->get('pchomepay_test');

        $baseURL = $data['testmode'] ? ControllerPaymentPChomePay::SB_BASE_URL : ControllerPaymentPChomePay::BASE_URL;

        $this->load->model('checkout/order');
        $this->load->model('payment/pchomepay');

        $postPaymentData = $this->getPChomepayPaymentData();

        if ($postPaymentData) {
            try {
                // 建立訂單
                $result = $this->model_payment_pchomepay->postPayment($postPaymentData);

                $this->ocLog($result);exit();

                if (!$result) {
                    $this->ocLog("交易失敗：伺服器端未知錯誤，請聯絡 PChomePay支付連。");
                    throw new Exception("嘗試使用付款閘道 API 建立訂單時發生錯誤，請聯絡網站管理員。");
                }
            } catch (Exception $exception) {
                $this->ocLog($exception->getMessage());
            }
        }

        $data['custom'] = $this->session->data['order_id'];
        $data['action'] = 'https://123.123.123';
    }

    private function getPChomepayPaymentData()
    {
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {
            $order_id = date('Ymd') . $order_info['order_id'];
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

            return json_encode($pchomepay_args);
        }

        return null;
    }

    public function callback()
    {

    }


    public function ocLog($message)
    {
        $message = json_encode($message);
        $today = date('Ymd');
        $log = new Log("PChomePay-{$today}.log");
        $log->write('class ' . get_class() . ' : ' . $message . "\n");
    }
}