<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;
/**
 * 上分记录相关接口
 * @Apidoc\Title("上分记录相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class BillLog extends Base{
    /**
     * @Apidoc\Title("上分记录")
     * @Apidoc\Desc("上分记录获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("上分记录")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("mobile", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Param("inv_code", type="string",require=false, desc="用户邀请码：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="上分记录相关",table="cp_bill_log",children={
     *          @Apidoc\Returned("name",type="string",desc="渠道名称")
     *     })
     */
    public function index(){
        if($this->request->isPost()) {
            $where = [];
            $limit = input("limit");
            $orderBy = input("orderBy", 'id desc');
            $mobile = input("mobile", '');
            $cid  = input("cid", 0);
            $inv_code = input("inv_code",'');
            /*if($cid === ''){
                return error("渠道ID不能为空");
            }*/
            if($inv_code){
                $where[] = ['inv_code',"=",$inv_code];
            }
            if($mobile !== '') $where[] = ['mobile', '=', $mobile];
            if($cid > 0) $where[] = ['b.cid', '=', $cid];
            $BillModel = app('app\common\model\BillLog');
            $list =  $BillModel->lists($where, $limit, $orderBy);
            return success("获取成功", $list);
        }
        return view();
    }
}