<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 用户订单相关接口
 * @Apidoc\Title("用户订单相关接口")
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
     * @Apidoc\Returned("list",type="array",desc="排行榜",table="cp_user_stat")
     */
    public function rank()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $channel = model('app\common\model\Channel')->info($cid,'');
        if (!$channel) {
            return error("O canal não existe",10001);//渠道不存在
        }
        $aid = $channel['activity']['rank'];
        $activity = app('app\common\model\Activity')->where("aid",'=',$aid)->find();
        if($activity['start_time'] <= date("Y-m-d H:i:s")){
            return error("A atividade ainda não começou",500);//活动未开始
        }
        if($activity['end_time'] >= date("Y-m-d H:i:s")){
            return error("A atividade terminou",500);//活动结束
        }
        $UserStat = model('app\common\model\UserStat',$cid);
        $where = ['date','between',[$activity['start_time'],$activity['end_time']]];
        $list = $UserStat->get_rank($where,20);
        foreach ($list as $key => &$value) {
            if($key <= 2){
                $value['is_get'] = 0;
                if($value['uid'] == $uid && $activity['end_time'] <= date("Y-m-d H:i:s")){
                    $log = app('app\common\model\RankLog')->where('uid','=',$value['uid'])->where('cid','=',$cid)->where('aid','=',$aid)->count();
                    if($log) {
                        $value['is_get'] = 1;
                    }
                }
            }
        }
        $data['rank'] = $activity;
        $data['list'] = $list;
        return success('obter sucesso',$data);   //获取成功
    }
}
