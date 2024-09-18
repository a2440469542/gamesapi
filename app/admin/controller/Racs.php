<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 比赛活动相关接口
 * @Apidoc\Title("比赛活动相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(9)
 */
class Racs extends Base{
    /**
     * @Apidoc\Title("比赛活动列表")
     * @Apidoc\Desc("比赛活动列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("比赛活动")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="比赛活动列表",table="cp_racs")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id desc');
        $RacsModel = app('app\common\model\Racs');
        $list = $RacsModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑比赛活动")
     * @Apidoc\Desc("添加编辑比赛活动")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("比赛活动")
     * @Apidoc\Param("",type="array",table="cp_racs")
     */
    public function edit(){
        $data = input("post.");
        $RacsModel = app('app\common\model\Racs');
        return $RacsModel->add($data);
    }

    /**
     * @Apidoc\Title("删除比赛活动")
     * @Apidoc\Desc("删除比赛活动")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("比赛活动")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的比赛活动ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $RacsModel = app('app\common\model\Racs');
        $res = $RacsModel->where('id', $id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}