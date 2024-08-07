<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 活动相关接口
 * @Apidoc\Title("活动相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(24)
 */
class Activity extends Base{
    /**
     * @Apidoc\Title("活动列表")
     * @Apidoc\Desc("活动列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("活动列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="活动列表",table="cp_activity")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id desc');
        $adModel = app('app\common\model\Activity');
        $list = $adModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑活动")
     * @Apidoc\Desc("添加编辑活动")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("活动")
     * @Apidoc\Param("",type="array",table="cp_activity")
     */
    public function edit(){
        $data = input("post.");
        $requiredFields = [
            'name' => '请输入活动名称',
            'first_reward' => '请输入第一名奖励',
            'second_reward' => '请输入第二名奖励',
            'third_reward' => '请输入第三名奖励',
            'multiple'  =>   '请输入投注流水倍数',
            'start_time' => '请输入开始时间',
            'end_time' => '请输入结束时间',
        ];
        foreach ($requiredFields as $field => $errorMsg) {
            if (!isset($data[$field]) || $data[$field] == '') {
                return error($errorMsg);
            }
        }
        if($data['start_time'] > $data['end_time']){
            return error("开始时间不能大于结束时间");
        }
        $adModel = app('app\common\model\Activity');
        return $adModel->add($data);
    }

    /**
     * @Apidoc\Title("删除活动")
     * @Apidoc\Desc("删除活动")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("活动")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的活动ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $adModel = app('app\common\model\Activity');
        $res = $adModel->where('id', $id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}