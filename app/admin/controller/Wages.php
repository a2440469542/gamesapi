<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;
/**
 * 工资领取相关接口
 * @Apidoc\Title("账变相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(12)
 */
class Wages extends Base{
    /**
     * @Apidoc\Title("工资领取记录")
     * @Apidoc\Desc("工资领取记录")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("工资领取记录")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("mobile", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Param("type", type="string",require=false, desc="账变类型")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="充值记录相关",table="cp_wages")
     */
    public function index(){
        if($this->request->isPost()) {
            $where = [];
            $limit = input("limit");
            $orderBy = input("orderBy", 'id desc');
            $mobile = input("mobile", '');
            $type  = input("type", '');
            $cid  = input("cid", '');
            if($cid === ''){
                return error("渠道ID不能为空");
            }
            if($mobile !== '') $where[] = ['mobile', '=', $mobile];
            if($type !== '') $where[] = ['type', '=', $type];
            $WagesModel = model('app\common\model\Wages',$cid);
            $list = $WagesModel->lists($where, $limit, $orderBy);
            return success("获取成功", $list);
        }
        return view();
    }
}