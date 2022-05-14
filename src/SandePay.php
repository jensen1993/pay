<?php
/**
 * Created by pay
 * Author : Jensen
 * Date : 2022/5/14
 * Time : 4:18
 */

namespace Jensen\Pay;

use Jensen\Pay\helper;

/**
 * Class SandePay
 * @package SandePay
 * @Author : Jensen
 * @Time : 2022/5/14 4:50
 */
class SandePay
{
    private $config = [
        'payChannel' => 3,
    ];

    public function __get($name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    /**
     * SandePay constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        if (!is_array($config) && !empty($config)) {
            throw new \Exception('请传入正确参数');
        }
        if (empty($config['notifyUrl'])) {
            throw new \Exception('请写入回调地址');
        }
        if (empty($config['key1'])) {
            throw new \Exception('请传入Key1');
        }
        if (empty($config['merNo'])) {
            throw new \Exception('请传入merNo');
        }
        if (empty($config['md5key'])) {
            throw new \Exception('请传入md5key');
        }
        if (!empty($config['payType'] && $config['payType'] == 3)) {
            if (empty($config['staticUrl'])) {
                throw new \Exception('请传入staticUrl');
            }
        }
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @Explain : 根据支付渠道获取支付参数
     * @param $amount
     * @param $name
     * @param $sn
     * @param $uid
     * @return string
     * @throws \Exception
     * @Date : 2022/5/14 4:50
     * @Author : By Jensen
     */
    public function getPayment($amount, $name, $sn, $uid)
    {
        $payType = $this->payType;
        $return = $this->SdPay($uid, $payType, $amount, $name, $sn);
        return $return;
    }

    /**
     * @Explain : 根据支付方式选择支付
     * @param $uid
     * @param $payType
     * @param $amount
     * @param $name
     * @param $sn
     * @return string
     * @throws \Exception
     * @Date : 2022/5/14 4:45
     * @Author : By Jensen
     */
    private function SdPay($uid, $payType, $amount, $name, $sn)
    {
        if ($payType == 3) {
            return $this->Alipay($amount, $name, $sn);
        } elseif ($payType == 4) {
            return $this->WechatPay($amount, $name, $sn);
        } elseif ($payType == 5) {
            return $this->BankPay($uid, $amount, $name, $sn);
        }
        throw new \Exception('支付错误');
    }

    /**
     * @Explain : 支付宝支付
     * @param $amount
     * @param $name
     * @param $sn
     * @return string
     * @Date : 2022/5/14 4:43
     * @Author : By Jensen
     */
    private function Alipay($amount, $name, $sn)
    {
        return $this->PayData($amount, $name, $sn, '02020002', 'alipayh5', '');
    }

    /**
     * @Explain : 微信支付
     * @param $amount
     * @param $name
     * @param $sn
     * @return string
     * @Date : 2022/5/14 4:42
     * @Author : By Jensen
     */
    private function WechatPay($amount, $name, $sn)
    {
        return $this->PayData($amount, $name, $sn, '02010006', 'applet', $this->staticUrl);
    }

    /**
     * @Explain : 银行卡支付
     * @param $uid
     * @param $amount
     * @param $name
     * @param $sn
     * @return string
     * @throws \Exception
     * @Date : 2022/5/14 下午10:05
     * @Author : By Jensen
     */
    private function BankPay($uid, $amount, $name, $sn)
    {
        $price = bcmul($amount, 100, 0);
        $len = strlen($price);
        if ($len < 12) {
            $price = self::getZero(12 - $len) . $price;
        }
        $data = array(
            'head' => array(
                'version' => '1.0',
                'method' => 'sandPay.fastPay.quickPay.index',
                'productId' => "00000016",
                'accessType' => "1",
                'mid' => $this->merNo,
                'plMid' => "",
                'channelType' => "07",
                'reqTime' => date('YmdHis', time()),
            ),
            'body' => [
                'userId' => $uid,
                'orderCode' => $sn,
                'orderTime' => date('YmdHis', time()),
                'totalAmount' => $price,
                'subject' => $name,
                'body' => $name,
                'currencyCode' => 156,
                'notifyUrl' => $this->notifyUrl,
                'frontUrl' => $this->returnUrl,
            ],
        );
        $postData = array(
            'charset' => 'utf-8',
            'signType' => '01',
            'data' => json_encode($data),
            'sign' => self::sign($data),
        );
        $request = request();
        $url = $request->root(true) . '/index/bank/index?shandeInfo=' . base64_encode(json_encode($postData));
        return $url;
    }

    /**
     * @Explain : 组装支付数据
     * @param $amount
     * @param $name
     * @param $sn
     * @param $productCode
     * @param $function
     * @param $wechat
     * @return string
     * @Date : 2022/5/14 4:41
     * @Author : By Jensen
     */
    private function PayData($amount, $name, $sn, $productCode, $function, $wechat)
    {
        $ip = helper::getIp();
        $ip = str_replace('.', '_', $ip);
        $isSign = [
            'mer_key' => $this->key1,
            'version' => '10',
            'mer_no' => $this->merNo,
            'mer_order_no' => $sn,
            'create_time' => date('YmdHis'),
            'order_amt' => $amount,
            'notify_url' => $this->notifyUrl,
            'create_ip' => $ip,
            'accsplit_flag' => 'NO',
            'sign_type' => 'MD5',
            'store_id' => '000000',
        ];
        if (!empty($wechat)) {
            $isSign['gh_static_url'] = $wechat;
        }
        ksort($isSign);
        $str = urldecode(http_build_query($isSign)) . '&key=' . $this->md5key;
        $sign = strtoupper(md5($str));
        $isSign['sign'] = $sign;
        if (!empty($wechat)) {
            unset($isSign['gh_static_url']);
        }
        $isNotSign = [
            'expire_time' => date('YmdHis', time() + 3600),
            'goods_name' => $name,
            'clear_cycle' => 0,
            'meta_option' => '[{"s":"Android","n":"","id":"","sc":""},{"s":"IOS","n":"","id":"","sc":""}]',
            'product_code' => $productCode,
        ];
        $data = array_merge($isSign, $isNotSign);
        $str = http_build_query($data);
        if (!empty($wechat)) {
            $str .= '&gh_static_url=' . $wechat;
        }
        $url = 'https://sandcash.mixienet.com.cn/h5/?' . $str . '#/' . $function;
        return $url;
    }


    /**
     * @Explain : 获取公钥
     * @return mixed
     * @throws \Exception
     * @Date : 2021/6/26 上午1:06
     * @Author : By Jensen
     */
    private function publicKey()
    {
        try {
            $file = file_get_contents($this->publicKeySD);
            if (!$file) {
                throw new \Exception('getPublicKey::file_get_contents ERROR');
            }
            $cert = chunk_split(base64_encode($file), 64, "\n");
            $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";
            $res = openssl_pkey_get_public($cert);
            $detail = openssl_pkey_get_details($res);
            openssl_free_key($res);
            if (!$detail) {
                throw new \Exception('getPublicKey::openssl_pkey_get_details ERROR');
            }
            return $detail['key'];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @Explain : 私钥
     * @return mixed
     * @throws \Exception
     * @Date : 2021/7/9 下午7:26
     * @Author : By Jensen
     */
    private function privateKey()
    {
        try {
            $file = file_get_contents($this->privateKeySD);
            if (!$file) {
                throw new \Exception('getPrivateKey::file_get_contents');
            }
            if (!openssl_pkcs12_read($file, $cert, $this->privatePwd)) {
                throw new \Exception('getPrivateKey::openssl_pkcs12_read ERROR');
            }
            return $cert['pkey'];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @Explain : 签名
     * @param $plainText
     * @return string
     * @throws \Exception
     * @Date : 2022/5/14 4:25
     * @Author : By Jensen
     */
    protected function sign($plainText)
    {
        $plainText = json_encode($plainText);
        try {
            $resource = openssl_pkey_get_private($this->privateKey());
            $result = openssl_sign($plainText, $sign, $resource);
            openssl_free_key($resource);
            if (!$result) throw new \Exception('sign error');
            return base64_encode($sign);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @Explain : 公钥验签
     * @param $plainText
     * @param $sign
     * @return int|string
     * @Date : 2022/5/14 4:25
     * @Author : By Jensen
     */
    public function verify($plainText, $sign)
    {
        $resource = openssl_pkey_get_public($this->publicKey());
        $result = openssl_verify($plainText, base64_decode($sign), $resource);
        openssl_free_key($resource);
        if (!$result) {
            return '签名验证未通过,plainText:' . $plainText . '。sign:' . $sign;
        }
        return $result;
    }

    /**
     * @Explain : curl 请求
     * @param $url
     * @param $params
     * @return bool|string
     * @Date : 2022/5/14 4:24
     * @Author : By Jensen
     */
    protected function httpPost($url, $params)
    {
        if (empty($url) || empty($params)) {
            return '请求参数错误';
        }
        $params = http_build_query($params);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $data = curl_exec($ch);
            $err = curl_error($ch);
            $errno = curl_errno($ch);
            if ($errno) {
                $msg = 'curl errInfo: ' . $err . ' curl errNo: ' . $errno;
                return $msg;
            }
            curl_close($ch);
            return $data;
        } catch (\Exception $e) {
            if ($ch) curl_close($ch);
            return $e->getMessage();
        }
    }

    /**
     * @Explain : 解析返回数据
     * @param $result
     * @return array
     * @Date : 2021/7/9 下午7:51
     * @Author : By Jensen
     */
    protected function parseResult($result)
    {
        $arr = array();
        $response = urldecode($result);
        $arrStr = explode('&', $response);
        foreach ($arrStr as $str) {
            $p = strpos($str, "=");
            $key = substr($str, 0, $p);
            $value = substr($str, $p + 1);
            $arr[$key] = $value;
        }

        return $arr;
    }
}