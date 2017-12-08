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

        $baseURL = $data['testmode'] ? ControllerPaymentPChomePay::SB_BASE_URL : ControllerPaymentPChomePay::BASE_URL;

        $this->load->model('checkout/order');
        $this->load->model('payment/pchomepay');

        $postPaymentData = $this->getPChomepayPaymentData();


        exit();

        if ($order_info) {
            $data['item_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

            foreach ($this->cart->getProducts() as $product) {
                $option_data = array();

                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($upload_info) {
                            $value = $upload_info['name'];
                        } else {
                            $value = '';
                        }
                    }

                    $option_data[] = array(
                        'name'  => $option['name'],
                        'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                    );
                }

                $data['products'][] = array(
                    'name'     => htmlspecialchars($product['name']),
                    'model'    => htmlspecialchars($product['model']),
                    'price'    => $this->currency->format($product['price'], $order_info['currency_code'], false, false),
                    'quantity' => $product['quantity'],
                    'option'   => $option_data,
                    'weight'   => $product['weight']
                );
            }

            $data['discount_amount_cart'] = 0;

            $total = $this->currency->format($order_info['total'] - $this->cart->getSubTotal(), $order_info['currency_code'], false, false);

            if ($total > 0) {
                $data['products'][] = array(
                    'name'     => $this->language->get('text_total'),
                    'model'    => '',
                    'price'    => $total,
                    'quantity' => 1,
                    'option'   => array(),
                    'weight'   => 0
                );
            } else {
                $data['discount_amount_cart'] -= $total;
            }

            $data['currency_code'] = $order_info['currency_code'];
            $data['first_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
            $data['last_name'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
            $data['address1'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
            $data['address2'] = html_entity_decode($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');
            $data['city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
            $data['zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
            $data['country'] = $order_info['payment_iso_code_2'];
            $data['email'] = $order_info['email'];
            $data['invoice'] = $this->session->data['order_id'] . ' - ' . html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
            $data['lc'] = $this->session->data['language'];
            $data['return'] = $this->url->link('checkout/success');
            $data['notify_url'] = $this->url->link('payment/pchomepay/callback', '', true);
            $data['cancel_return'] = $this->url->link('checkout/checkout', '', true);

            if (!$this->config->get('pchomepay_transaction')) {
                $data['paymentaction'] = 'authorization';
            } else {
                $data['paymentaction'] = 'sale';
            }

            $data['custom'] = $this->session->data['order_id'];

            $this->ocLog(json_encode($data));

            return $this->load->view('payment/pchomepay', $data);
        }

        $data['custom'] = $this->session->data['order_id'];
        $data['action'] = 'https://123.123.123';

        return $this->load->view('payment/pchomepay', $data);
    }

    private function getPChomepayPaymentData()
    {
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {
            $order_id = date('Ymd') . $order_info['order_id'];
            $pay_type = $this->config->get('pchomepay_payment_methods');
            $amount = $this->model_payment_pchomepay->formatOrderTotal($order_info['total']);
            $return_url = $this->url->link('checkout/success');
            $notify_url = $this->url->link('payment/pchomepay/callback', '', true);
            $buyer_email = $order_info['email'];

            $atm_expiredate = $this->config->get('pchomepay_atm_expiredate');

            if (isset($atm_expiredate) && (!preg_match('/^\d*$/', $atm_expiredate) || $atm_expiredate < 1 || $atm_expiredate > 5)) {
                $atm_expiredate = 5;
            }

            $atm_info = (object)['expire_days' => (int)$atm_expiredate];

            $this->config->get('');
            $card_info = [];

            foreach ($this->card_installment as $items) {
                switch ($items) {
                    case 'CARD_3' :
                        $card_installment['installment'] = 3;
                        break;
                    case 'CARD_6' :
                        $card_installment['installment'] = 6;
                        break;
                    case 'CARD_12' :
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

            $order_items = $order->get_items();
            foreach ($order_items as $item) {
                $product = [];
                $order_item = new WC_Order_Item_Product($item);
                $product_id = ($order_item->get_product_id());
                $product['name'] = $order_item->get_name();
                $product['url'] = get_permalink($product_id);

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

            $pchomepay_args = apply_filters('woocommerce_pchomepay_args', $pchomepay_args);

            return $pchomepay_args;
        }

        return null;
    }

    public function callback()
    {

    }



    public function ocLog($message)
    {
        $today = date('Ymd');
        $log = new Log("PChomePay-{$today}.log");
        $log->write('class ' . get_class() . ' : ' . $message . "\n");
    }
}