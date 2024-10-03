<?php

namespace app\api\controller;
use app\BaseController;
use app\service\game\GamePlatformFactory;
use hg\apidoc\annotation as Apidoc;
use think\App;
use think\facade\Cache;
use think\facade\Db;

class V1 extends BaseController
{
    protected $cid = 0;
    protected $key = '';
    protected $secretKey = '';

    public function __construct(App $app)
    {
        parent::__construct($app);
        $plate = app('app\common\model\Plate')->where("code", 'IslotGame')->find();
        if(empty($plate)) return $this->error("平台不存在");
        $this->cid = $plate['id'];
        $line = app('app\common\model\Line')
            ->where('pid', "=", $plate['id'])
            ->where('is_rebot', '=', 0)
            ->order('lid desc')
            ->find();
        if(empty($line)) return $this->error("线路不存在");
        $this->secretKey = $line['app_secret'];
    }


    public function userVerify(){
        $str = request()->getContent();
        write_log('用户鉴权', 'IslotGameBet'.$this->cid);
        write_log($str, 'IslotGameBet'.$this->cid);
        $data = $this->decrypt($str);
        write_log($data, 'IslotGameBet'.$this->cid);

        if(empty($data['ticket'])){
            return $this->error('ticket不能为空');
        }
        $user_info = Cache::store('redis')->get($data['ticket']);
        if(empty($user_info)){
            return $this->error('用户不存在');
        }
        return $this->success('');
    }

    //获取余额
    public function getBalance(){
        $str = request()->getContent();
        write_log('获取余额', 'IslotGameBet'.$this->cid);
        write_log($str, 'IslotGameBet'.$this->cid);
        $data = $this->decrypt($str);
        write_log($data, 'IslotGameBet'.$this->cid);


        if(empty($data['ticket'])){
            return $this->error('ticket不能为空',401);
        }
        $user_info = Cache::store('redis')->get($data['ticket']);
        if(empty($user_info)){
            return $this->error('用户不存在',500);
        }

        $user = $this->getUser($user_info['cid'], $user_info['uid']);
        if (empty($user)) return $this->error("用户不存在");
        return $this->success_balance($user);
    }

    public function orderPush(){
        $str = request()->getContent();
        write_log('订单推送', 'IslotGameBet'.$this->cid);
        write_log($str, 'IslotGameBet'.$this->cid);
        $data = $this->decrypt($str);
        write_log($data, 'IslotGameBet'.$this->cid);



        $requiredParams = [
            'requestNo', 'transNo', 'ticket', 'timestamp', 'betOrder', 'round',
            'betStatus', 'betTime', 'settlementTime', 'betType', 'mcid', 'betMoney',
            'customerWin', 'mpptw', 'mppw', 'mpebw', 'apptw', 'appw', 'apebw', 'beforeBetMoney',
            'afterBetMoney', 'junketsChipBetAmount',
        ];
        // 验证参数
        $params = $this->validateParams($requiredParams,$data);
        if ($params === false) {
            // 如果验证失败，返回错误信息
            return $this->error('缺少参数');
        }
        $user_info = Cache::store('redis')->get($data['ticket']);
        if(empty($user_info)){
            return $this->error('用户不存在',500);
        }
        $plate = app('app\common\model\Plate')->where("code", 'IslotGame')->find();
        if(empty($plate)) return $this->error("平台不存在");

        $game_user = model('app\common\model\GameUser',$user_info['cid'])->where('pid',"=",$plate['id'])->where("uid", '=',$user_info['uid'])->find();
        if(empty($game_user)) return $this->error("用户不存在");

        $line = app('app\common\model\Line')->where("lid","=",$game_user['lid'])->find();
        if(empty($line)) return $this->error("线路不存在");

        $game = app('app\common\model\Game')
            ->where("code", $data['gameName'])
            ->where("pid","=",$plate['id'])->find();
        if (empty($game)) return $this->error("游戏不存在");
        $user = $this->getUser($user_info['cid'], $user_info['uid']);
        if (empty($user)) return $this->error("用户不存在");
        //if($user['money'] < ($data['betMoney']*5)) return $this->error("余额不足",3202);
        if($data['betStatus'] == 2){
            $betMoney = $data['betMoney']*5;
            $winMoney = $data['customerWin']*5;
            $award = $betMoney+$winMoney;
            return $this->processTransaction($user, $user_info['cid'], $user_info['uid'], $game, $winMoney, $data['betOrder'], $betMoney, $award);
        }
        return $this->success("");
    }

