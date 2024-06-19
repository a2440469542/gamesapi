<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\admin\model\Admin as AdminModel;
use think\facade\Cache;

/**
 * 管理员管理相关接口
 * @Apidoc\Title("管理员管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(2)
 */
class Admin extends Base{
    /**
     * @Apidoc\Title("管理员列表")
     * @Apidoc\Desc("管理员列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("管理员")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("orderBy", type="string",require=false, desc="字段排序")
     * @Apidoc\Param("keyword", type="string",require=false, desc="登录名：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("",type="array",ref="app\admin\model\Admin\lists")
     */
    public function index(){
        if($this->request->isPost()){
            $where = [];
            $limit = input("limit");
            $keyword = input("keyword",'');
            $orderBy = input("orderBy",'id asc');
            if($keyword){
                $where[] = ['user_name',"LIKE","%{$keyword}%"];
            }
            $list = AdminModel::lists($where,$limit,$orderBy);
            return success("获取成功",$list);
        }
        return view();
    }
    /**
     * @Apidoc\Title("添加编辑管理员")
     * @Apidoc\Desc("添加编辑管理员")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("管理员")
     * @Apidoc\Param(ref="app\admin\model\Admin\add")
     */
    public function add(){
        $data = input("post.");
        if(isset($data['id']) && $data['id'] > 0){
            unset($data['salt']);
            if($data['password'] != ''){
                $info = AdminModel::find($data['id']);
                if($info['password'] != $data['password']){
                    $data['password'] = md5(md5($data['password']) . $info->salt);
                }
            }else{
                unset($data['password']);
            }
            $res = AdminModel::where("id","=",$data['id'])->update($data);
        }else{
            $data['salt'] = random(32);
            $data['password'] = md5(md5($data['password']) . $data['salt']);
            $data['created_at'] = date("Y-m-d H:i:s",time());
            $res = AdminModel::insert($data);
        }
        if($res){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }

    /**
     * @Apidoc\Title("删除管理员")
     * @Apidoc\Desc("删除管理员")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("管理员")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        if($id == 10000){
            return error("超级管理员无法删除");
        }
        $res = AdminModel::destroy($id);
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
    /**
     * @Apidoc\Title("获取当前管理员信息")
     * @Apidoc\Desc("获取当前管理员信息接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("管理员")
     */
    public function userInfo(){
        $aid =  $this->request->aid;
        $user = AdminModel::field("id,user_name,nickname,avatar")->find($aid);
        if($user){
            return success("获取成功",$user);
        }else{
            return error("没有此用户");
        }
    }
    /**
     * @Apidoc\Title("退出登录")
     * @Apidoc\Desc("退出登录接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("管理员")
     */
    public function logout(){
        $token = $this->request->header("token");
        try {
            Cache::delete($token);
        }catch (\Throwable $e) {
            return error($e->getMessage());
        }
        return success('退出成功');
    }
}