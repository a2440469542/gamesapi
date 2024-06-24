<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 轮播图管理相关接口
 * @Apidoc\Title("轮播图管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(9)
 */
class Ad extends Base{
    /**
     * @Apidoc\Title("轮播图列表")
     * @Apidoc\Desc("轮播图列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("轮播图")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="string",require=false, desc="字段排序")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="轮播图列表",table="cp_ad")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id desc');
        $cid = input("cid");
        if($cid) $where[] = ['cid',"=",$cid];
        $adModel = app("app\common\model\Ad");
        $list = $adModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑轮播图")
     * @Apidoc\Desc("添加编辑轮播图")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("轮播图")
     * @Apidoc\Param("",type="array",table="cp_ad")
     */
    public function edit(){
        $data = input("post.");
        if(!isset($data['cid']) || !$data['cid']){
            return error("请选择渠道");
        }
        $adModel = app("app\common\model\Ad");
        return $adModel->add($data);
    }

    /**
     * @Apidoc\Title("删除轮播图")
     * @Apidoc\Desc("删除轮播图")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("轮播图")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的轮播图ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $adModel = app("app\common\model\Ad");
        $res = $adModel->where('id', $id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}