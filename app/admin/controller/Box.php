<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 渠道宝箱管理相关接口
 * @Apidoc\Title("渠道宝箱管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class Box extends Base{
    /**
     * @Apidoc\Title("渠道宝箱列表")
     * @Apidoc\Desc("渠道宝箱列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道宝箱")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned(type="array",desc="渠道宝箱列表",table="cp_box")
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
        $BoxModel = app("app\common\model\Box");
        $list = $BoxModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑渠道宝箱")
     * @Apidoc\Desc("添加编辑渠道宝箱")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道宝箱")
     * @Apidoc\Param("cid",type="float",desc="渠道ID")
     * @Apidoc\Param("cz_money",type="float",desc="有效玩家累计充值")
     * @Apidoc\Param("bet_money",type="float",desc="有效玩家累计投注")
     * @Apidoc\Param("box",type="array",table="cp_box")
     */
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
}