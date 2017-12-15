<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 2017/11/27
 * Time: 下午3:19
 */

class ModelPaymentPChomePay extends Model
{
    const BASE_URL = "https://api.pchomepay.com.tw/v1";
    const SB_BASE_URL = "https://sandbox-api.pchomepay.com.tw/v1";

    public function getMethod($address, $total)
    {
        $this->load->language('payment/pchomepay');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('pchomepay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('pchomepay_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('pchomepay_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $currencies = array(
            'AUD',
            'CAD',
            'EUR',
            'GBP',
            'JPY',
            'USD',
            'NZD',
            'CHF',
            'HKD',
            'SGD',
            'SEK',
            'DKK',
            'PLN',
            'NOK',
            'HUF',
            'CZK',
            'ILS',
            'MXN',
            'MYR',
            'BRL',
            'PHP',
            'TWD',
            'THB',
            'TRY',
            'RUB'
        );

        if (!in_array(strtoupper($this->session->data['currency']), $currencies)) {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code' => 'pchomepay',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('pchomepay_sort_order')
            );
        }

        return $method_data;
    }

    public function baseURL()
    {
        $sandBox_mode = $this->config->get('pchomepay_test');
        $baseURL = $sandBox_mode ? ModelPaymentPChomePay::SB_BASE_URL : ModelPaymentPChomePay::BASE_URL;

        return $baseURL;
    }

    // 建立訂單
    public function postPayment($data)
    {
        $token = $this->getToken()->token;
        $postPaymentURL = $this->baseURL() . '/payment';

        $result = $this->postAPI($token, $postPaymentURL, $data);

        return $this->handleResult($result);
    }

    // 建立退款
    public function postRefund($data)
    {
        $token = $this->getToken()->token;
        $postRefundURL = $this->baseURL() . '/refund';

        $result = $this->postAPI($token, $postRefundURL, $data);

        return $this->handleResult($result);

    }

    // 查詢訂單
    public function getPayment($orderID)
    {
        if (!is_string($orderID) || stristr($orderID, "/")) {
            throw new Exception('Order does not exist!', 20002);
        }

        $token = $this->getToken()->token;
        $getPaymentURL = $this->baseURL() . '/payment/{order_id}';

        $result = $this->getAPI($token, str_replace("{order_id}", $orderID, $getPaymentURL));

        return $this->handleResult($result);

    }

    // 取Token
    public function getToken()
    {
        $tokenURL = $this->baseURL() . "/token";
        $sandBox_mode = $this->config->get('pchomepay_test');

        $appID = $this->config->get('pchomepay_appid');
        $secret = $sandBox_mode ? $this->config->get('pchomepay_sandbox_secret') : $this->config->get('pchomepay_secret');

        $userAuth = "{$appID}:{$secret}";

        $body = $this->postToken($userAuth, $tokenURL);

        return $this->handleResult($body);
    }

    /**
     * @param $url
     * @param $userAuth
     * @return string
     */
    private function postToken($userAuth, $url)
    {
        return $this->post($url, null, [], ["CURLOPT_USERPWD" => $userAuth]);
    }

    private function postAPI($token, $url, $data)
    {
        return $this->post($url, null, ["pcpay-token: {$token}"], ["CURLOPT_POSTFIELDS" => $data]);
    }

    private function getAPI($token, $url, $data = [])
    {
        return $this->get($url, $data, ["pcpay-token: $token"]);

    }


    /**
     * @param $url
     * @param $params
     * @param array $headers
     * @param array $settings
     * @param int $timeout
     * @return mixed
     */
    private function post($url, $params, array $headers = null, array $settings = [], $timeout = 500)
    {
        $reqData = $this->parseReqData($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        if ($headers !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($settings)) {
            foreach ($settings as $key => $value) {
                if (defined($key)) {
                    curl_setopt($ch, constant($key), $value);
                }
            }
        }

        $content = curl_exec($ch);

        $err = curl_errno($ch);

        if ($err) {
            $errMessage = "curl error => (" . $err . ")" . curl_error($ch);
            curl_close($ch);
            throw new RuntimeException($errMessage);
        }

        curl_close($ch);
        return $content;
    }

    /**
     * @param $params
     * @return string
     */
    private function parseReqData($params)
    {
        $reqData = '';
        if (is_array($params) && !empty($params)) {
            foreach ($params as $key => $value) {
                $reqData .= "{$key}={$value}&";
            }
            $reqData = rtrim($reqData, '&');
        } else {
            $reqData = $params;
        }

        return $reqData;
    }

    private function get($url, $params, array $headers = null, array $settings = [], $timeout = 500)
    {
        $query = "?";

        if ($params !== null) {
            $query .= http_build_query($params);
        }

        $query .= "&xdebug_session_start=PHPSTORM";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        if ($headers !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($this->ignoreSSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        if (!empty($settings)) {
            foreach ($settings as $key => $value) {
                if (defined($key)) {
                    curl_setopt($ch, constant($key), $value);
                }
            }
        }

        $content = curl_exec($ch);

        $err = curl_errno($ch);

        if ($err) {
            $errMessage = "curl error => (" . $err . ")" . curl_error($ch);
            curl_close($ch);
            throw new RuntimeException($errMessage);
        }

        curl_close($ch);
        return $content;
    }

    private function handleResult($result)
    {
        $jsonErrMap = [
            JSON_ERROR_NONE => 'No error has occurred',
            JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded	PHP 5.3.3',
            JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded	PHP 5.5.0',
            JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded	PHP 5.5.0',
            JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given	PHP 5.5.0'
        ];

        $obj = json_decode($result);

        $err = json_last_error();

        if ($err) {
            $errStr = "($err)" . $jsonErrMap[$err];
            if (empty($errStr)) {
                $errStr = " - unknow error, error code ({$err})";
            }
            throw new Exception("server result error($err) {$errStr}:$result");
        }

        if (property_exists($obj, "error_type")) {
            throw new Exception($obj->message, $obj->code);
        }

        return $obj;
    }

    public function formatOrderTotal($order_total)
    {
        return intval(round($order_total));
    }

    public function ocLog($message)
    {
        $message = json_encode($message);
        $today = date('Ymd');
        $log = new Log("PChomePay-{$today}.log");
        $log->write('class ' . get_class() . ' : ' . $message . "\n");
    }

}