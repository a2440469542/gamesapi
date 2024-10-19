<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

/**
 * 排行榜相关接口
 * @Apidoc\Title("排行榜相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(8)
 */
class Activity extends Base
{
    /**
     * @Apidoc\Title("用户排行榜配置接口")
     * @Apidoc\Desc("用户排行榜配置接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户排行榜配置接口")
     * @Apidoc\Returned("rank",type="object",desc="活动配置相关信息",table="cp_activity")
     */
    public function rank_config(){
        $cid = $this->request->cid;
        $channel = model('app\common\model\Channel')->info($cid,'');
        if (!$channel) {
            return error("O canal não existe",10001);//渠道不存在
        }
        if(!isset($channel['activity']['rank']) || $channel['activity']['rank'] == 0){
            return error("Actividade não ativada",10001);//获取未开启
        }
        $aid = $channel['activity']['rank'];
        if(empty($aid)) {
            return error("A atividade não existe",10001);//活动不存在
        }
        $activity = app('app\common\model\Activity')->info($aid);
        if(empty($activity)) {
            return error("A atividade não existe",10001);//活动不存在
        }

        if($activity['start_time'] >= date("Y-m-d H:i:s")){
            return error("A atividade ainda não começou",500);//活动未开始
        }
        $activity['over_time'] = max(0, strtotime($activity['end_time']) - time());
        return success('obter sucesso',$activity);   //获取成功
    }
    /**
     * @Apidoc\Title("用户排行榜")
     * @Apidoc\Desc("用户排行榜")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户排行榜")
     * @Apidoc\Param("aid",type="int",desc="活动ID")
     * @Apidoc\Param("type",type="int",desc="类型：1=玩家充值排行榜；2=N1充值排行榜；3=N3充值排行榜；4=N4充值排行榜")
     * @Apidoc\Returned("rank",type="object",desc="活动配置相关信息",table="cp_activity")
     * @Apidoc\Returned("list",type="array",desc="充值排行榜",children={
     *     @Apidoc\Returned("uid",type="int",desc="用户uid"),
     *     @Apidoc\Returned("inv_code",type="int",desc="用户邀请码"),
     *     @Apidoc\Returned("mobile",type="string",desc="用户手机号"),
     *     @Apidoc\Returned("cz_money",type="float",desc="充值金额"),
     *     @Apidoc\Returned("is_get",type="int",desc="是否能领取:0=不能；1=可以")
     *  })
     * @Apidoc\Returned("list",type="array",desc="排行榜字段",children={
     *      @Apidoc\Returned("uid",type="int",desc="用户uid"),
     *      @Apidoc\Returned("inv_code",type="int",desc="用户邀请码"),
     *      @Apidoc\Returned("mobile",type="string",desc="用户手机号"),
     *      @Apidoc\Returned("invite_user",type="int",desc="邀请人数"),
     *      @Apidoc\Returned("cz_money",type="float",desc="充值金额"),
     *      @Apidoc\Returned("is_get",type="int",desc="是否能领取:0=不能；1=可以")
     *   })
     */
    public function rank()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $type = input('type',1);
        $aid = input('aid',0);
        if(empty($aid)) {
            return error("A atividade não existe",10001);//活动不存在
        }
        /*$channel = model('app\common\model\Channel')->info($cid,'');
        if (!$channel) {
            return error("O canal não existe",10001);//渠道不存在
        }
        if(!isset($channel['activity']['rank']) || $channel['activity']['rank'] == 0){
            return error("Actividade não ativada",10001);//获取未开启
        }
        $aid = $channel['activity']['rank'];
        if(empty($aid)) {
            return error("A atividade não existe",10001);//活动不存在
        }*/
        $activity = app('app\common\model\Activity')->info($aid);
        if(empty($activity)) {
            return error("A atividade não existe",10001);//活动不存在
        }
        if($activity['start_time'] >= date("Y-m-d H:i:s")){
            return error("A atividade ainda não começou",500);//活动未开始
        }
        /*if($activity['end_time'] >= date("Y-m-d H:i:s")){
            return error("A atividade terminou",500);//活动结束
        }*/
        $UserStat = model('app\common\model\UserStat',$cid);
        $sttime = date("Y-m-d",strtotime($activity['start_time']));
        $ettime = date("Y-m-d",strtotime($activity['end_time']));
        $where[] = ['date', 'between', [$sttime, $ettime]];

        if($type == 1){
            $list = $UserStat->get_rank($where, 20);
            $this->processRankList($list, $uid, $cid, $aid, $activity,$type);
        }else{
            $list = $UserStat->get_inv_rank($where, 20,$type);
            $this->processRankList($list, $uid, $cid, $aid, $activity, $type);
        }

        //$inv_list = $UserStat->get_inv_rank($where, 20);

