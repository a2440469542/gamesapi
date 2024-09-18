<?php
namespace app\api\controller;

use hg\apidoc\annotation as Apidoc;
use think\App;
use think\facade\Db;
use think\facade\Request;
use app\service\game\GamePlatformFactory;

/**
 * 调用三方直播游戏登录相关接口
 * @Apidoc\Title("调用三方直播游戏登录相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(4)
 */
class LiveGameLogin extends Base
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
        $slotId = Request::post('slotId',''); // 直播游戏的ID
        $pid = Request::post('pid',''); // 平台ID
        if ((empty($pid) && empty($slotId))) {
            return error("Erro de parâmetro", 500);  //参数错误
        }
        $this->cid = $cid;
        $this->user = model('app\common\model\User',$cid)->getInfo($uid);

        $plate = app('app\common\model\Plate')->getInfo($pid);
        $game_slot = Db::name('game_slot')->where('slotId','=',$slotId)->find();
        if(empty($game_slot)){
            return ['code'=>500,'msg'=>'O jogo não existe'];
        }

        $game = model('app\common\model\Game')
            ->where("pid","=",$plate['id'])
            ->where("name","=",$game_slot['gameName'])
            ->find();

        $this->plate = $plate;
        $this->line = $this->getLineInfo();
        if(empty($this->line)) return ['code'=>500,'msg'=>'Jogo não configurado'];
        $this->game = $game->toArray();
        $this->game['slotId'] = $slotId;
        $platform = $plate['code'];
        $this->platformService = GamePlatformFactory::getPlatformService($platform, $this->line, $this->user);
        return ['code'=>0];
    }

    protected function registerUser($game_user)
    {
        $user = $this->user;
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
        $GameWallet = model('app\common\model\GameWallet',$this->cid);
        Db::startTrans();
        try {
            $balance = $this->user['balance'];
            $BillModel = model('app\common\model\Bill', $this->cid);
            $BillModel->addIntvie($this->user, $BillModel::GAME_DEPOSIT, -$balance);
            $row = $this->platformService->depositUser($this->user);
            if($row['code'] == 0){
                $dorder_sn = $row['transNo'];
                $d_tx = $row['d_tx'];
                $pid = $this->plate['id'];
                $lid = $this->line['lid'];
                $GameWallet->add($this->cid,$pid,$lid,$this->user['uid'],$this->user['mobile'],$this->user['inv_code'],$balance,$dorder_sn,$d_tx);
                Db::commit();
            }else{
                Db::rollback();
                return $row;
            }
        }catch (\Exception $e) {
            Db::rollback();
            return ['code'=>502,'msg'=>$e->getMessage()];
        }
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
        if($row['code'] > 0) {
            return error($row['msg']);
        }
        if(round($this->user['money']/5,2) < 1){
            return error('Desculpe, seu crédito está diminuindo'); //余额不足
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
        $response = $this->platformService->getGameUrl($this->user,$this->game);
        if ($response['code'] != 0) {
            return error($response['msg'], 501);    // 游戏登录失败
        }
        $response = $this->up_score();
        if ($response['code'] != 0) {
            return error($response['msg'], 501);    // 游戏登录失败
        }
        return success("obter sucesso", $response); //获取成功
    }
    protected function getLineInfo()
    {
        if ($this->user['is_rebot'] == 1) {
            return app('app\common\model\Line')
                ->where('pid', "=", $this->plate['id'])
                ->where('is_rebot', '=', 1)
                ->find();
        }

        $channel = model('app\common\model\Channel')->info($this->cid);
        if (isset($channel['plate_line'][$this->plate['id']])) {
            $lid = $channel['plate_line'][$this->plate['id']];
            return app('app\common\model\Line')
                ->where("lid", "=", $lid)
                ->find();
        }

        return app('app\common\model\Line')
            ->where('pid', "=", $this->plate['id'])
            ->where('is_rebot', '=', 0)
            ->order('lid desc')
            ->find();
    }
    /**
     * @Apidoc\Title("退出游戏")
     * @Apidoc\Desc("退出游戏")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("退出游戏")
     * @Apidoc\Param("pid", type="int", require=true, desc="平台ID")
     */
    public function out_game(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $pid = Request::post('pid',''); // 平台ID
        if (empty($pid)) {
            return error("Erro de parâmetro", 500);  //参数错误
        }
        $this->cid = $cid;
        $this->user = model('app\common\model\User',$cid)->getInfo($uid);
        $plate = app('app\common\model\Plate')->getInfo($pid);
        $game_wallet = model('app\common\model\GameWallet',$cid)
            ->where("cid","=",$cid)
            ->where('uid',"=",$uid)
            ->where('pid',"=",$plate['id'])
            ->where("status","=",0)
            ->find();
        if(empty($game_wallet)){
            return success("Sair com sucesso"); //退出成功
        }
        $this->plate = $plate;
        $this->line = $this->getLineInfo();
        $platform = $plate['code'];
        $this->platformService = GamePlatformFactory::getPlatformService($platform, $this->line, $this->user);
        $GameWallet = model('app\common\model\GameWallet',$this->cid);
        $row = $this->platformService->balanceUser($this->user);
        if($row['code'] == 0){
            $data = [
                'withdraw' => $row['money'],
                'worder_sn' => $row['transNo'],
                'up_time' => time(),
            ];
            $GameWallet->edit($game_wallet['id'],$data);
            $this->user_blance($this->user,$row['money'],$game_wallet['id']);
        }
        return success("Sair com sucesso"); //退出成功
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
}