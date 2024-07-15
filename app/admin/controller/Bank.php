<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;
/**
 * 用户绑定银行卡接口
 * @Apidoc\Title("用户绑定银行卡接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class Bank extends Base{
    /**
     * @Apidoc\Title("绑定列表")
     * @Apidoc\Desc("绑定列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("绑定列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("mobile", type="string",require=false, desc="pix手机号：搜索时候传")
     * @Apidoc\Param("inv_code", type="string",require=false, desc="用户邀请码：搜索时候传")
     * @Apidoc\Param("phone", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Param("pix", type="string",require=false, desc="银行账号：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="充值记录相关",table="cp_bank",children={
     *          @Apidoc\Returned("phone",type="string",desc="用户账号"),
     *          @Apidoc\Returned("inv_code",type="string",desc="用户邀请码")
     *     })
     */
    public function index(){
        if($this->request->isPost()) {
            $where = [];
            $limit = input("limit");
            $orderBy = input("orderBy", 'id desc');
            $mobile = input("mobile", '');
            $pix  = input("pix", '');
            $cid  = input("cid", '');
            $inv_code = input("inv_code",'');
            $phone = input("phone", '');
            if($cid === ''){
                return error("渠道ID不能为空");
            }
            $where[] = ['b.cid',"=",$cid];
            if($inv_code){
                $where[] = ['u.inv_code',"=",$inv_code];
            }
            if($mobile !== '') $where[] = ['b.mobile', '=', $mobile];

            if($pix !== '') $where[] = ['pix', '=', $pix];
            if($phone !== '') $where[] = ['u.mobile', '=', $mobile];

            $BillModel = model('app\common\model\Bank',$cid);
            $list = $BillModel->getList($where, $limit, $orderBy);
            return success("获取成功", $list);
        }
        return view();
    }
    /**
     * @Apidoc\Title("拉黑账户")
     * @Apidoc\Desc("拉黑账户")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("拉黑账户")
     * @Apidoc\Param("type", type="int",require=true, desc="类型：1=pix或者手机号；2=ip")
     * @Apidoc\Param("pix", type="string",require=true, desc="银行账号：搜索时候传")
     */
    public function bind_pix(){
        $pix = input("pix",'');
        $type = input("type",1);
        if($pix == ''){
            return error("参数错误");
        }
        $count = app('app\common\model\BankBlack')->where("pix","=",$pix)->count();
        if($count > 0) return error("该账户已拉黑");
        $data = [
            "pix"=>$pix,
            "type"=>$type,
            "add_time"=>date("Y-m-d H:i:s",time())
        ];
        $row = app('app\common\model\BankBlack')->insert($data);
        if($row){
            return success("成功");
        }else{
            return error("操作失败");
        }
    }
    /**
     * @Apidoc\Title("pix黑名单")
     * @Apidoc\Desc("pix黑名单")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("pix黑名单")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("pix", type="string",require=false, desc="银行账号：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="pix黑名单相关",table="cp_bank_black")
     */
    public function black_bank(){
        if($this->request->isPost()) {
            $where = [];
            $limit = input("limit");
            $orderBy = input("orderBy", 'id desc');
            $pix  = input("pix", '');

            if($pix !== '') $where[] = ['pix', '=', $pix];

            $BillModel = app('app\common\model\BankBlack');
            $list = $BillModel->getList($where, $limit, $orderBy);
            return success("获取成功", $list);
        }
        return view();
    }
    /**
     * @Apidoc\Title("解除pix黑名单")
     * @Apidoc\Desc("解除pix黑名单")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("解除pix黑名单")
     * @Apidoc\Param("id", type="int",require=true, desc="黑名单id")
     */
    public function black_bank_del(){
        $id = input("id",0);
        if($id == 0){
            return error("参数错误");
        }
        $row = app('app\common\model\BankBlack')->where("id","=",$id)->delete();
        if($row){
            return success("成功");
        }else{
            return error("操作失败");
        }
    }
}