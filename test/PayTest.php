<?php
/**
 * Created by pay
 * Author : Jensen
 * Date : 2022/5/14
 * Time : 5:35
 */

use Jensen\Pay\SandePay;

require_once __DIR__ . '/../vendor/autoload.php';
$config = [
    'merNo' => '6888801041218',
    'key1' => '0P7wXT5g4B7Mk4Kr6SEYa0IqOr2mmUCaq2zwgTSNu3Jwx0vFwRw8fWCAxTCKOgpumYxnKeEBSgc=',
    'md5key' => 'f/79lJyS8m31PpgzcVF9oI0iYRzatEOytHpEzX0fPqasGsSGGHo7+KVEBE8TH+hZN+xDEiKade885DNW160xLWEwu4EcMmNV1suTN1td/ukzWQFMscbv8IksLGabJStTta9x21jtQaRixuz+UKFTiw==',
    'payChannel' => 3,
    'payType' => 4,
];
$pay = new SandePay($config);
$url = $pay->getPayment('0.01', '测试', '123');
var_dump($url);
exit;