        $data['rank'] = $activity;
        $data['list'] = $list;
        //$data['inv_list'] = $inv_list;
        return success('obter sucesso',$data);   //获取成功
    }
    private function processRankList(&$list, $uid, $cid, $aid, $activity, $type=1)
    {
        foreach ($list as $key => &$value) {
            if ($key <= 2) {
                $value['is_get'] = 0;
                if ($value['uid'] == $uid && $activity['end_time'] <= date("Y-m-d H:i:s")) {
                    $logCondition = [
                        ['uid', '=', $value['uid']],
                        ['cid', '=', $cid],
                        ['aid', '=', $aid],
                        ['inv_type', '=', $type]
                    ];
                    $log = app('app\common\model\RankLog')->where($logCondition)->count();
                    if (!$log) {
                        $value['is_get'] = 1;
                    }
                }
            }
        }
    }
    /**
     * @Apidoc\Title("领取排行榜奖励")
     * @Apidoc\Desc("领取排行榜奖励")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("领取排行榜奖励")
     * @Apidoc\Param("type",type="int",desc="类型：1=玩家充值排行榜；2=N1充值排行榜；3=N3充值排行榜；4=N4充值排行榜")
     * @Apidoc\Param("level",type="int",desc="排名第几：1=第一名；2=第二名；3=第三名")
     */
    public function get_rank()
    {
        $type = input('type', 1);
        return $this->processGetRank($type);
    }

    private function processGetRank($type)
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $level = input('level', '');
        $redis = Cache::store('redis')->handler();
        $lockKey = "user_rank_lock_{$uid}";
        if ($redis->exists($lockKey)) {
            return error('O pedido está sendo atualmente processado, por favor tente de novo mais tarde');
        }
        $redis->set($lockKey, true, 60);

        if ($level == '') {
            $redis->del($lockKey);
            return error("Faltam parâmetros necessários",10001);//缺少必要参数
        }

        $channel = model('app\common\model\Channel')->info($cid, '');
        if (!$channel || !isset($channel['activity']['rank']) || $channel['activity']['rank'] == 0) {
            $redis->del($lockKey);
            return error("A atividade não está aberta ou o canal não existe", 10001); //活动未开启或渠道不存在
        }

        $aid = $channel['activity']['rank'];
        $activity = app('app\common\model\Activity')->find($aid);
        if (empty($activity) || $activity['start_time'] >= date("Y-m-d H:i:s")) {
            $redis->del($lockKey);
            return error("A atividade não existe ou ainda não começou", 10001); //活动不存在或未开始
        }

        if ($activity['end_time'] >= date("Y-m-d H:i:s")) {
            $redis->del($lockKey);
            return error("O evento ainda não acabou", 500);     //活动未结束
        }

        $UserStat = model('app\common\model\UserStat', $cid);
        $sttime = date("Y-m-d", strtotime($activity['start_time']));
        $ettime = date("Y-m-d", strtotime($activity['end_time']));
        $where[] = ['date', 'between', [$sttime, $ettime]];
        if($type == 1){
            $list = $UserStat->get_rank($where, 3);
            $this->processRankList($list, $uid, $cid, $aid, $activity,$type);
        }else{
            $list = $UserStat->get_inv_rank($where, 3,$type);
            $this->processRankList($list, $uid, $cid, $aid, $activity, $type);
        }
        $money = $this->getRewardMoney($activity, $level, $type);
        $user = model('app\common\model\User', $cid)->getInfo($uid);
        $BillModel = model('app\common\model\Bill', $cid);
        $logCondition = [
            ['uid', '=', $uid],
            ['cid', '=', $cid],
            ['aid', '=', $aid],
            ['inv_type', '=', $type]
        ];
        $log = app('app\common\model\RankLog')->where($logCondition)->count();
        if ($log > 0) {
            $redis->del($lockKey);
            return error("Você já coletou",500);//已领取
        }

        Db::startTrans();
        try {
            $row = false;
            foreach ($list as $key => &$value) {
                if ($key + 1 == $level && $value['uid'] == $uid) {
                    if($type == 1){
                        $bill_type = $BillModel::RANK_MONEY;
                    }elseif($type == 2){
                        $bill_type = $BillModel::N1_RANK_MONEY;
                    }elseif($type == 3){
                        $bill_type = $BillModel::N2_RANK_MONEY;
                    }else{
                        $bill_type = $BillModel::N3_RANK_MONEY;
                    }
                    $result = $BillModel->addIntvie($user, $bill_type, $money, 0, $activity['multiple']);
                    if ($result['code'] !== 0) {
                        Db::rollback();
                        return error("A coleção falhou");   //领取失败
                    }
                    $row = app('app\common\model\RankLog')->add($cid, $uid, $aid,  $level, $type, $money);
                    break;
                }
            }
            if (!$row) {
                Db::rollback();
                return error("Não na lista", 500);  //不在榜单
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            write_log($e->getMessage() . '代码行数:' . $e->getLine() . '文件:' . $e->getFile(), 'rank');
            return error("A coleção falhou");   //领取失败
        } finally {
            $redis->del($lockKey);
        }
        return success("Recebido com sucesso"); //领取成功
    }

    private function getRewardMoney($activity, $level, $type)
    {
        if($type == 1){
            $reward = explode("|", $activity['user_reward']);
        }elseif($type == 2){
            $reward = explode("|", $activity['n1_reward']);
        }elseif($type == 3){
            $reward = explode("|", $activity['n2_reward']);
        }else{
            $reward = explode("|", $activity['n3_reward']);
        }
        if ($level == 1) {
            return $reward[0];
        } elseif ($level == 2) {
            return $reward[1];
        } elseif ($level == 3) {
            return $reward[2];
        }
        return 0;
    }
}
