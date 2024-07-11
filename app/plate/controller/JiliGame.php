<?php
namespace app\plate\controller;

use app\BaseController;
use think\App;
use think\facade\Db;

class JiliGame extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $action = strtolower(request()->controller() . '.' . request()->action());
        $this->logRequest($action);
    }

    private function logRequest($action)
    {
        write_log("======接口地址=======\n", 'JiliGame');
        write_log($action, 'JiliGame');
        write_log("======接口参数=======\n", 'JiliGame');
        $post = $this->request->post();
        write_log($post, 'JiliGame');
    }

    public function get_balance()
    {
        $params = $this->validateParams(['player_id', 'currency', 'timestamp', 'sign']);
        if ($params === false) return $this->error("参数错误");

        $player_id = $params['player_id'];
        list($cid, $uid) = explode('_', $player_id);

        $plate = app('app\common\model\Plate')->where("code", 'JiliGame')->find();
        if(empty($plate)) return $this->error("平台不存在");

        $game_user = model('app\common\model\GameUser',$cid)->where('pid',"=",$plate['id'])->where("player_id", $player_id)->find();
        if(empty($game_user)) return $this->error("用户不存在");

        $line = app('app\common\model\Line')->where("lid","=",$game_user['lid'])->find();
        if(empty($line)) return $this->error("线路不存在");

        if($this->validateSign($params,$line['app_id'],$line['app_secret']) === false) return $this->error("签名错误");

        $user = $this->getUser($cid, $uid);
        if (empty($user)) return $this->error("用户不存在");
        return $this->success($user['money']);
    }

    public function trans_in_out()
    {
        $params = $this->validateParams(['bet_amount', 'win_amount', 'net_amount', 'player_id', 'currency', 'transactionid', 'gid','timestamp','sign']);
        if ($params === false) return $this->error("参数错误");
        $player_id = $params['player_id'];
        list($cid, $uid) = explode('_', $player_id);

        $plate = app('app\common\model\Plate')->where("code", 'JiliGame')->find();
        if(empty($plate)) return $this->error("平台不存在");

        $game_user = model('app\common\model\GameUser',$cid)->where('pid',"=",$plate['id'])->where("player_id", $player_id)->find();
        if(empty($game_user)) return $this->error("用户不存在");

        $line = app('app\common\model\Line')->where("lid","=",$game_user['lid'])->find();
        if(empty($line)) return $this->error("线路不存在");

        if($this->validateSign1($params,$line['app_id'],$line['app_secret']) === false) return $this->error("签名错误");

        $game = app('app\common\model\Game')->where("code", $params['gid'])->where("pid","=",$game_user['pid'])->find();
        if (empty($game)) return $this->error("游戏不存在");

        $user = $this->getUser($cid, $uid);
        if (empty($user)) return $this->error("用户不存在");
        if($user['money'] < $params['bet_amount']) return $this->error("余额不足",3202);
        return $this->processTransaction($user, $cid, $uid, $game, $params['net_amount'], $params['transactionid'], $params['bet_amount'], $params['win_amount']);
    }

    private function validateParams($requiredParams)
    {
        $params = [];
        foreach ($requiredParams as $param) {
            $value = input($param, "");
            if ($value === "") return false;
            $params[$param] = $value;
        }
        return $params;
    }

    private function validateSign($params, $app_id, $app_secret)
    {
        $str = md5($app_id . $params['player_id'] . $params['timestamp'] . $app_secret);
        return $str === $params['sign'];
    }

    private function validateSign1($params, $app_id, $app_secret)
    {
        unset($params['currency']);
        $str = md5($app_id . $params['player_id'] . $params['transactionid'] . $params['net_amount'] . $params['timestamp'] . $app_secret);
        return $str === $params['sign'];
    }

    private function getUser($cid, $uid)
    {
        $UserModel = app('app\common\model\User');
        $UserModel->setPartition($cid);
        return $UserModel->getInfo($uid);
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
            $BillModel = app('app\common\model\Bill');
            $bid = 0;
            if($UpdateCredit > 0 || $UpdateCredit < 0){
                $row = $BillModel->addIntvie($user, $BillModel::GAME_BET, $UpdateCredit);
                $user = $row['user'];
                $bid = $row['bid'];
            }
            $GameLog = app('app\common\model\GameLog');
            $GameLog->add($cid, $uid, $user['mobile'],$bid, $game['pid'],$game['gid'],$game['name'], $UpdateCredit, $game['code'], $Term, $Bet, $Award);
            if($user['water'] > 0){
                $UserModel = model('app\common\model\User',$cid);
                if($Bet > $user['water']){
                    $UserModel->decWater($uid,$user['water']);
                }else{
                    $UserModel->decWater($uid,$Bet);
                }
            }
            //数据统计
            $UserStatModel = model('app\common\model\UserStat',$cid);
            $stat = ['bet_money' => $Bet, 'win_money' => $UpdateCredit];
            $UserStatModel->add($user,$stat);
            Db::commit();
            return $this->success($user['money']);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }

    protected function error($msg = '',$code=500)
    {
        write_log($msg, 'JiliGame');
        return json([
            'success' => '0',
            'balance' => '0',
            'currency' => 'BRL',
            'ts' => time(),
            'err_code' => $code,
        ]);
    }

    protected function success($money = '', $code = 1)
    {
        write_log("====余额====", 'JiliGame');
        write_log($money, 'JiliGame');
        return json([
            'success' => '1',
            'balance' => $money,
            'currency' => 'BRL',
            'ts' => time()
        ]);
    }
}