    /**
     * @param $user         array    用户信息
     * @param $cid          int      渠道ID
     * @param $uid          int      用户ID
     * @param $game         array    游戏信息
     * @param $UpdateCredit float    输赢
     * @param $Term         string   游戏订单号
     * @param $Bet          float    本局下注额
     * @param $Award        float    本局得分
     */
    private function processTransaction($user, $cid, $uid, $game, $UpdateCredit, $Term, $Bet, $Award)
    {
        Db::startTrans();
        try {
            /*$BillModel = app('app\common\model\Bill');
            $bid = 0;
            if($UpdateCredit > 0 || $UpdateCredit < 0){
                $row = $BillModel->addIntvie($user, $BillModel::GAME_BET, $UpdateCredit,0,0,$Bet);
                $user = $row['user'];
                $bid = $row['bid'];
            }*/
            $GameLog = app('app\common\model\GameLog');
            $GameLog->add($cid, $uid, $user['user'],0, $game['pid'],$game['gid'],$game['name'], $UpdateCredit, $game['code'], $Term, $Bet, $Award);
            //数据统计
            $UserStatModel = model('app\common\model\UserStat',$cid);
            $stat = ['bet_money' => $Bet, 'win_money' => $UpdateCredit];
            $UserStatModel->add($user,$stat);
            Db::commit();
            return $this->success('');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage(),500);
        }
    }

    public function transfer(){
        $str = request()->getContent();
        write_log('用户余额变更', 'IslotGameBet'.$this->cid);
        write_log($str, 'IslotGameBet'.$this->cid);
        $data = $this->decrypt($str);
        write_log($data, 'IslotGameBet'.$this->cid);



        if(empty($data['ticket'])){
            return $this->error('ticket不能为空',401);
        }
        $user_info = Cache::store('redis')->get($data['ticket']);
        if(empty($user_info)){
            return $this->error('用户不存在',500);
        }

        $user = $this->getUser($user_info['cid'], $user_info['uid']);
        if (empty($user)) return $this->error("用户不存在");
        $money = $user['money'];
        $amount = $data['amount'] * 5;
        Db::startTrans();
        try {
            $BillModel = app('app\common\model\Bill');
            $row = $BillModel->addIntvie($user, $BillModel::ISLOT_MONEY, $amount,0,0,0,$data['transAction']);
            $user = $row['user'];
            $bid = $row['bid'];
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage(),500);
        }
        return $this->success_balance($user,$money,$user['money'],$bid);
    }

    public function confirmTransfer(){
        return $this->success('');
    }
    public function transNoEvent(){
        return $this->success('');
    }

    private function validateParams($requiredParams,$data)
    {
        $params = [];
        foreach ($requiredParams as $param) {
            $value = $data[$param] ?? '';
            if ($value === "") {
                return false; // 如果有任何参数为空，返回 false
            }
            $params[$param] = $value; // 将参数和值添加到数组中
        }
        return $params; // 如果所有参数都不为空，返回参数数组
    }


    private function getUser($cid, $uid)
    {
        $UserModel = app('app\common\model\User');
        $UserModel->setPartition($cid);
        return $UserModel->getInfo($uid);
    }

    private function user_blance($user,$money,$bid)
    {
        try {
            $BillModel = app('app\common\model\Bill');
            $BillModel->addIntvie($user, $BillModel::GAME_WITHDRAW, $money);
            $GameWallet = model('app\common\model\GameWallet',$this->cid);
            $GameWallet->edit($bid,['status'=>1]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            write_log($e->getMessage(), 'IslotGame'.$this->cid);
        }
    }
    public function get_bet(){
        $start_time = Cache::get('IslotGameBet');
        if(empty($start_time)){
            $start_time = time();
        }
        $end_time = $start_time + 10*60;

        $plate = app('app\common\model\Plate')->where("code", 'IslotGame')->find();
        if(empty($plate)) return error("平台不存在");
        $line = app('app\common\model\Line')->find();
        $this->platformService = GamePlatformFactory::getPlatformService($plate, $line, []);
        $row = $this->platformService->get_order($start_time,$end_time);
        $data = [];
        $game = app('app\common\model\Game')->where("pid","=",$plate['id'])->select();
        $gameList = [];
        foreach($game as $val){
            $gameList[$val['name']] = $val;
        }

        if($row['code'] == 0){
            foreach ($row['data'] as $key => $value) {
                list($cid, $uid) = explode('_', $value['customerLoginName']);
                $UserModel = model('app\common\model\User',$cid);
                $mobile = $UserModel->getMobile($uid);
                $data[] = [
                    'cid' => $cid,
                    'uid' => $uid,
                    'mobile' => $mobile,
                    'pid' => $plate['id'],
                    'gid' => $gameList[$value['gameName']]['gid'],
                    'name' => $value['gameName'],
                    'win_lose' => $value['customerWin'] * 5,
                    'game_id' => 0,
                    'term' => $value['betOrder'],
                    'bet' => $value['betMoney'] * 5,
                    'award' =>  $value['customerWin'] > 0 ? ($value['customerWin'] + $value['betMoney']) * 5 : 0,
                    'add_time' => strtotime($value['betTime'])
                ];
            }
            if($data){
                Db::name('game_log')->insertAll($data);
            }
            Cache::set('IslotGameBet',$end_time);
        }else{
            echo $row['msg'];
        }
        echo "完成";
    }

    protected function error($msg = 'ok',$code=500)
    {
        write_log($msg, 'IslotGameBet'.$this->cid);
        return json([
            'code' => $code,
            'message' => $msg,
            'data' => [
                'success' => false
            ],
            'success' => false
        ]);
    }

    protected function success($money = '', $code = 200)
    {
        write_log('成功：'.$money, 'IslotGameBet'.$this->cid);
        return json([
            'code' => 200,
            'message' => 'ok',
            'data' => [
                'success' => true
            ],
            'success' => true
        ]);
    }
    protected function success_balance($user, $afterAmount=0,$beforeAmount=0,$bid = 0,$code = 200)
    {
        write_log('成功：'.$user['money'], 'IslotGameBet'.$this->cid);
        $money = round($user['money']/5,2);
        $afterAmount = round($afterAmount/5,2);
        $beforeAmount = round($beforeAmount/5,2);
        $data = [
            'code' => 200,
            'message' => 'ok',
            'data' => [
                'nickName' => $user['cid'].'_'.$user['user'],
                'amount' => $money,
                'currency' => 'USD',
                'userBalanceList' => [
                    [
                        'amount' => $money,
                        'currency' => 'USD',
                        'rate' => "1"
                    ]
                ]
            ],
            'success' => true
        ];
        if($afterAmount > 0) {$data['data']['afterAmount'] = $afterAmount;}
        if($beforeAmount > 0){$data['data']['beforeAmount'] = $beforeAmount;}
        if($bid > 0){$data['data']['transNo'] = $bid;}
        return json($data);
    }

    public function encrypt($text)
    {
        $md5Key = md5($this->secretKey);
        $key = self::parseHexStr2Byte($md5Key);
        $cipherByte = openssl_encrypt($text, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return self::parseByte2HexStr($cipherByte);
    }

    public function decrypt($cipherText)
    {
        $md5Key = md5($this->secretKey);
        $key = self::parseHexStr2Byte($md5Key);
        $cipherByte = self::parseHexStr2Byte($cipherText);
        $decrypted = openssl_decrypt($cipherByte, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return  json_decode($decrypted,true);
    }

    private static function parseByte2HexStr($byteArray)
    {
        return bin2hex($byteArray);
    }

    private static function parseHexStr2Byte($hexStr)
    {
        return hex2bin($hexStr);
    }
}
