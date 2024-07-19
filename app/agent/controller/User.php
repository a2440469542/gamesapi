<?php
namespace app\agent\controller;
use app\admin\controller\Base;
use hg\apidoc\annotation as Apidoc;
use app\common\model\User as UserModel;
use app\admin\model\Menu;
/**
 * 虚拟账号相关接口
 * @Apidoc\Title("虚拟账号相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(3)
 */
class User extends Base{
    /**
     * @Apidoc\Title("虚拟账号列表")
     * @Apidoc\Desc("虚拟账号列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("虚拟账号")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("orderBy", type="string",require=false, desc="字段排序")
     * @Apidoc\Param("user", type="string",require=false, desc="用户名：搜索时候传")
     * @Apidoc\Param("mobile", type="string",require=false, desc="手机：搜索时候传")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("uid", type="int",require=false, desc="用户ID：搜索时候传")
     * @Apidoc\Param("inv_code", type="string",require=false, desc="用户邀请码：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="虚拟账号列表",table="cp_user")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $cid = input("cid", 0);
        $mobile = input("mobile", '');
        $user = input("user", '');
        $uid = input("uid", 0);
        $inv_code = input("inv_code", '');
        $orderBy = input("orderBy", 'uid asc');
        $where[] = ['is_rebot','=',1];
        if ($mobile) {
            $where[] = ['mobile', "=", $mobile];
        }
        if ($user) {
            $where[] = ['user', "LIKE", "%{$user}%"];
        }
        if ($uid > 0) {
            $where[] = ['uid', "=", $uid];
        }
        if($inv_code) {
            $where[] = ['inv_code', "=", $inv_code];
        }

        if($cid === 0){
            return error("请选择渠道");
        }
        $userModel = app('app\common\model\User');
        $userModel->setPartition($cid);
        $list = $userModel->lists($where, $limit, "uid desc");
        foreach ($list['data'] as &$v) {
            $where = [
                ['u.pid',"=",$v['uid']],
            ];
            $v['child_num'] = $userModel->get_child_num($where);
        }
        return success("获取成功", $list);
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
    /**
     * @Apidoc\Title("生成试玩账号")
     * @Apidoc\Desc("生成试玩账号")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("生成试玩长啊后")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("num", type="int",require=true, desc="用户数量")
     * @Apidoc\Returned("mobile",type="string",desc="试玩手机号")
     * @Apidoc\Returned("pwd",type="string",desc="试玩密码")
     */
    public function create_rebot(){
        $cid = input("cid");
        if(!$cid){
            return  error("缺少参数cid");
        }
        $userModel = app('app\common\model\User');
        $userModel->setPartition($cid);
        $res = $userModel->create_rebot(20,$cid);
        return success("创建成功",$res);
    }
}