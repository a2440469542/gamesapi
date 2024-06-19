<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\User as UserModel;
use app\admin\model\Menu;
/**
 * 用户管理相关接口
 * @Apidoc\Title("用户管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(6)
 */
class User extends Base{
    /**
     * @Apidoc\Title("用户列表")
     * @Apidoc\Desc("用户列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("orderBy", type="string",require=false, desc="字段排序")
     * @Apidoc\Param("user", type="string",require=false, desc="用户名：搜索时候传")
     * @Apidoc\Param("mobile", type="string",require=false, desc="手机：搜索时候传")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="用户列表",table="cp_user")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $cid = input("cid", 0);
        $mobile = input("mobile", '');
        $user = input("user", '');
        $orderBy = input("orderBy", 'id desc');
        if ($mobile) {
            $where[] = ['mobile', "=", $mobile];
        }
        if ($user) {
            $where[] = ['user', "LIKE", "%{$user}%"];
        }
        if($cid === 0){
            return error("请选择渠道");
        }
        $userModel = app("app\common\model\User");
        $userModel->setPartition($cid);
        $list = $userModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑用户")
     * @Apidoc\Desc("添加编辑用户")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户")
     * @Apidoc\Param("",type="object",table="cp_user")
     */
    public function edit(){
        $data = input("post.");
        if(!isset($data['cid']) || !$data['cid']){
            return error("请选择渠道");
        }
        if(!isset($data['user']) || !$data['user']){
            return error("请输入用户名");
        }
        if(!isset($data['mobile']) || !$data['mobile']){
            return error("请输入手机号");
        }
        $userModel = app("app\common\model\User");
        $userModel->setPartition($data['cid']);
        return $userModel->add($data);
    }

    /**
     * @Apidoc\Title("删除用户")
     * @Apidoc\Desc("删除用户")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户")
     * @Apidoc\Param("uid", type="int",require=true, desc="删除数据的用户ID")
     * @Apidoc\Param("cid", type="int",require=true, desc="删除数据的渠道ID")
     */
    public function del(){
        $uid = input("uid");
        $cid = input("cid");
        if(!$uid){
            return error("请选择要删除的数据");
        }
        if(!$cid){
            return  error("请选择某个渠道要删除的数据");
        }
        $userModel = app("app\common\model\User");

        $res = $userModel::partition($cid)->where('uid', $uid)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}