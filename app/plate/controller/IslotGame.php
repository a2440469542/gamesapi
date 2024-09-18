<?php

namespace app\plate\controller;

use app\BaseController;
use app\service\game\GamePlatformFactory;
use think\App;
use think\facade\Db;
use think\facade\Cache;
class IslotGame extends BaseController
{
    protected $cid = 0;
    protected $key = '';

    public function game_out()
    {
        $post = $this->request->post();
        $userName = $post['userName'];
        list($cid, $uid) = explode('_', $userName);
        $plate = app('app\common\model\Plate')->where("code", 'IslotGame')->find();
        write_log("平台不存在", 'IslotGame'.$this->cid);
        if(empty($plate)) return error("平台不存在");
        $UserModel = app('app\common\model\User');
        $UserModel->setPartition($cid);
        $user = $UserModel->getInfo($uid);
        $game_wallet = model('app\common\model\GameWallet',$cid)
            ->where("cid","=",$cid)
            ->where('uid',"=",$uid)
            ->where('pid',"=",$plate['id'])
            ->where("status","=",0)
            ->find();

        if(empty($game_wallet)){
            write_log("上分记录不存在", 'IslotGame'.$this->cid);
            return error("上分记录不存在");
        }
        if($game_wallet['up_time'] > 0){
            $this->user_blance($user,$game_wallet['withdraw'],$game_wallet['id']);
        }

        $line = app('app\common\model\Line')
            ->where("lid","=",$game_wallet['lid'])
            ->find();
        $this->platformService = GamePlatformFactory::getPlatformService($plate, $line, $user);
        $GameWallet = model('app\common\model\GameWallet',$this->cid);
        $row = $this->platformService->balanceUser($user);
        if($row['code'] == 0){
            $data = [
                'withdraw' => $row['money'],
                'worder_sn' => $row['transNo'],
                'up_time' => time(),
            ];
            $GameWallet->edit($game_wallet['id'],$data);
            $this->user_blance($user,$row['money'],$game_wallet['id']);
            return true;
        }else{
            return false;
        }
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
}