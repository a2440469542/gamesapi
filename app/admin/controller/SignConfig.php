<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;

/**
 * 签到管理相关接口
 * @Apidoc\Title("签到管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(9)
 */
class SignConfig extends Base{
    /**
     * @Apidoc\Title("签到列表")
     * @Apidoc\Desc("签到列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("签到")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="签到列表",table="cp_sign_config")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'day asc');
        $adModel = app('app\common\model\SignConfig');
        $list = $adModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑签到")
     * @Apidoc\Desc("添加编辑签到")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("签到")
     * @Apidoc\Param("",type="array",table="cp_ad")
     */
    public function edit(){
        $data = input("post.");
        if(!isset($data['day']) || !$data['day']){
            return error("请输入签到天数");
        }
        if(!isset($data['money']) || !$data['money']){
            return error("请输入奖励金额");
        }
        if(!isset($data['multiple']) || !$data['multiple']){
            return error("请输入投注倍数");
        }
        $adModel = app('app\common\model\SignConfig');
        return $adModel->add($data);
    }
    /**
     * @Apidoc\Title("签到规则获取")
     * @Apidoc\Desc("签到规则获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("签到规则获取")
     * @Apidoc\Returned("",type="string",desc="签到规则")
     */
    public function get_rule(){
        $res = Db::name("config")->where("code","=",'sign_rule')->find();
        return success("获取成功",$res);
    }
    /**
     * @Apidoc\Title("签到规则")
     * @Apidoc\Desc("签到规则")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("签到规则")
     * @Apidoc\Param("sign_rule",type="string",desc="签到规则")
     */
    public function set_rule(){
        $sign_rule = input("sign_rule");
        if(!$sign_rule){
            return error("请输入签到规则");
        }
        $res = Db::name("config")->where("code","=",'sign_rule')->find();
        if($res){
            Db::name("config")->where("id","=",$res['id'])->update(['value'=>$sign_rule]);
        }else{
            Db::name("config")->insert(['code'=>'sign_rule','value'=>$sign_rule]);
        }
        Cache::store('redis')->delete('config');
        return success("保存成功");
    }
    /**
     * @Apidoc\Title("删除签到")
     * @Apidoc\Desc("删除签到")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("签到")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的签到ID")
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