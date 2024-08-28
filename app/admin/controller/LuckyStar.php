<?php

namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\LuckyStar as LuckyStarModel;
use app\common\model\StarFile;
use think\facade\Db;

/**
 * 幸运星配置
 * @Apidoc\Title("幸运星配置")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(2)
 */
class LuckyStar extends Base
{
    /**
     * @Apidoc\Title("活动信息")
     * @Apidoc\Desc("活动信息")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("活动")
     * @Apidoc\Returned("star_rule",type="string",desc="参与规则")
     * @Apidoc\Returned("star_file",type="array",desc="数据文件",table="cp_star_file")
     * @Apidoc\Returned("star_phrase",type="array",desc="活动推广语",table="cp_star_phrase")
     * @Apidoc\Returned("star_coin",type="array",desc="活动彩金档位",table="cp_star_coin")
     */
    public function index(){
        $data['star_rule'] = Db::name("config")->where("code","=",'star_rule')->value('value');
        $data['star_file'] = Db::name("star_file")->select();
        $data['star_phrase'] = Db::name("star_phrase")->select();
        $data['star_coin'] = Db::name("star_coin")->select();
        return success("获取成功",$data);
    }
    /**
     * @Apidoc\Desc("幸运星规则保存")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("幸运星规则保存")
     * @Apidoc\Param("star_rule",type="string",desc="幸运星规则保存")
     */
    public function add(){
        $data = input("post.");
        if(!isset($data['star_num']) || !$data['star_num']){
            return error("请选择活动参数次数");
        }
        foreach($data as $k=>$v){
            $res = Db::name("config")->where("code","=",$k)->find();
            if($res){
                Db::name("config")->where("id","=",$res['id'])->update(['value'=>$v]);
            }else{
                Db::name("config")->insert(['code'=>$k,'value'=>$v]);
            }
        }
        cache('config',null);
        return success("保存成功");
    }
}