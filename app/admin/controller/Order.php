<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 充值记录相关接口
 * @Apidoc\Title("充值记录相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(10)
 */
class Order extends Base{
    /**
     * @Apidoc\Title("充值记录")
     * @Apidoc\Desc("充值记录获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("充值记录")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("mobile", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Param("inv_code", type="string",require=false, desc="用户邀请码：搜索时候传")
     * @Apidoc\Param("order_sn", type="string",require=false, desc="订单号：搜索时候传")
     * @Apidoc\Param("money", type="float",require=false, desc="金额：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="充值记录相关",table="cp_order",children={
     *           @Apidoc\Returned("mobile",type="string",desc="用户手机号")
     *      })
     */
    public function index(){
        if($this->request->isPost()) {
            $where = [];
            $limit = input("limit");
            $orderBy = input("orderBy", 'id desc');
            $mobile = input("mobile", '');
            $order_sn  = input("order_sn", '');
            $cid  = input("cid", '');
            $inv_code = input("inv_code",'');
            $money = input("money",0);
            if($cid === ''){
                return error("渠道ID不能为空");
            }
            if($order_sn) {
                $where[] = ['order_sn|orderno', '=', $order_sn];
            }
            if($mobile) {
                $where[] = ['mobile', '=', $mobile];
            }
            if($inv_code){
                $where[] = ['u.inv_code',"=",$inv_code];
            }
            if($order_sn){
                $OrderModel = app('app\common\model\Order');
            }else{
                $OrderModel = model('app\common\model\Order',$cid);
            }
            if($money >0){
                $list = $OrderModel->getMoneyList($money, $limit, $orderBy);
            }else{
                $list = $OrderModel->lists($where, $limit, $orderBy);
            }

            return success("获取成功", $list);
        }
        return view();
    }
}