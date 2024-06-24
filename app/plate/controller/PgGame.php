<?php
namespace app\plate\controller;

use app\BaseController;
use think\App;
use think\facade\Db;

class PgGame extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $action = strtolower(request()->controller() . '.' . request()->action());
        $this->logRequest($action);
    }

    private function logRequest($action)
    {
        write_log("======接口地址=======\n", 'PgGame');
        write_log($action, 'PgGame');
        write_log("======接口参数=======\n", 'PgGame');
        $post = $this->request->post();
        write_log($post, 'PgGame');
    }

    public function get_balance()
    {
        $params = $this->validateParams(['OperatorToken', 'UseID', 'GameID', 'SecretStr']);
        if ($params === false) return $this->error("参数错误");

        list($OperatorToken, $uid, $gid, $SecretStr) = $params;

        $plate = app("app\common\model\Plate")->where("app_id", $OperatorToken)->find();
        if (!$this->validatePlate($plate, $OperatorToken, $SecretStr)) return $this->error("平台或密钥不正确");

        list($cid, $uid) = explode('_', $uid);
        $user = $this->getUser($cid, $uid);
        if (empty($user)) return $this->error("用户不存在");

        return $this->success($user['money']);
    }

    public function trans_in_out()
    {
        $params = $this->validateParams(['OperatorToken', 'UseID', 'GameID', 'SecretStr', 'UpdateCredit', 'Term', 'Bet', 'Award']);
        if ($params === false) return $this->error("参数错误");

        list($OperatorToken, $uid, $game_id, $SecretStr, $UpdateCredit, $Term, $Bet, $Award) = $params;

        $plate = app("app\common\model\Plate")->where("app_id", $OperatorToken)->find();
        if (!$this->validatePlate($plate, $OperatorToken, $SecretStr)) return $this->error("平台或密钥不正确");

        $game = app("app\common\model\Game")->where("code", $game_id)->find();
        if (empty($game)) return $this->error("游戏不存在");

        list($cid, $uid) = explode('_', $uid);
        $user = $this->getUser($cid, $uid);
        if (empty($user)) return $this->error("用户不存在");

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
            $GameLog->add($cid, $uid, $user['mobile'],$bid, $game['gid'],$game['name'], $UpdateCredit, $game['code'], $Term, $Bet, $Award);
            if($user['water'] > 0){
                $UserModel = model('app\common\model\User',$cid);
                $UserModel->decWater($uid,$Bet);
            }
            //数据统计
            $UserStatModel = model('app\common\model\UserStat',$cid);
            $stat = [
                'uid'       => $user['uid'],
                'cid'       => $user['cid'],
                'mobile'    => $user['mobile'],
                'bet_money' => $Bet,
                'win_money' => $UpdateCredit
            ];
            $UserStatModel->add($stat);
            Db::commit();
            return $this->success($user['money']);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }

    protected function error($msg = '')
    {
        write_log($msg, 'PgGame');
        return json([
            'data' => $msg,
            'error' => 3202
        ]);
    }

    protected function success($money = '', $code = 1)
    {
        write_log("====余额====", 'PgGame');
        write_log($money, 'PgGame');
        $milliseconds = round(microtime(true) * 1000);
        return json([
            'data' => [
                'currency_code' => 'BRL',
                'balance_amount' => $money * 1000,
                'updated_time' => $milliseconds,
                'error' => 'None',
            ],
            'error' => 0
        ]);
    }
}