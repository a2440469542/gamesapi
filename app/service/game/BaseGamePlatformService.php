<?php
namespace app\service\game;

abstract class BaseGamePlatformService
{
    protected $operatorToken;
    protected $secretKey;

    public function __construct($config, $data=[])
    {
        $this->operatorToken = $config['app_id'];
        $this->secretKey = $config['app_secret'];
    }

    protected function request($uri, $params, $headers)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $uri);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return ['status' => '500', 'msg' => curl_error($ch)];
        }

        curl_close($ch);
        return json_decode($response, true);
    }
    protected function generateUniqueOrderId($prefix = 'ORD') {
        // 获取当前时间戳（精确到毫秒）
        $timestamp = round(microtime(true) * 1000);

        // 生成一个随机数
        $randomNumber = mt_rand(100000, 999999);

        // 获取当前进程ID
        $processId = getmypid();

        // 组合成唯一订单号
        $orderId = sprintf('%s-%d-%d-%d', $prefix, $timestamp, $randomNumber, $processId);

        return $orderId;
    }

    abstract public function registerUser($user);
    abstract public function balanceUser($user);
    //下分
    abstract public function withdrawUser($user,$amount);
    //上分
    abstract public function depositUser($user);
    abstract public function getGameUrl($user,$game);
    // ... 其它方法
}