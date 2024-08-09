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
     * @Apidoc\Title("用户排行榜")
     * @Apidoc\Desc("用户排行榜")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户排行榜")
     * @Apidoc\Returned("rank",type="object",desc="活动配置相关信息",table="cp_activity")
     * @Apidoc\Returned("list",type="array",desc="排行榜",children={
     *     @Apidoc\Returned("uid",type="int",desc="用户uid"),
     *     @Apidoc\Returned("inv_code",type="int",desc="用户邀请码"),
     *     @Apidoc\Returned("mobile",type="string",desc="用户手机号"),
     *     @Apidoc\Returned("cz_money",type="float",desc="充值金额"),
     *     @Apidoc\Returned("is_get",type="int",desc="是否能领取:0=不能；1=可以")
     *  })
     */
    public function rank()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $channel = model('app\common\model\Channel')->info($cid,'');
        if (!$channel) {
            return error("O canal não existe",10001);//渠道不存在
        }
        if(!isset($channel['activity']['rank']) || $channel['activity']['rank'] == 0){
            return error("Actividade não ativada",10001);//获取未开启
        }
        $aid = $channel['activity']['rank'];
        $activity = app('app\common\model\Activity')->where("id",'=',$aid)->find();
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
        $activity['over_time'] = max(0, strtotime($activity['start_time']) - time());
        
        $where[] = ['date','between',[$sttime,$ettime]];
        $list = $UserStat->get_rank($where,20);
        foreach ($list as $key => &$value) {
            if($key <= 2){
                $value['is_get'] = 0;
                if($value['uid'] == $uid && $activity['end_time'] <= date("Y-m-d H:i:s")){
                    $log = app('app\common\model\RankLog')->where('uid','=',$value['uid'])->where('cid','=',$cid)->where('aid','=',$aid)->count();
                    if(!$log) {
                        $value['is_get'] = 1;
                    }
                }
            }
        }
        $data['rank'] = $activity;
        $data['list'] = $list;
        return success('obter sucesso',$data);   //获取成功
    }
    /**
     * @Apidoc\Title("领取排行榜奖励")
     * @Apidoc\Desc("领取排行榜奖励")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("领取排行榜奖励")
     *
     * @Apidoc\Param("level",type="int",desc="排名第几：1=第一名；2=第二名；3=第三名")
     */
    public function get_rank()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $level = input('level','');
        $redis = Cache::store('redis')->handler();
        $lockKey = "user_rank_lock_{$uid}";
        if ($redis->exists($lockKey)) {
            return error('O pedido está sendo atualmente processado, por favor tente de novo mais tarde');
        }
        $redis->set($lockKey, true, 60); // 设置锁，60秒后过期


        if($level == ''){
            $redis->del($lockKey); // 处理完成后删除锁
            return error("Faltam parâmetros necessários",10001);//缺少必要参数
        }
        $channel = model('app\common\model\Channel')->info($cid,'');
        if (!$channel) {
            $redis->del($lockKey); // 处理完成后删除锁
            return error("O canal não existe",10001);//渠道不存在
        }
        if(!isset($channel['activity']['rank']) && $channel['activity']['rank'] > 0){
            return error("Actividade não ativada",10001);//获取未开启
        }
        $aid = $channel['activity']['rank'];
        $activity = app('app\common\model\Activity')->where("id",'=',$aid)->find();
        if(empty($activity)) {
            return error("A atividade não existe",10001);//活动不存在
        }
        if($activity['start_time'] >= date("Y-m-d H:i:s")){
            $redis->del($lockKey); // 处理完成后删除锁
            return error("A atividade ainda não começou",500);//活动未开始
        }
        if($activity['end_time'] >= date("Y-m-d H:i:s")){
            $redis->del($lockKey); // 处理完成后删除锁
            return error("A atividade terminou",500);//活动未结束
        }
        $UserStat = model('app\common\model\UserStat',$cid);
        $sttime = date("Y-m-d",strtotime($activity['start_time']));
        $ettime = date("Y-m-d",strtotime($activity['end_time']));
        $where[] = ['date','between',[$sttime,$ettime]];
        $list = $UserStat->get_rank($where,3);
        $money = 0;
        if($level == 1){
            $money = $activity['first_reward'];
        }elseif($level == 2){
            $money = $activity['second_reward'];
        }else if($level == 3){
            $money = $activity['third_reward'];
        }
        $user = model('app\common\model\User',$cid)->getInfo($uid);
        $BillModel = model('app\common\model\Bill', $cid);
        $log = app('app\common\model\RankLog')->where('uid','=',$uid)->where('cid','=',$cid)->where('aid','=',$aid)->count();
        if($log > 0) {
            $redis->del($lockKey); // 处理完成后删除锁
            return error("Você já coletou",500);//已领取
        }
        Db::startTrans();
        try {
            $row = false;
            foreach ($list as $key => &$value) {
                if($key+1 == $level && $value['uid'] == $uid){
                    $result = $BillModel->addIntvie($user, $BillModel::RANK_MONEY, $money,0,$activity['multiple']);
                    if($result['code'] !== 0){
                        Db::rollback();
                        return error("A coleção falhou");  //领取失败
                    }
                    $row = app('app\common\model\RankLog')->add($cid,$uid,$aid,$level,$money);
                    break;
                }
            }
            if(!$row) {
                Db::rollback();
                return error("Não há disponível para afirmar",500);//不在榜单
            }
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            write_log($e->getMessage().'代码行数:'.$e->getLine().'文件:'.$e->getFile(),'rank');
            return error('A coleção falhou');      //领取失败
        }finally {
            $redis->del($lockKey); // 处理完成后删除锁
        }
        return success("Recebido com sucesso");  //提现成功
    }
}
