<?php
namespace app\service\game;

class JiliGamePlatformService extends BaseGamePlatformService
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
        $str = '';
        foreach ($params as $key => $value) {
            $str .= $value;
        }
        $str .= $this->secretKey;
        return md5($str);
    }
    public function registerUser($user)
    {
        $apiUrl = '/player/create';
        $params['merchant_id'] = $this->operatorToken;
        $params['player_id'] = $user['cid'].'_'.$user['uid'];
        $params['player_name'] = (string) $user['cid'];
        $params['currency'] = 'BRL';
        $params['language'] = "pt-BR";
        $params['timestamp'] = time();
        $params['sign'] = $this->generateSign($params);
        write_log($params,'JiliGame');
        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        write_log("请求地址：".$this->baseUrl.$apiUrl,'JiliGame');
        $row = $this->request($apiUrl, http_build_query($params), $headers);
        write_log($row,'JiliGame');
        if($row['success'] == 1){
            return ['code'=>0, 'msg'=>'登录成功','token'=>$row['data']['token']];
        }else{
            return ['code'=>$row['code'], 'msg'=>$row['message']];
        }
    }

    public function getGameUrl($user,$game)
    {
        $apiUrl = '/player/play_url';

        $params['merchant_id'] = $this->operatorToken;
        $params['player_id'] = $user['cid'].'_'.$user['uid'];
        $params['currency'] = 'BRL';
        $params['gameid'] = $game['code'];
        $params['timestamp'] = time();
        $params['sign'] = $this->generateSign($params);
        $params['language'] = "pt-BR";
        write_log("获取游戏链接",'JiliGame');
        write_log($params,'JiliGame');
        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        write_log("请求地址：".$this->baseUrl.$apiUrl,'JiliGame');
        $response = $this->request($apiUrl, http_build_query($params), $headers);
        write_log($response,'JiliGame');
        if($response['success'] == 1){
            return ['code'=>0, 'msg'=>'获取成功','url'=>$response['play_url']];
        }else{
            return ['code'=>$response['code'], 'msg'=>$response['message']];
        }
    }
// 其它对应的API方法...
    public function withdrawUser($user,$amount)
    {
        // TODO: Implement withdrawUser() method.
        $apiUrl = '/player/withdraw';
        $params['merchant_id'] = $this->operatorToken;
        $params['player_id'] = $user['cid'].'_'.$user['uid'];
        $params['currency'] = 'BRL';
        $params['amount'] = $amount;
        $params['transactionid'] = $this->generateUniqueOrderId('JiliW');
        $params['timestamp'] = time();
        $params['sign'] = $this->generateSign($params);
        write_log("====下分====",'JiliGame');
        write_log($params,'JiliGame');
        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        $row = $this->request($apiUrl, http_build_query($params), $headers);
        write_log($row,'JiliGame');
        if($row['success'] == 1){
            return ['code'=>0, 'msg'=>'获取成功','balance'=>$row['amount'],'worder_sn'=>$row['transactionid'],'w_tx'=>$row['tx']];
        }else{
            return ['code'=>$row['code'], 'msg'=>$row['message']];
        }
    }

    public function depositUser($user)
    {
        $apiUrl = '/player/deposit';
        $params['merchant_id'] = $this->operatorToken;
        $params['player_id'] = $user['cid'].'_'.$user['uid'];
        $params['currency'] = 'BRL';
        $params['amount'] = $user['money'];
        $params['transactionid'] = $this->generateUniqueOrderId('JiliD');
        $params['timestamp'] = time();
        $params['sign'] = $this->generateSign($params);
        write_log("====上分====",'JiliGame');
        write_log($params,'JiliGame');
        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        $row = $this->request($apiUrl, http_build_query($params), $headers);
        write_log($row,'JiliGame');
        if($row['success'] == 1){
            return ['code'=>0, 'msg'=>'获取成功','money'=>$user['money'],'dorder_sn'=>$row['transactionid'],'d_tx'=>$row['tx']];
        }else{
            return ['code'=>$row['code'], 'msg'=>$row['message']];
        }
    }
    //查询余额
    public function balanceUser($user)
    {
        $apiUrl = '/player/balance';
        $params['merchant_id'] = $this->operatorToken;
        $params['player_id'] = $user['cid'].'_'.$user['uid'];
        $params['currency'] = 'BRL';
        $params['timestamp'] = time();
        $params['sign'] = $this->generateSign($params);
        write_log("====获取余额====",'JiliGame');
        write_log($params,'JiliGame');
        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        $row = $this->request($apiUrl, http_build_query($params), $headers);
        write_log($row,'JiliGame');
        if($row['success'] == 1){
            return ['code'=>0, 'msg'=>'获取成功','balance'=>$row['balance']];
        }else{
            return ['code'=>$row['code'], 'msg'=>$row['message']];
        }
    }
    // 其它对应的API方法...
    public function set_rtp_limit($user,$rtp_limit){
        $apiUrl = '/player/set_rtp_limit';
        $params['merchant_id'] = $this->operatorToken;
        $params['player_id'] = $user['cid'].'_'.$user['uid'];
        $params['rtp_limit'] = $rtp_limit;
        $params['timestamp'] = time();
        $params['sign'] = $this->generateSign($params);
        write_log("====设置RTP====",'JiliGame');
        write_log($params,'JiliGame');
        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        write_log("请求地址：".$this->baseUrl.$apiUrl,'JiliGame');
        $row = $this->request($apiUrl, http_build_query($params), $headers);
        write_log($row,'JiliGame');
        if($row['success'] == 1){
            return ['code'=>0, 'msg'=>'获取成功'];
        }else{
            return ['code'=>$row['code'], 'msg'=>$row['message']];
        }
    }
}