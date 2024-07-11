<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\Plate as PlateModel;
use app\admin\model\Menu;
/**
 * 游戏平台管理相关接口
 * @Apidoc\Title("游戏平台管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(7)
 */
class Plate extends Base{
    /**
     * @Apidoc\Title("游戏平台列表")
     * @Apidoc\Desc("游戏平台列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏平台")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("orderBy", type="string",require=false, desc="字段排序")
     * @Apidoc\Param("keyword", type="string",require=false, desc="登录名：搜索时候传")
     * @Apidoc\Returned(type="array",desc="游戏平台列表",table="cp_plate")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id desc');
        $PlateModel = app("app\common\model\Plate");
        $list = $PlateModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑游戏平台")
     * @Apidoc\Desc("添加编辑游戏平台")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏平台")
     * @Apidoc\Param("",type="array",table="cp_plate")
     */
    public function edit(){
        $data = input("post.");
        if(!isset($data['name']) || !$data['name']){
            return error("请输入游戏平台名");
        }
        if(!isset($data['code']) || !$data['code']){
            return error("请输入游戏code");
        }
        $PlateModel = app("app\common\model\Plate");
        return $PlateModel->add($data);
    }

    /**
     * @Apidoc\Title("删除游戏平台")
     * @Apidoc\Desc("删除游戏平台")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏平台")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的游戏平台ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $PlateModel = app("app\common\model\Plate");
        $LineModel = app("app\common\model\Line");
        $GameModel = app('app\common\model\Game');
        $count = $GameModel->where("pid",$id)->count();
        if($count > 0) return error("该游戏平台下有游戏，不能删除");
        $res = $PlateModel->where('id', $id)->delete();
        $LineModel->where("pid",$id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}