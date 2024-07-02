<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
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
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
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
            $cid  = input("cid", '');
            $inv_code = input("inv_code",'');
            if($cid === ''){
                return error("渠道ID不能为空");
            }
            if($mobile) {
                $where[] = ['mobile', '=', $mobile];
            }
            if($inv_code){
                $where[] = ['u.inv_code',"=",$inv_code];
            }
            $CashModel = model('app\common\model\Cash',$cid);
            $list = $CashModel->lists($where, $limit, $orderBy);
            return success("获取成功", $list);
        }
        return view();
    }
}