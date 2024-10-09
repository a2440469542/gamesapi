<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;
/**
 * 提现相关接口
 * @Apidoc\Title("提现相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class Cash extends Base{
    /**
     * @Apidoc\Title("提现记录")
     * @Apidoc\Desc("提现记录获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("提现记录")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("mobile", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Param("inv_code", type="string",require=false, desc="用户邀请码：搜索时候传")
     * @Apidoc\Param("pix", type="string",require=false, desc="提款账号：搜索时候传")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("status", type="int",require=true, desc="状态：0=待审核；1=审核通过；-1=拒绝提现；2=提现成功；-2=提现失败")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="充值记录相关",table="cp_cash",children={
     *           @Apidoc\Returned("mobile",type="string",desc="用户手机号")
     *      })
     */
    public function index(){
        if($this->request->isPost()) {
            $where = [];
            $limit = input("limit");
            $orderBy = input("orderBy", 'id desc');
            $mobile = input("mobile", '');
            $cid  = input("cid", 0);
            $inv_code = input("inv_code",'');
            $order_sn = input("order_sn",'');
            $pix = input("pix",'');
            $status = input("status",'');
            if($status){
                $where[] = ['c.status',"=",$status];
            }
            if($order_sn){
                $where[] = ['order_sn|orderno',"=",$order_sn];
            }
            if($mobile) {
                $where[] = ['mobile', '=', $mobile];
            }
            if($inv_code){
                $where[] = ['u.inv_code',"=",$inv_code];
            }
            if($pix){
                $where[] = ['pix',"=",$pix];
            }

            if($cid === 0){
                $CashModel = app('app\common\model\Cash');
            }else{
                $CashModel = model('app\common\model\Cash',$cid);
            }
            $list = $CashModel->lists($where, $limit, $orderBy);
            return success("获取成功", $list);
        }
        return view();
    }
    /**
     * @Apidoc\Title("用户提现审核")
     * @Apidoc\Desc("用户提现审核")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户提现审核")
     * @Apidoc\Param("id", type="int",require=false, desc="字段排序")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("status", type="int",require=true, desc="状态：1=审核通过；-1=拒绝提现")
     * @Apidoc\Param("desc", type="string",require=true, desc="备注")
     */
    public function cash_examine(){
        $id = input("id");
        $cid = input('cid');
        $desc = input("desc");
        $status = input("status");
        if(empty($id) || empty($status) || empty($cid)) return error("参数错误");
        $CashModel = model('app\common\model\Cash', $cid);
        $order = $CashModel->getByIdInfo($id);
        $BillModel = model('app\common\model\Bill', $cid);
        if (empty($order)) {
            return error("订单不存在");
        }
        $UserModel = model('app\common\model\User', $cid);
        $user = $UserModel->getInfo($order['uid']);
        if (empty($user)) {
            return error("用户不存在");
        }
        Db::startTrans();
        try {
            $update = [
                'id' => $order['id'],
                'status' => $status,
                'desc' => $desc,
                'update_time' => time()
            ];
            if($status == 1){
                $config = get_config();
                $payClass = app('app\service\pay\KirinPay');
                if(isset($config['pay_config'])){
                    $payClass = app('app\service\pay\\'.$config['cash_pay_config']);
                }
                $res = $payClass->cash_out($order['order_sn'] ,$order['real_money'],$order['type'],$order['account'],$order['pix'],$user);
                if($res['code'] != 0) {
                    return error($res['msg']);
                }
            }else if($status == -1){
                //提现失败返回

                $BillModel->addIntvie($user, $BillModel::CASH_RETURN, $order['money']);
                app('app\common\model\Mail')->add($cid,$order['uid'],$desc,$order['money']);
                if ($CashModel->update_order($update)) {
                    Db::commit();
                } else {
                    Db::rollback();
                    return error("操作失败");
                }
            }
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage());
        }
        return success("操作成功");
    }
}