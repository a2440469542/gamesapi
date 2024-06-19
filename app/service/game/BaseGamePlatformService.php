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

    protected function request($uri, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $uri);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return ['status' => '500', 'msg' => curl_error($ch)];
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    abstract public function registerUser($user);
    abstract public function getGameUrl($user,$game);
    // ... 其它方法
}