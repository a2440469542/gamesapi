<?php
namespace app\api\controller;

use hg\apidoc\annotation as Apidoc;
use think\App;
use think\facade\Db;
use think\facade\Request;
use app\service\game\GamePlatformFactory;

/**
 * 调用三方游戏登录相关接口
 * @Apidoc\Title("调用三方游戏登录相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(4)
 */
class GameLogin extends Base
{
    protected $platformService;
    protected $user;
    protected $game;
    protected $cid;
    protected $plate;
    protected $line;
    protected $GameWallet;
    protected function set_config()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $gid = Request::post('gid'); // 从前端获取游戏类型
        if (empty($gid)) {
            return error("Erro de parâmetro", 500);  //参数错误
        }
        $this->cid = $cid;
        $this->user = model('app\common\model\User',$cid)->getInfo($uid);
        $game = model('app\common\model\Game')->find($gid);
        $plate = app('app\common\model\Plate')->getInfo($game['pid']);
        $this->plate = $plate;
        if($this->user['is_rebot'] == 1){
            $line = app('app\common\model\Line')
                ->where('pid',"=",$game['pid'])
                ->where('is_rebot','=',1)->find();   //线路
        }else{
            $channel = model('app\common\model\Channel')->info($cid);
            $lid = $channel['plate_line'][$plate['id']];
            $line = app('app\common\model\Line')
                ->where("lid","=",$lid)
                ->find();
        }
        $this->line = $line;
        $this->game = $game->toArray();
        $platform = $plate['code'];
        //$this->GameWallet = model('app\common\model\GameWallet',$cid);
        $this->platformService = GamePlatformFactory::getPlatformService($platform, $line, $this->user);
        return true;
    }

    protected function registerUser($game_user)
    {
        $channel = model('app\common\model\Channel')->where("cid", $this->user['cid'])->find();
        $user = $this->user;
        $user['cname'] = $channel['name'];  // 渠道名称
        $row = $this->platformService->registerUser($user);
        if($row['code'] != 0){
            return $row;
        }
        $player_id = $row['player_id'];
        $username = $row['user'];
        $is_login = $row['is_login'];
        if(empty($game_user)){
            $GameUser = model('app\common\model\GameUser',$this->cid);
            $lid = $this->line['lid'];
            $uid = $this->user['uid'];
            $pid = $this->plate['id'];
            $rtp = $this->line['rtp'];
            $GameUser->add($this->cid,$uid,$pid,$lid,$username,$player_id,$is_login,$rtp);
        }
        return $row;
    }
    protected function getGameUser(){
        $GameUser = model('app\common\model\GameUser',$this->cid);
        $uid = $this->user['uid'];
        $pid = $this->plate['id'];
        $info = $GameUser->getInfo($uid,$pid);
        return $info;
    }
    //上分
    protected function up_score(){
        Db::startTrans();
        try {
            $balance = $this->user['balance'];
            $BillModel = model('app\common\model\Bill', $this->cid);
            $BillModel->addIntvie($this->user, $BillModel::GAME_DEPOSIT, -$balance);
            $row = $this->platformService->depositUser($this->user);
            if($row['code'] == 0){
                $dorder_sn = $row['dorder_sn'];
                $d_tx = $row['d_tx'];
                $pid = $this->plate['id'];
                $this->GameWallet->add($this->cid,$pid,$this->user['uid'],$this->user['mobile'],$this->user['inv_code'],$balance,$dorder_sn,$d_tx);
                Db::commit();
            }else{
                Db::rollback();
                return false;
            }
        }catch (\Exception $e) {
            Db::rollback();
            return false;
        }

    }
    //下分
    protected function down_score(){
        $info = $this->GameWallet->getInfo($this->user['uid'],$this->plate['id']);
        if($info){
            $row = $this->platformService->balanceUser($this->user);
            if($row['code'] == 0){
                $balance = $row['balance'];
                if($balance > 0){
                    Db::startTrans();
                    try {
                        $BillModel = model('app\common\model\Bill', $this->cid);
                        $BillModel->addIntvie($this->user, $BillModel::GAME_WITHDRAW, $balance);
                        $res = $this->platformService->withdrawUser($this->user,$balance);
                        if($res['code'] == 0){
                            $worder_sn = $res['worder_sn'];
                            $w_tx = $res['w_tx'];
                            $data =[
                                'worder_sn' => $worder_sn,
                                'w_tx' => $w_tx,
                                'withdraw' => $balance,
                                'up_time' => date("Y-m-d H:i:s"),
                                'status' => 1
                            ];
                            $this->GameWallet->edit($info['id'],$data);
                        }else{
                            Db::rollback();
                            return false;
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        return false;
                    }
                }
            }
        }
        return true;
    }
    protected function rtp_limit($game_user){
        $row = $this->platformService->set_rtp_limit($this->user,$this->line['rtp']);
        if($row['code'] != 0){
            return $row;
        }
        $GameUser = model('app\common\model\GameUser',$this->cid);
        $GameUser->edit($game_user['id'],['rtp'=>$this->line['rtp']]);
        return $row;
    }
    /**
     * @Apidoc\Title("获取游戏启动链接")
     * @Apidoc\Desc("获取游戏启动链接")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("获取游戏启动链接")
     * @Apidoc\Param("gid", type="int", require=true, desc="游戏ID")
     * @Apidoc\Returned("url", type="object", desc="游戏启动链接")
     */
    public function get_game_url()
    {
        $row = $this->set_config();
        if($row !== true) {
            return $row;
        }
        $game_user = $this->getGameUser();
        if(empty($game_user) || $game_user['is_login'] == 1){
            $token = $this->registerUser($game_user);
            if ($token['code'] != 0) {
                return error($token['msg'], 501);    // 游戏登录失败
            }
            $this->user['user_token'] = $token['token'] ?? '';
        }else{
            $this->user['user_token'] = md5(uniqid(md5(microtime(true)),true));
        }
        $plate = $this->plate;
        /*if($game_user && $game_user['rtp'] != $this->line['rtp']){
            $row = $this->rtp_limit($game_user);
            if($row['code'] != 0) error($row['msg'], 501);    // 游戏登录失败
        }*/
        //$this->down_score();
        /*if($plate['wallet_type'] == 2){
            $this->up_score();
        }*/
        $response = $this->platformService->getGameUrl($this->user,$this->game);

        if ($response['code'] != 0) {
            return error($response['msg'], 501);    // 游戏登录失败
        }

        return success("obter sucesso", $response); //获取成功
    }

}