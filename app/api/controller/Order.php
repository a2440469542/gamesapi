<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 用户订单相关接口
 * @Apidoc\Title("用户订单相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(8)
 */
class Order extends Base
{
    /**
     * @Apidoc\Title("用户充值订单列表")
     * @Apidoc\Desc("用户充值订单列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户充值订单列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("orderBy", type="string",require=false, desc="字段排序")
     * @Apidoc\Param("limit", type="int",require=true, desc="每页的条数")
     * @Apidoc\Param("status", type="int",require=true, desc="状态：1=支付中;2=支付成功;3=支付失败")
     * @Apidoc\Param("date", type="int",require=true,default=5,desc="时间：1=今天；2=本周；3=本月；4=本年；5=全部")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="订单相关信息",table="cp_order")
     */
    public function get_recharge()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $status = input('status',"");
        $date = $this->request->post('date',5);
        $limit = input("limit",10);
        $OrderModel = model('app\common\model\Order',$cid);
        $where[] = ['uid',"=",$uid];
        $date_where = $this->get_time($date);
        if($date_where){
            $where[] = $date_where;
        }
        $list = $OrderModel->getList($where,$limit);
        return success('obter sucesso',$list);   //获取成功
    }
    protected function get_time($date){
        $where = [];
        switch($date){
            case 1:
                $start_time = strtotime(date("Y-m-d"));
                //获取明天的日期
                $end_time = time();
                $where = ['add_time',"between",[$start_time, $end_time]];
                break;
            case 2:
                //创建一个本周开始时间和结束时间的条件
                $start_time = strtotime('monday this week');
                $end_time = time();
                $where = ['add_time', 'between', [$start_time, $end_time]];
                break;
            //创建一个本月开始时间和结束时间的条件
            case 3:
                $start_time = strtotime(date('Y-m-01'));
                $end_time = time();
                $where = ['add_time', 'between', [$start_time, $end_time]];
                break;
            //创建一个今年开始时间和当前为结束时间的条件
            case 4:
                $start_time = strtotime(date('Y-01-01'));
                $end_time = time();
                $where = ['add_time', 'between', [$start_time, $end_time]];
                break;
        }
        return $where;
    }
}
