<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 代理相关接口
 * @Apidoc\Title("代理相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class Agent extends Base{
    /**
     * @Apidoc\Title("代理列表")
     * @Apidoc\Desc("代理列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("代理列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("mobile", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Returned("data",type="array",desc="渠道宝箱列表",table="cp_agent")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id asc');
        $mobile = input("mobile",'');
        if($mobile != ''){
            $where[] = ['mobile','=',$mobile];
        }
        $list = app('app\admin\model\Agent')->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    public function edit(){
        $data = input("post.");
        $requiredFields = [
            'cid' => '请选择渠道',
            'cz_money' => '请输入有效玩家要求累计充值',
            'bet_money' => '请输入有效玩家要求累计投注',
            'box' => '请输入宝箱相关信息',
        ];

        foreach ($requiredFields as $field => $errorMsg) {
            if (!isset($data[$field]) || !$data[$field]) {
                return error($errorMsg);
            }
        }

        $BoxModel = app('app\common\model\Box');
        return $BoxModel->add($data);
    }
    /**
     * @Apidoc\Title("代理列表")
     * @Apidoc\Desc("代理列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("代理列表")
     * @Apidoc\Param("id", type="int",require=true, desc="代理ID")
     * @Apidoc\Param("pid", type="array",require=true, desc="平台ID格式：[1,2,3]")
     */
    public function set_channel(){
        $pid = input("pid");
        $id = input("id");
        if(empty($id)) return error("请选择数据");
        if(empty($pid)) return error("请选择渠道");
        $row = app('app\admin\model\Agent')->set_channel($id, $pid);
        if($row){
            return success("设置成功");
        }else{
            return error("设置失败");
        }
    }
}