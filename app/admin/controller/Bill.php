<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;
/**
 * 账变相关接口
 * @Apidoc\Title("账变相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class Bill extends Base{
    /**
     * @Apidoc\Title("账变记录")
     * @Apidoc\Desc("账变记录获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("账变记录")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("mobile", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Param("type", type="string",require=false, desc="账变类型")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="充值记录相关",table="cp_bill",children={
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
    /**
     * @Apidoc\Title("账变记录类型")
     * @Apidoc\Desc("账变记录类型")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("账变记录类型")
     */
    public function get_type(){
        $BillModel = app('app\common\model\Bill');
        $list = $BillModel->getTypeTextAttr();
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("用户余额修改")
     * @Apidoc\Desc("用户余额修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户余额修改")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("uid", type="int",require=true, desc="用户uid")
     * @Apidoc\Param("money", type="float",require=true, desc="账变金额：增加正数；扣除负数")
     */
    public function bill(){
        $uid = input("uid", 0);
        $cid  = input("cid", '');
        $money = input("money", '');
        if($uid === 0) return error("用户ID不能为空");
        if($cid === '') return error("渠道ID不能为空");
        if($money === '') return error("金额不能为空");
        $BillModel = model('app\common\model\Bill',$cid);
        $user = model('app\common\model\User',$cid);
        $user_info = $user->getInfo($uid);
        if(!$user_info) return error("用户不存在");
        if($money < 0 && $user_info['money'] < abs($money)) return error("余额不足");
        // 启动事务
        Db::startTrans();
        try {
            $BillModel->addIntvie($user,$BillModel::ADMIN_MONEY,$money);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        return success("操作成功");
    }
}