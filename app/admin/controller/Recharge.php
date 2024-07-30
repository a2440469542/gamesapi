<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 渠道充值金额配置管理相关接口
 * @Apidoc\Title("渠道充值金额配置管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(12)
 */
class Recharge extends Base{
    /**
     * @Apidoc\Title("渠道充值金额配置列表")
     * @Apidoc\Desc("渠道充值金额配置列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道充值金额配置")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned(type="array",desc="渠道充值金额配置列表",table="cp_recharge")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id desc');
        $cid = input("cid",0);
        if($cid === 0){
            return error("请选择渠道");
        }else{
            $where[] = ['cid',"=",$cid];
        }
        $RechargeModel = app("app\common\model\Recharge");
        $list = $RechargeModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑渠道充值金额配置")
     * @Apidoc\Desc("添加编辑渠道充值金额配置")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道充值金额配置")
     * @Apidoc\Param("",type="array",table="cp_recharge")
     */
    public function edit(){
        $data = input("post.");
        $requiredFields = [
            'cid' => '请选择渠道',
            'money' => '请输入充值金额',
            'gifts' => '请输入赠送金额',
            'multiple' => '请输入赠送金额流水倍数'
        ];
        foreach ($requiredFields as $field => $errorMsg) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return error($errorMsg);
            }
        }
        if($data['gifts'] > 0 && $data['multiple'] <= 0) {
            return error("赠送金额流水倍数必须大于0");
        }
        $RechargeModel = app("app\common\model\Recharge");
        return $RechargeModel->add($data);
    }

    /**
     * @Apidoc\Title("删除渠道充值金额配置")
     * @Apidoc\Desc("删除渠道充值金额配置")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道充值金额配置")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的渠道充值金额配置ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $RechargeModel = app("app\common\model\Recharge");
        $res = $RechargeModel->where('id', $id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}