<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 游戏日志相关接口
 * @Apidoc\Title("游戏日志相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class GameLog extends Base{
    /**
     * @Apidoc\Title("游戏日志")
     * @Apidoc\Desc("游戏日志")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏日志")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("mobile", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="充值记录相关",table="cp_order",children={
     *          @Apidoc\Returned("mobile",type="string",desc="用户手机号")
     *     })
     */
    public function index(){
        if($this->request->isPost()) {
            $where = [];
            $limit = input("limit");
            $orderBy = input("orderBy", 'id desc');
            $mobile = input("mobile", '');
            $order_sn  = input("order_sn", '');
            $type  = input("type", '');
            $cid  = input("cid", '');
            if($cid === ''){
                return error("渠道ID不能为空");
            }
            if($order_sn !== '') $where[] = ['order_sn', '=', $order_sn];
            if($mobile !== '') $where[] = ['mobile', '=', $mobile];
            if($type !== '')$where[] = ['type', '=', $type];
            $BillModel = model('app\common\model\Bill',$cid);
            $list = $BillModel->lists($where, $limit, $orderBy);
            return success("获取成功", $list);
        }
        return view();
    }
    public function get_type(){
        $BillModel = app('app\common\model\Bill');
        $list = $BillModel->getTypeListText();
        return success("获取成功", $list);
    }
}