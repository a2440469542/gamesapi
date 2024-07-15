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
     * @Apidoc\Param("uid", type="int",require=false, desc="用户ID：搜索时候传")
     * @Apidoc\Param("inv_code", type="string",require=false, desc="用户邀请码：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="用户列表",table="cp_user")
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
    /*public function edit(){
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
    }*/
    /**
     * @Apidoc\Title("修改密码")
     * @Apidoc\Desc("修改密码")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("修改密码")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("uid", type="int",require=true, desc="用户ID")
     * @Apidoc\Param("pwd", type="string",require=true, desc="用户密码")
     */
    public function update_pwd(){
        $uid = input("uid");
        $cid = input("cid");
        $pwd = input("pwd");
        if(!$uid){
            return error("请选择要修改的用户");
        }
        if(!$cid){
            return  error("缺少参数cid");
        }
        if(!$pwd){
            return  error("请输入密码");
        }
        $userModel = app('app\common\model\User');
        $userModel->setPartition($cid);
        $res = $userModel->update_pwd($uid,$pwd);
        if($res){
            return success("修改成功");
        }else{
            return error("修改失败");
        }
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
    public function set_kol(){
        $uid = input("uid");
        $cid = input("cid");
        $is_kol = input("is_kol",0);
        if(!$uid){
            return error("请选择要修改的用户");
        }
        if(!$cid){
            return  error("缺少参数cid");
        }
        $userModel = app('app\common\model\User');
        $userModel->setPartition($cid);
        $res = $userModel->set_kol($uid,$is_kol);
        if($res){
            return success("修改成功");
        }else{
            return error("修改失败");
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
        $num = input("num",1);
        if(!$cid){
            return  error("缺少参数cid");
        }
        $userModel = app('app\common\model\User');
        $userModel->setPartition($cid);
        $res = $userModel->create_rebot($num,$cid);
        return success("创建成功",$res);
    }
    /**
     * @Apidoc\Title("获取下级数据")
     * @Apidoc\Desc("获取下级数据")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("获取下级数据")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("uid", type="int",require=true, desc="用户ID")
     * @Apidoc\Returned("",type="array",desc="用户列表",table="cp_user_stat",children={
     *      @Apidoc\Returned("mobile",type="string",desc="试玩手机号"),
     *      @Apidoc\Returned("inv_code",type="string",desc="邀请码"),
     *      @Apidoc\Returned("money",type="float",desc="金额")
     * })
     */
    public function get_child(){
        $uid = input("uid");
        $cid = input("cid");
        if(!$uid){
            return  error("缺少参数cid");
        }
        if(!$cid){
            return  error("缺少参数cid");
        }
        $UserStatModel = app('app\common\model\UserStat');
        $list = $UserStatModel->get_child($cid,$uid);
        return success("获取成功",$list);
    }
    /**
     * @Apidoc\Title("绑定上级")
     * @Apidoc\Desc("绑定上级")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("绑定上级")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("uid", type="int",require=true, desc="用户ID")
     * @Apidoc\Param("inv_code", type="string", require=true, desc="邀请码")
     */
    public function bind_child(){
        $inv_code = input("inv_code");//上级ID
        $uid = input("uid");    //自己ID
        $cid = input("cid");
        if(!$inv_code){
            return  error("缺少参数inv_code");
        }
        if(!$uid){
            return  error("缺少参数uid");
        }
        if(!$cid){
            return  error("缺少参数cid");
        }
        $UserModel = model('app\common\model\User',$cid);
        $user = $UserModel->getInfo($uid);    //获取自己的信息
        if($user['pid'] > 0){
            return error("该用户已经绑定上级");
        }
        $p_user = $UserModel->get_inv_info($inv_code);    //获取上级的信息
        if($p_user){
            $data['uid'] = $uid;
            $data['pid']   = $p_user['uid'];
            $data['ppid']  = $p_user['pid'];
            $data['pppid'] = $p_user['ppid'];
            $UserModel->update_user($data);
            $UserStatModel = model('app\common\model\UserStat',$cid);
            $stat = ['invite_user' => 1];
            $UserStatModel->add($p_user,$stat);
        }
        return success("绑定成功");
    }
}