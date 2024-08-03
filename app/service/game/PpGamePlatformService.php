<?php
namespace app\service\game;

class PpGamePlatformService extends BaseGamePlatformService
{
    protected $baseUrl;
    protected $data;
    public function __construct($config, $data=[])
    {
        // 初始配置
        $this->data = $data;
        parent::__construct($config, $data);
        $this->operatorToken = $config['app_id'];
        $this->secretKey = $config['app_secret'];
        $this->baseUrl = $config['url']; // 设置为实际的baseUrl
    }
    protected function generateSign($params,$time)
    {
        //ksort($params);
        $signStr = json_encode($params) . $time . $this->secretKey;
        return md5($signStr);
    }
    public function registerUser($user)
    {
        $token = md5(uniqid(md5(microtime(true)),true));
        return ['code'=>0, 'msg'=>'登录成功','token'=>$token,'user'=>$user['cid'].'_'.$user['user'],'player_id'=>$user['cid'].'_'.$user['uid'],'is_login'=>0];
    }

    public function getGameUrl($user,$game)
    {
        $apiUrl = '/api/web/game_url/';

        $params['operator_token'] = $this->operatorToken;
        $params['uname'] = $user['cid'].'_'.$user['user'];

        $params['gameid'] = $game['code'];
        $params['user_token'] = $user['user_token'];
        $params['lang'] = "pt";
        write_log($params,'PpGame');
        $time = time();
        $headers = [
            'Content-Type: application/json;charset=UTF-8',
            'X-Atgame-Mchid:'.$this->operatorToken,
            'X-Atgame-Timestamp:'.$time,
            'X-Atgame-Sign:'.$this->generateSign($params,$time)
        ];
        write_log($headers,'PpGame');
        write_log("获取游戏请求地址：".$this->baseUrl.$apiUrl,'PpGame'.$user['cid']);
        $response = $this->request($apiUrl, json_encode($params), $headers);
        write_log($response,'PpGame');
        if(isset($response['code']) && $response['code'] == 0){
            return ['code'=>0, 'msg'=>'获取成功','url'=>$response['data']['gameurl']];
        }else{
            return ['code'=>$response['code'], 'msg'=>$response['msg']];
        }
    }
    // 其它对应的API方法...
    public function balanceUser($user)
    {
        // TODO: Implement balanceUser() method.
    }

    public function withdrawUser($user, $amount)
    {
        // TODO: Implement withdrawUser() method.
    }

    public function depositUser($user)
    {
        // TODO: Implement depositUser() method.
    }
}