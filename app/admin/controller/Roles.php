<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\admin\model\Roles as RolesModel;
use app\admin\model\Menu;
/**
 * 角色管理相关接口
 * @Apidoc\Title("角色管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(5)
 */
class Roles extends Base{
    /**
     * @Apidoc\Title("角色列表")
     * @Apidoc\Desc("角色列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("角色")
     * @Apidoc\Returned(type="array",desc="角色列表",table="cp_roles")
     */
    public function index(){
        if($this->request->isPost()) {
            $list = RolesModel::lists();
            return success("获取成功", $list);
        }
        return view();
    }
    public function menu(){
        $aid = $this->aid;
        $admin = app("\app\admin\model\Admin")::find($aid);
        $where = [];
        if($admin->rid != 1){
            $roles = RolesModel::find($admin->rid);
            $where[] = ['id',"IN",$roles->rule];
        }
        $where[] = ['is_menu',"=",1];
        $menu = Menu::where($where)->select();
        $menu = Menu::auth_menu($menu,0);
        return success("获取成功",$menu);
    }
    /**
     * @Apidoc\Title("添加编辑角色")
     * @Apidoc\Desc("添加编辑角色")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("角色")
     * @Apidoc\Param("",type="object",table="cp_roles")
     */
    public function edit(){
        $data = input("post.");
        if(isset($data['rid']) && $data['rid'] == 1){
            return error("超级管理员不可编辑");
        }
        $res = RolesModel::add($data);
        if($res){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }

    /**
     * @Apidoc\Title("删除角色")
     * @Apidoc\Desc("删除角色")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("角色")
     * @Apidoc\Param("rid", type="int",require=true, desc="删除数据的ID")
     */
    public function del(){
        $id = input("rid");
        if(!$id){
            return error("请选择要删除的数据");
        }
        if($id === 1){
            return  error("超级管理员不可删除");
        }
        $res = RolesModel::destroy($id);
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
    public function menu_list(){
        $list = Menu::lists();
        unset($list[0]);
        $list = array_values($list);
        return success("获取成功",$list);
    }
}