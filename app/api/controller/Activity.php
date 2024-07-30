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
     * @Apidoc\Returned("activity",type="object",desc="活动相关信息",table="cp_activity")
     * @Apidoc\Returned("rank",type="array",desc="排行榜",table="cp_user_stat")
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
        $where = ['date','>=',$activity['start_time']];
        $list = $UserStat->where($where)->order('id desc')->paginate();


        return success('obter sucesso',$list);   //获取成功
    }
}
