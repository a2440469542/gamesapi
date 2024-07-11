<?php
namespace app\service\game;

class PgGamePlatformService extends BaseGamePlatformService
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
    protected function generateSign($params)
    {
        ksort($params);
        $signStr = http_build_query($params) . "&key=" . $this->secretKey;
        return md5($signStr);
    }
    public function registerUser($user)
    {
        $apiUrl = '/api/web/user_session/';
        $params['operator_token'] = $this->operatorToken;
        $params['user_id'] = $user['cid'].'_'.$user['uid'];
        $params['channelid'] = (string) $user['cid'];
        $params['channelname'] = $user['cname'];
        $params['user_name'] = $user['cid'].'_'.$user['user'];
        $params['currency'] = 'BRL';
        $params['ts'] = time();
        $params['sign'] = $this->generateSign($params);
        write_log($params,'PgGame');
        $headers = [
            'Content-Type: application/json'
        ];
        write_log("注册用户请求地址：".$this->baseUrl.$apiUrl,'PgGame');
        $row = $this->request($apiUrl, json_encode($params), $headers);
        write_log($row,'PgGame');
        if($row['status'] == 0){
            return ['code'=>0, 'msg'=>'登录成功','token'=>$row['data']['token'],'user'=>$params['user_name'],'player_id'=>$params['user_id'],'is_login'=>1];
        }else{
            return ['code'=>$row['status'], 'msg'=>$row['msg']];
        }
    }

    public function getGameUrl($user,$game)
    {
        $apiUrl = '/api/web/game_url/';

        $params['operator_token'] = $this->operatorToken;
        $params['user_id'] = $user['cid'].'_'.$user['uid'];
        $params['user_token'] = $user['user_token'];
        $params['language'] = "pt";
        $params['game_code'] = $game['code'];
        $params['ts'] = time();
        $params['sign'] = $this->generateSign($params);
        write_log($params,'PgGame');
        $headers = [
            'Content-Type: application/json'
        ];
        write_log("获取游戏请求地址：".$this->baseUrl.$apiUrl,'PgGame');
        $response = $this->request($apiUrl, json_encode($params), $headers);
        write_log($response,'PgGame');
        if(isset($response['code']) && $response['code'] == 0){
            return ['code'=>0, 'msg'=>'获取成功','url'=>$response['url']];
        }else{
            return ['code'=>$response['status'], 'msg'=>$response['msg']];
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