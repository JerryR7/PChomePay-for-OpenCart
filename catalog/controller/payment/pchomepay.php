<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 2017/11/27
 * Time: 下午2:53
 */

class ControllerPaymentPChomePay extends Controller
{
    public function index()
    {
        $this->load->language('payment/pchomepay');

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['testmode'] = $this->config->get('pchomepay_test');

        if (!$data['testmode']) {
            $data['action'] = 'https://api.pchomepay.com.tw/v1';
        } else {
            $data['action'] = 'https://sandbox-api.pchomepay.com.tw/v1';
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {
            $data['business'] = $this->config->get('pp_standard_email');
            $data['item_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

            $this->ocLog(json_encode($order_info));
            foreach ($this->cart->getProducts() as $product) {
                $this->ocLog(json_encode($product));
            }
            $this->ocLog(json_encode($data));
        }

        $data['custom'] = $this->session->data['order_id'];

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