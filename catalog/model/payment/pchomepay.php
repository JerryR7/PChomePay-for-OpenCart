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

    public function getToken()
    {
        $tokenURL = $this->baseURL() . "/token";
        $sandBox_mode = $this->config->get('pchomepay_test');

        $appID = $this->config->get('pchomepay_appid');
        $secret = $sandBox_mode ? $this->config->get('pchomepay_sandbox_secret') : $this->config->get('pchomepay_secret');

        $userAuth = "{$appID}:{$secret}";

        $body = $this->postToken($userAuth, $tokenURL);
        $this->handleResult($body);
        $this->token = new PPToken($body);
        $this->tokenStorage->saveTokenStr($this->token->getJson());

        return $this->token;
    }

    /**
     * @param $url
     * @param $params
     * @param array $headers
     * @param array $settings
     * @param int $timeout
     * @return mixed
     */
    public function post($url, $params, array $headers = null, array $settings = [], $timeout = 500)
    {
        $reqData = $this->parseReqData($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

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


    /**
     * @param $url
     * @param $userAuth
     * @return string
     */
    public function postToken($userAuth, $url)
    {
        return $this->post($url, null, [], ["CURLOPT_USERPWD" => $userAuth]);
    }

    public function postAPI($token, $url, $data)
    {
        return $this->post($url, null, ["pcpay-token: {$token}"], ["CURLOPT_POSTFIELDS" => $data]);
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
            $expClass = Exception::getExceptionClassNameByErrorType($obj->error_type);
            if (class_exists($expClass)) {
                throw new $expClass($obj->message, $obj->code);
            } else {
                throw new Exception($obj->message, $obj->code);
            }
        }
        return $obj;
    }

}