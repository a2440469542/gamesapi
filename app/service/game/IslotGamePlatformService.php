<?php
namespace app\service\game;

class IslotGamePlatformService extends BaseGamePlatformService
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
    public function get_game_list($page,$num=20){
        $apiUrl = '/api/v1/getSlotList?agent='.$this->operatorToken;
        $params['timestamp'] = round(microtime(true) * 1000);
        $params['page'] = $page;
        $params['num'] = $num;
        $headers = [
            'Content-Type: application/json'
        ];
        $str = $this->encrypt(json_encode($params));
        $row = $this->request($apiUrl, $str, $headers);
        print_r($row);
        if($row['code'] == 200){
            return ['code'=>0, 'data'=>$row['data']];
        }else{
            return ['code'=>$row['code'], 'msg'=>$row['message']];
        }
    }
    public function get_jackpot_list(){
        $apiUrl = '/api/v1/getJackpots?agent='.$this->operatorToken;
        $params['timestamp'] = round(microtime(true) * 1000);
        $headers = ['Content-Type: application/json'];
        $str = $this->encrypt(json_encode($params));
        $row = $this->request($apiUrl, $str, $headers);
        print_r($row);
        if($row['code'] == 200){
            return ['code'=>0, 'data'=>$row['data']];
        }else{
            return ['code'=>$row['code'], 'msg'=>$row['message']];
        }
    }
    public function registerUser($user)
    {
        $apiUrl = '/api/v1/userLogin?agent='.$this->operatorToken;
        $params['timestamp'] = round(microtime(true) * 1000);
        $params['userName'] = $user['cid'].'_'.$user['uid'];
        $params['password'] = md5('123456');
        $params['currency'] = 'USD';
        $params['aliasName'] = $user['cid'].'_'.$user['user'];
        $params['ip'] = get_real_ip__();
        $headers = [
            'Content-Type: application/json'
        ];
        write_log("注册用户请求地址：".$this->baseUrl.$apiUrl,'Islot'.$user['cid']);
        write_log($params,'Islot'.$user['cid']);
        $str = $this->encrypt(json_encode($params));
        write_log("key：".$this->secretKey,'Islot'.$user['cid']);
        write_log($str,'Islot'.$user['cid']);
        $row = $this->request($apiUrl, $str, $headers);
        write_log($row,'Islot'.$user['cid']);
        if($row['code'] == 200){
            return ['code'=>0, 'msg'=>'登录成功','token'=>$row['data']['token'],'user'=>$params['aliasName'],'player_id'=>$params['userName'],'is_login'=>1];
        }else{
            return ['code'=>$row['code'], 'msg'=>$row['message']];
        }
    }

    public function getGameUrl($user,$game)
    {
        $apiUrl = '/api/v2/gameEntrance?agent='.$this->operatorToken;
        $params['timestamp'] = intval(microtime(true) * 1000);
        $params['ticket'] = $user['user_token'];
        $params['singleGame'] = 1;
        $params['slotId'] = $game['slotId'];
        $params['lang'] = "PT";
        $params['userToken'] = $user['user_token'];
        $params['callbackUrl'] = $game['callbackPath'];
        $headers = [
            'Content-Type: application/json'
        ];
        write_log("获取游戏请求地址：".$this->baseUrl.$apiUrl,'Islot'.$user['cid']);
        write_log($params,'Islot'.$user['cid']);
        $str = $this->encrypt(json_encode($params));
        write_log($str,'Islot'.$user['cid']);

        $response = $this->request($apiUrl, $str, $headers);
        write_log($response,'Islot'.$user['cid']);
        if(isset($response['code']) && $response['code'] == 200){
            return ['code'=>0, 'msg'=>'获取成功','url'=>$response['data']['gameUrl']];
        }else{
            return ['code'=>$response['code'], 'msg'=>$response['message']];
        }
    }
    // 其它对应的API方法...
    public function balanceUser($user)
    {
        // TODO: Implement balanceUser() method.
        $apiUrl = '/api/v2/getBalance?agent='.$this->operatorToken;
        $params['timestamp'] = round(microtime(true) * 1000);
        $params['userName'] = $user['cid'].'_'.$user['uid'];
        $params['currency'] = "USD";
        write_log($params,'Islot'.$user['cid']);
        $headers = [
            'Content-Type: application/json'
        ];
        write_log("余额查询请求地址：".$this->baseUrl.$apiUrl,'Islot'.$user['cid']);
        $str = $this->encrypt(json_encode($params));
        $response = $this->request($apiUrl, $str, $headers);
        write_log($response,'Islot'.$user['cid']);
        if(isset($response['code']) && $response['code'] == 200){
            $money = 0;
            foreach($response['data']['userBalanceList'] as $key=>$val){
                if($val['currency'] == 'USD'){
                    $money = round($val['balance'] * 5,2);
                }
            }
            $user['money'] = $money;
            if($money > 0){
                return $this->depositUser($user,'OUT');
            }else{
                return ['code'=>0, 'msg'=>'获取成功','transNo'=>'','money'=>0];
            }
        }else{
            return ['code'=>$response['code'], 'msg'=>$response['message']];
        }
    }

    public function withdrawUser($user, $amount)
    {
        // TODO: Implement withdrawUser() method.
    }
    //预转账
    public function depositUser($user,$action="IN")
    {
        // TODO: Implement depositUser() method.
        $apiUrl = '/api/v1/prepareTransferCredit?agent='.$this->operatorToken;
        $params['timestamp'] = round(microtime(true) * 1000);
        $params['userName'] = $user['cid'].'_'.$user['uid'];
        $params['currency'] = "USD";
        $params['transNo'] = getSn("UP");
        $params['ip'] = get_real_ip__();
        $params['action'] = $action;
        $params['credit'] = round($user['money']/5,2);
        write_log($user['money'],'Islot'.$user['cid']);
        write_log($params,'Islot'.$user['cid']);
        $headers = [
            'Content-Type: application/json'
        ];
        write_log("预转账请求地址：".$this->baseUrl.$apiUrl,'Islot'.$user['cid']);
        $str = $this->encrypt(json_encode($params));
        $response = $this->request($apiUrl, $str, $headers);
        write_log($response,'Islot'.$user['cid']);
        if(isset($response['code']) && $response['code'] == 200){
            $res = $this->confirm_depositUser($user,$params['transNo'],$params['credit'],$action);
            write_log($res,'Islot'.$user['cid']);
            return $res;
        }else{
            return ['code'=>$response['code'], 'msg'=>$response['message']];
        }
    }
    //预转账确认
    public function confirm_depositUser($user,$order_no,$money,$action="IN"){
        $apiUrl = '/api/v1/confirmTransferCredit?agent='.$this->operatorToken;
        $params['timestamp'] = round(microtime(true) * 1000);
        $params['userName'] = $user['cid'].'_'.$user['uid'];
        $params['currency'] = "USD";
        $params['transNo'] = $order_no;
        $params['action'] = $action;
        $params['credit'] = $money;
        write_log($params,'Islot'.$user['cid']);
        $headers = [
            'Content-Type: application/json'
        ];
        write_log("确认预转账请求地址：".$this->baseUrl.$apiUrl,'Islot'.$user['cid']);
        $str = $this->encrypt(json_encode($params));
        $response = $this->request($apiUrl, $str, $headers);
        write_log($response,'Islot'.$user['cid']);
        if(isset($response['code']) && $response['code'] == 200){
            return ['code'=>0, 'msg'=>'获取成功','transNo'=>$order_no,'money'=>$money];
        }else{
            return ['code'=>$response['cod'], 'msg'=>$response['message']];
        }
    }
    public function get_order($start_time,$end_time){
        $apiUrl = '/api/v1/getOrders?agent=?agent='.$this->operatorToken;
        $params['timestamp'] = round(microtime(true) * 1000);
        $params['beginDate'] = date('Y-m-d H:i:s',$start_time);
        $params['endDate'] = date('Y-m-d H:i:s',$end_time);
        $params['page'] = 1;
        $params['num'] = 1000000;
        write_log($params,'Islot');
        $headers = [
            'Content-Type: application/json'
        ];
        write_log("获取注单记录：".$this->baseUrl.$apiUrl,'Islot');
        $str = $this->encrypt(json_encode($params));
        $response = $this->request($apiUrl, $str, $headers);
        write_log($response,'Islot');
        if(isset($response['code']) && $response['code'] == 200){
            return ['code'=>0, 'msg'=>'获取成功','data'=>$response['data']['ordersList']];
        }else{
            return ['code'=>$response['cod'], 'msg'=>$response['message']];
        }
    }
    /**
     * @param string $authPwd
     * @param string $text
     * @return string
     * @description: <使用MD5密文作为AES密钥加密字符串>
     */
    public function encrypt($text)
    {
        $md5Key = md5($this->secretKey);
        $key = self::parseHexStr2Byte($md5Key);
        $cipherByte = openssl_encrypt($text, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return self::parseByte2HexStr($cipherByte);
    }

    /**
     * @param string $authPwd
     * @param string $cipherText
     * @return string
     * @description: <使用MD5密文作为AES密钥解密字符串>
     */
    public function decrypt($cipherText)
    {
        $md5Key = md5($this->secretKey);
        $key = self::parseHexStr2Byte($md5Key);
        $cipherByte = self::parseHexStr2Byte($cipherText);
        $decrypted = openssl_decrypt($cipherByte, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return $decrypted;
    }

    /**
     * @param string $byteArray
     * @return string
     * @description: <将byte数组转换成16进制字符串>
     */
    private static function parseByte2HexStr($byteArray)
    {
        return bin2hex($byteArray);
    }

    /**
     * @param string $hexStr
     * @return string
     * @description: <将16进制字符串转换成byte数组>
     */
    private static function parseHexStr2Byte($hexStr)
    {
        return hex2bin($hexStr);
    }
}