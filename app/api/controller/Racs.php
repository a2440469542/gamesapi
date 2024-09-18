<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

/**
 * 比赛活动相关接口
 * @Apidoc\Title("比赛活动相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(16)
 */
class Racs extends Base
{
    /**
     * @Apidoc\Title("比赛活动列表")
     * @Apidoc\Desc("比赛活动列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("比赛活动")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="比赛活动列表",table="cp_racs")
     */
    public function list(){
        $cid = $this->request->cid;
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id desc');
        $RacsModel = app('app\common\model\Racs');
        $list = $RacsModel->lists($where, $limit, $orderBy,$cid);
        foreach ($list['data'] as &$value) {
            if($value['game'] == 1){
                $game = app('app\common\model\Game')
                    ->field('gid,name,img,long_img')
                    ->where('is_open','=',1)
                    ->order('sort desc')->find()->toArray();
            }else if($value['game'] == 2){
                $game = app('app\common\model\Game')
                    ->field('gid,name,img,long_img')
                    ->where('is_open','=',1)
                    ->where('pid','=',$value['game_id'])
                    ->order('gid desc')->find()->toArray();
            }else if($value['game'] == 3){
                $game = app('app\common\model\Game')
                    ->field('gid,name,img,long_img')
                    ->where('gid',$value['game_id'])->find()->toArray();
            }
            $value['game_info'] = $game;
            if(strtotime($value['start_time']) > time()){
                $value['racs_status'] = 1;
            }elseif(strtotime($value['start_time']) < time() && strtotime($value['end_time']) > time()){
                $value['racs_status'] = 2;
            }else{
                $value['racs_status'] = 3;
            }
        }
        return success("obter sucesso", $list); //获取成功
    }
    /**
     * @Apidoc\Title("比赛活动列表")
     * @Apidoc\Desc("比赛活动列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("比赛活动")
     * @Apidoc\Param("id",type="int",desc="活动ID")
     * @Apidoc\Returned("over_time",type="int",desc="结束时间（秒）")
     * @Apidoc\Returned("info",type="object",desc="比赛活动详情",table="cp_racs")
     * @Apidoc\Returned("rank",type="object",desc="排行榜",children={
     *     @Apidoc\Returned("total_money",type="float",desc="金额"),
     *     @Apidoc\Returned("uid",type="int",desc="用户UID"),
     *     @Apidoc\Returned("mobile",type="string",desc="手机号"),
     *     @Apidoc\Returned("inv_code",type="string",desc="邀请码")
     *  })
     * @Apidoc\Returned("plate_name",type="string",desc="平台名称")
     * @Apidoc\Returned("game_info",type="object",desc="游戏信息",children={
     *      @Apidoc\Returned("gid",type="int",desc="游戏ID"),
     *      @Apidoc\Returned("name",type="string",desc="游戏名称"),
     *      @Apidoc\Returned("img",type="string",desc="游戏图标")
     *   })
     */
    public function getInfo(){
        $id = input("id");
        if(empty($id)) return error("Faltam parâmetros necessários"); //缺少必要参数
        $info = app('app\common\model\Racs')->find($id);
        if(empty($info)) return error("A atividade não existe"); //没有数据
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $data = [];

        $orderModel = model('app\common\model\Order',$cid);
        $GameLogModel = model('app\common\model\GameLog',$cid);
        $sttime = strtotime($info['start_time']);
        $ettime = strtotime($info['end_time']);

        if($ettime > time()){
            $data['over_time'] = $ettime-time();
        }else{
            $data['over_time'] = 0;
            if($info['uid'] == 0){
                $row = $this->get_award($cid,$uid,$info);
                if($row){
                    $update = [
                        'uid' => $uid,
                        'mobile' => $row['mobile'],
                        'inv_code' => $row['inv_code']
                    ];
                    $data['uid'] = $uid;
                    $data['mobile'] = $row['mobile'];
                    $data['inv_code'] = $row['inv_code'];
                    app('app\common\model\Racs')->where('id',"=",$info['id'])->update($update);
                    app('app\common\model\Mail')->add($cid,$uid,'Obter recompensas de concorrência'.$info['title'],$info['first']);
                }
            }
        }
        if(strtotime($info['start_time']) > time()){
            $info['racs_status'] = 1;
        }elseif(strtotime($info['start_time']) < time() && strtotime($info['end_time']) > time()){
            $info['racs_status'] = 2;
        }else{
            $info['racs_status'] = 3;
        }
        if($info['game'] == 1){

            if($info['race_type'] == 1){
                $rank = $orderModel->get_rank($sttime,$ettime);
            }else{
                $rank = $GameLogModel->get_rank($sttime,$ettime);
            }
        }else if($info['game'] == 2){
            $plate = app('app\common\model\Plate')->where('id',$info['game_id'])->value('name');
            $data['plate_name'] = $plate;

            if($info['race_type'] == 1){
                $rank = $orderModel->get_rank($sttime,$ettime);
            }else{
                $where[] = ['gl.pid','=',$info['game_id']];
                $rank = $GameLogModel->get_rank($sttime,$ettime,$where);
            }
        }else if($info['game'] == 3){
            $game = app('app\common\model\Game')->field('gid,name,img')->where('gid',$info['game_id'])->find();
            $data['game_info'] = $game;

            if($info['race_type'] == 1){
                $rank = $orderModel->get_rank($sttime,$ettime);
            }else{
                $where[] = ['gl.gid','=',$info['game_id']];
                $rank = $GameLogModel->get_rank($sttime,$ettime,$where);
            }
        }
        $data['info'] = $info;
        $data['rank'] = $rank;

        return success("obter sucesso", $data); //获取成功
    }
    private function get_award($cid,$uid,$racs){
        Db::startTrans();
        try {
            $num = $racs['first'];
            $UserModel = model('app\common\model\User',$cid);
            $user = $UserModel->getInfo($uid);
            $user_info = Db::name('user_info')->where("cid","=",$cid)->where("uid","=",$uid)->find();
            if($racs['award_type'] == 1){
                $BillModel = model('app\common\model\Bill',$cid);
                $BillModel->addIntvie($user, $BillModel::RACS_MONEY, $num, 0, $racs['multiple']);
            }else{
                $ScoreBillModel = app('app\common\model\ScoreBill');
                $ScoreBillModel->addIntvie($user_info, $cid, $uid, $ScoreBillModel::RACS_SCORE, $num);
            }
            Db::commit();
            return $user;
        }catch (\Exception $e){
            Db::rollback();
            return false;
        }
    }
}
