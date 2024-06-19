<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\admin\model\Menu as MenuModel;
use app\admin\model\Roles;
/**
 * 菜单管理相关接口
 * @Apidoc\Title("菜单管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(4)
 */
class Menu extends Base{
    /**
     * @Apidoc\Title("菜单列表")
     * @Apidoc\Desc("菜单列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("菜单")
     * @Apidoc\Returned("",type="array",desc="菜单列表",table="cp_menu")
     */
    public function index(){
        if($this->request->isPost()) {
            $aid = $this->request->aid;
            $admin = app("\app\admin\model\Admin")::find($aid);
            $where = [];
            if ($admin->rid != 1) {
                $roles = Roles::find($admin->rid);
                $where[] = ['id', "IN", $roles->rule];
            }
            $menu = MenuModel::where($where)->order("sort asc")->select();
            $menu = MenuModel::auth_menu($menu, 0);
            return success("获取成功", $menu);
        }
        return view();
    }
    public function all_list(){
        $list = MenuModel::lists();
        return success("获取成功",$list);
    }
    /**
     * @Apidoc\Title("添加编辑菜单")
     * @Apidoc\Desc("添加编辑菜单")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("菜单")
     * @Apidoc\Param("",type="object",table="cp_menu")
     */
    public function add(){
        $data = input("post.");
        if(is_array($data['pid'])){
            $data['pid'] = end($data['pid']);
        }
        $res = MenuModel::add($data);
        if($res){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }

    /**
     * @Apidoc\Title("删除菜单")
     * @Apidoc\Desc("删除菜单")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("菜单")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $res = MenuModel::destroy($id);
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}