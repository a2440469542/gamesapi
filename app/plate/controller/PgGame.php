<?php
namespace app\plate\controller;

use app\BaseController;
use think\App;
use think\facade\Db;

class PgGame extends BaseController
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
        list($cid, $uid) = explode('_', $post['UseID']);
        $this->cid = $cid;
        $this->key = md5(uniqid(rand(), true));
        write_log($this->key."开始时间".$time, 'PgGame'.$cid);
        write_log($this->key."======接口地址=======\n", 'PgGame'.$cid);
        write_log($this->key.$action, 'PgGame'.$cid);
        write_log($this->key."======接口参数=======\n", 'PgGame'.$cid);

        write_log($post, 'PgGame'.$cid);
    }

    public function get_balance()
    {
        $params = $this->validateParams(['OperatorToken', 'UseID', 'GameID', 'SecretStr']);

        list($OperatorToken, $UseID, $gid, $SecretStr) = $params;
        list($cid, $uid) = explode('_', $UseID);
        $user = $this->getUser($cid, $uid);
        if (empty($user)) return $this->error("用户不存在");

        return $this->success($user['money']);
    }

    public function trans_in_out()
    {
        $params = $this->validateParams(['OperatorToken', 'UseID', 'GameID', 'SecretStr', 'UpdateCredit', 'Term', 'Bet', 'Award']);
        if ($params === false) return $this->error("参数错误");

        list($OperatorToken, $UseID, $game_id, $SecretStr, $UpdateCredit, $Term, $Bet, $Award) = $params;
        list($cid, $uid) = explode('_', $UseID);

        $plate = app('app\common\model\Plate')->where("code", 'PgGame')->find();
        if(empty($plate)) return $this->error("平台不存在");

        $game_user = model('app\common\model\GameUser',$cid)->where('pid',"=",$plate['id'])->where("player_id", $UseID)->find();
        if(empty($game_user)) return $this->error("用户不存在");

        $line = app('app\common\model\Line')->where("lid","=",$game_user['lid'])->find();
        if (!$this->validatePlate($line, $OperatorToken, $SecretStr)) return $this->error("平台或密钥不正确");

        $game = app("app\common\model\Game")->where("code", $game_id)->where("pid","=",$plate['id'])->find();
        if (empty($game)) return $this->error("游戏不存在");

        $user = $this->getUser($cid, $uid);
        if (empty($user)) return $this->error("用户不存在");
        if($user['money'] < $Bet / 1000) return $this->error("余额不足");
        return $this->processTransaction($user, $cid, $uid, $game, $UpdateCredit, $Term, $Bet, $Award);
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
            $UpdateCredit = round($UpdateCredit / 1000, 2);
            $Bet = round($Bet / 1000, 2);
            $Award = round($Award / 1000, 2);

            $BillModel = app('app\common\model\Bill');
            $bid = 0;
            if($UpdateCredit > 0 || $UpdateCredit < 0){
                $row = $BillModel->addIntvie($user, $BillModel::GAME_BET, $UpdateCredit);
                $user = $row['user'];
                $bid = $row['bid'];
            }
            $GameLog = app('app\common\model\GameLog');
            $GameLog->add($cid, $uid, $user['mobile'],$bid, $game['pid'], $game['gid'], $game['name'], $UpdateCredit, $game['code'], $Term, $Bet, $Award);
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

    protected function error($msg = '')
    {
        write_log($this->key.$msg, 'PgGame'.$this->cid);
        $time = microtime(true);
        write_log($this->key."结束时间".$time, 'PgGame'.$this->cid);
        return json([
            'data' => $msg,
            'error' => 3202
        ]);
    }

    protected function success($money = '', $code = 1)
    {
        write_log($this->key."====余额====", 'PgGame'.$this->cid);
        write_log($this->key."====".$money, 'PgGame'.$this->cid);
        $time = microtime(true);
        write_log($this->key."结束时间".$time, 'PgGame'.$this->cid);
        $milliseconds = round(microtime(true) * 1000);
        return json([
            'data' => [
                'currency_code' => 'BRL',
                'balance_amount' => round($money * 1000),
                'updated_time' => $milliseconds,
            ],
            'error' => null
        ]);
    }
}