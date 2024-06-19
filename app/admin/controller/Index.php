<?php

namespace app\admin\controller;

use app\admin\model\Menu;
use app\BaseController;
use think\facade\Db;

class Index extends BaseController
{
    public function index()
    {
        $admin =  session('admin');
        if(empty($admin)){
            return redirect(url("/admin/login/index"))->send();
        }
        $aid = $admin['aid'];
        $rid = $admin['rid'];
        $where = [];
        if($rid > 1){
            $rule = Db::name('roles')->where('rid',"=",$rid)->find();
            $where[] = ['id',"IN",$rule['rule']];
        }
        $where[] = ['is_menu',"=",1];
        $menu = Menu::where($where)->order('sort asc')->select()->toArray();
        $menu = Menu::auth_menu($menu,0);
        return view("",['menu'=>$menu]);
    }

    public function hello($name = 'ThinkPHP8')
    {
        return 'hello,' . $name;
    }
    public function main(){
        return view("");
    }
}
