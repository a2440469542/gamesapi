<?php
namespace app\plate\controller;

use app\BaseController;
use think\App;
use think\facade\Cache;
use think\facade\Db;

class PpGame extends BaseController
{
    protected $cid = 0;
    protected $key = '';
    public function __construct(App $app)
    {
        parent::__construct($app);
        $action = strtolower(request()->controller() . '.' . request()->action());
        $this->logRequest($action);
    }

    private function logRequest($action)
    {
        $time = microtime(true);
        $post = $this->request->post();
        list($cid, $uid) = explode('_', $post['uname']);
        $this->cid = $cid;
        $this->key = md5(uniqid(rand(), true));
        write_log($this->key."开始时间".$time, 'PpGame'.$cid);
        write_log($this->key."======接口地址=======\n", 'PpGame'.$cid);
        write_log($this->key.$action, 'PpGame'.$cid);
        write_log($this->key."======接口参数=======\n", 'PpGame'.$cid);

        write_log($post, 'PpGame'.$cid);
    }


    public function verify_user(){
        $params = $this->validateParams(['uname', 'token']);
        if ($params === false) return $this->error("参数错误");
        list($uname, $token) = $params;
        $user = Cache::store('redis')->get($token);
        if(empty($user)) return $this->error("玩家token验证错误",2001);
        $user = $this->getUser($user['cid'], $user['uid']);
        if (empty($user)) return $this->error("用户不存在",2000);
        return $this->success($user['money'],$uname);
    }


    public function get_balance()
    {
        $params = $this->validateParams(['uname', 'gameid']);

        list($UseID, $gid) = $params;
        list($cid, $uid) = explode('_', $UseID);
        $user = $this->getUser($cid, $uid);
        if (empty($user)) return $this->error("用户不存在",2000);

        return $this->success($user['money'],$uid);
    }

    public function trans_in_out()
    {
        $params = $this->validateParams(['uname', 'token', 'betid', 'sessionid', 'gameid', 'bet', 'award', 'is_end_round','ctime']);
        if ($params === false) return $this->error("参数错误");

        list($UseID, $token,$betid,$sessionid,$game_id, $bet, $award, $is_end_round, $ctime) = $params;
        list($cid, $uid) = explode('_', $UseID);

        $plate = app('app\common\model\Plate')->where("code", 'PpGame')->find();
        if(empty($plate)) return $this->error("平台不存在",3000);

        $game_user = model('app\common\model\GameUser',$cid)->where('pid',"=",$plate['id'])->where("player_id", $UseID)->find();
        if(empty($game_user)) return $this->error("用户不存在",2000);

        $game = app("app\common\model\Game")->where("code", $game_id)->where("pid","=",$plate['id'])->find();
        if (empty($game)) return $this->error("游戏不存在",3000);

        $user = $this->getUser($cid, $uid);
        if (empty($user)) return $this->error("用户不存在",2000);
        if($user['money'] < $bet) return $this->error("余额不足",2010);
        $win_lose = ($award*100 - $bet*100)/100;
        return $this->processTransaction($user, $cid, $uid, $game, $win_lose, $betid, $bet, $award);
    }

    private function validateParams($requiredParams)
    {
        $params = [];
        foreach ($requiredParams as $param) {
            $value = input($param, "");
            if ($value === "") return false;
            $params[] = $value;
        }
        return $params;
    }

    private function validatePlate($plate, $OperatorToken, $SecretStr)
    {
        return !empty($plate) || $plate['app_id'] == $OperatorToken || $plate['app_secret'] == $SecretStr;
    }

    private function getUser($cid, $uid)
    {
        $UserModel = app('app\common\model\User');
        $UserModel->setPartition($cid);
        return $UserModel->getInfo($uid);
    }

    private function processTransaction($user, $cid, $uid, $game, $UpdateCredit, $Term, $Bet, $Award)
    {
        Db::startTrans();
        try {
            $BillModel = app('app\common\model\Bill');
            $bid = 0;
            if($UpdateCredit > 0 || $UpdateCredit < 0){
                $row = $BillModel->addIntvie($user, $BillModel::GAME_BET, $UpdateCredit,0,0,$Bet);
                $user = $row['user'];
                $bid = $row['bid'];
            }
            $GameLog = app('app\common\model\GameLog');
            $GameLog->add($cid, $uid, $user['mobile'],$bid, $game['pid'], $game['gid'], $game['name'], $UpdateCredit, $game['code'], $Term, $Bet, $Award);
            //数据统计
            $UserStatModel = model('app\common\model\UserStat',$cid);
            $stat = ['bet_money' => $Bet, 'win_money' => $UpdateCredit];
            $UserStatModel->add($user,$stat);
            Db::commit();
            return $this->success($user['money'], $cid.'_'.$uid,$Term);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage(),2021);
        }
    }

    protected function error($msg = '', $code = 3000)
    {
        write_log($this->key.$msg, 'PpGame'.$this->cid);
        $time = microtime(true);
        write_log($this->key."结束时间".$time, 'PpGame'.$this->cid);
        return json([
            'code' => $code,
            'msg' => $msg,
        ]);
    }

    protected function success($money = '',$uname='',$betid='', $code = 1)
    {
        write_log($this->key."====余额====", 'PpGame'.$this->cid);
        write_log($this->key."====".$money, 'PpGame'.$this->cid);
        $time = microtime(true);
        write_log($this->key."结束时间".$time, 'PpGame'.$this->cid);
        return json([
            'data' => [
                'uname' => $uname,
                'balance' => $money,
                'betid' => $betid
            ],
            'code' => 0,
            'msg' => '',
        ]);
    }
}