<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;

/**
 * 视频管理相关接口
 * @Apidoc\Title("视频管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(20)
 */
class Video extends Base{
    /**
     * @Apidoc\Title("视频列表")
     * @Apidoc\Desc("视频列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("视频")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="视频列表",table="cp_video")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id desc');
        $VideoModel = app('app\common\model\Video');
        $list = $VideoModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑视频")
     * @Apidoc\Desc("添加编辑视频")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("视频")
     * @Apidoc\Param("",type="array",table="cp_video")
     */
    public function edit(){
        $data = input("post.");
        $VideoModel = app('app\common\model\Video');
        return $VideoModel->add($data);
    }

    /**
     * @Apidoc\Title("删除视频")
     * @Apidoc\Desc("删除视频")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("视频")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的视频ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $VideoModel = app('app\common\model\Video');
        $res = $VideoModel->where('id', $id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
    /**
     * @Apidoc\Title("获取跳转和客服链接，活动规则相关配置")
     * @Apidoc\Desc("获取跳转和客服链接，活动规则相关配置")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("获取跳转和客服链接，活动规则相关配置")
     * @Apidoc\Returned("tiktok_url",type="string",desc="TIKTOK链接")
     * @Apidoc\Returned("kwain_url",type="string",desc="KWAIN链接")
     * @Apidoc\Returned("insgram_url",type="string",desc="insgram链接")
     * @Apidoc\Returned("tg_kefu",type="array",desc="telegram客服")
     * @Apidoc\Returned("whatsapp_kefu",type="array",desc="whatsapp客服")
     * @Apidoc\Returned("video_rule",type="string",desc="活动规则")
     */
    public function get_config(){
        $info = Db::name('config')->select();
        $config = [];
        foreach($info as $k=>$v){
            if($v['code'] == 'tiktok_url' || $v['code'] == 'kwain_url' || $v['code'] == 'insgram_url' || $v['code'] == 'video_rule'){
                $config[$v['code']] = $v['value'];
            }elseif($v['code'] == 'tg_kefu' || $v['code'] == 'whatsapp_kefu'){
                $config[$v['code']] = json_decode($v['value'], true);
            }
        }
        return success("获取成功", $config);
    }
    /**
     * @Apidoc\Title("跳转和客服链接，活动规则相关配置")
     * @Apidoc\Desc("跳转和客服链接，活动规则相关配置")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("跳转和客服链接，活动规则相关配置")
     * @Apidoc\Param("tiktok_url",type="string",desc="TIKTOK链接")
     * @Apidoc\Param("kwain_url",type="string",desc="KWAIN链接")
     * @Apidoc\Param("insgram_url",type="string",desc="insgram链接")
     * @Apidoc\Param("tg_kefu",type="array",desc="telegram客服")
     * @Apidoc\Param("whatsapp_kefu",type="array",desc="whatsapp客服")
     * @Apidoc\Param("video_rule",type="string",desc="活动规则")
     */
    public function set_config(){
        $data = input("post.");
        foreach($data as $k=>$v){
            if($k == "tg_kefu" || $k == 'whatsapp_kefu'){
                $v = json_encode($v);
            }
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