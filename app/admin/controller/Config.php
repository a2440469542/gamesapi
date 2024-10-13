<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

/**
 * 配置管理相关接口
 * @Apidoc\Title("配置管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(12)
 */
class Config extends Base{
    /**
     * @Apidoc\Title("配置列表")
     * @Apidoc\Desc("配置列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("配置列表")
     */
    public function info(){
        $info = Db::name('config')->select();
        $config = [];
        foreach($info as $k=>$v){
            $config[$v['code']] = $v['value'];
        }
        return success("获取成功",$config);
    }
    /**
     * @Apidoc\Title("保存配置")
     * @Apidoc\Desc("保存配置")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("保存配置")
     */
    public function edit(){
        $data = input("post.");
        foreach($data as $k=>$v){
            $res = Db::name("config")->where("code","=",$k)->find();
            if($res){
                Db::name("config")->where("id","=",$res['id'])->update(['value'=>$v]);
            }else{
                Db::name("config")->insert(['code'=>$k,'value'=>$v]);
            }
        }
        Cache::store('redis')->delete('config');
        return success("保存成功");
    }
}