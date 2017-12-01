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

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

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

    public function callback()
    {

    }



    public function ocLog($message)
    {
        $today = date('Ymd');
        $log = new Log("PChomePay-{$today}.log");
        $log->write(get_class() . ' : ' . $message . "\n");
    }
}