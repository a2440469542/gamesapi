<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
/**
 * 首页信息相关接口
 * @Apidoc\Title("首页信息相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(1)
 */
class Index extends Base
{
    /**
     * @Apidoc\Title("轮播图列表")
     * @Apidoc\Desc("轮播图列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("轮播图")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned("data",type="array",desc="轮播图列表",table="cp_ad")
     */
    public function ad()
    {
        $cid = $this->cid;
        $ad = model('app\common\model\Ad')->getList($cid);
        /*foreach($ad as $k=>&$v){
            $v['img'] = SITE_URL.$v['img'];
        }*/
        return success("obter sucesso",$ad); //获取成功
    }
    /**
     * @Apidoc\Title("总投注额")
     * @Apidoc\Desc("轮播图列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("轮播图")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned("",type="int",desc="总投注额")
     */
    public function jack_pot(){
        $cid = $this->cid;
        $num = Cache::store('redis')->get("jack_pot_".$cid);
        if(!$num){
            $num = rand(10000000,50000000);
            Cache::store('redis')->set("jack_pot_".$cid,$num,0);
        }
        $model = model('app\common\model\UserStat',$cid);
        $jack_pot = $model->get_total_bet($cid);
        $jack_pot = number_format($jack_pot+$num,2,'.','');
        return success("obter sucesso",$jack_pot); //获取成功
    }
    /**
     * @Apidoc\Title("当前渠道信息")
     * @Apidoc\Desc("当前渠道信息获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("url", type="string",require=true, desc="当前域名")
     * @Apidoc\Returned("",type="object",desc="渠道相关",table="cp_channel")
     */
    public function channel(){
        $cid = input("cid",0);
        $url = input("url","");
        if($cid == 0 && $url == "") return error("O ID do canal não pode ficar vazio");  //渠道ID不能为空
        $channel = model('app\common\model\Channel')->info($cid,$url);
        if (!$channel) {
            return error("O canal não existe",10001);//渠道不存在
        }
        /*$channel['icon'] = SITE_URL.$channel['icon'];
        $channel['logo'] = SITE_URL.$channel['logo'];*/
        return success("obter sucesso",$channel);    //获取成功
    }
    /**
     * @Apidoc\Title("公共配置获取")
     * @Apidoc\Desc("公共配置获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("公共配置获取")
     * @Apidoc\Returned("",type="object",desc="公共配置")
     */
    public function config(){
        $config = get_config();
        return success("obter sucesso",$config);    //获取成功
    }
    /**
     * @Apidoc\Title("公共配置获取")
     * @Apidoc\Desc("公共配置获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("公共配置获取")
     * @Apidoc\Returned("",type="object",desc="公共配置")
     */
    public function video(){
        $video = app('app\common\model\Video')->getList();
        if(empty($video)) return error("O vídeo não existe",10001);//视频不存在
        $key = Cache::store('redis')->get("video_key",0);
        if($key > count($video)-1) $key = 0;
        foreach($video as $k=>$v){
            if($k == $key){
                $info = $v;
                Cache::store('redis')->set("video_key",$key+1,0);
                break;
            }
        }
        $config = get_config();
        $data['url'] = $info['url'];
        $data['tiktok_url'] = $config['tiktok_url'];
        $data['kwain_url'] = $config['kwain_url'];
        $data['insgram_url'] = $config['insgram_url'];
        $data['tg_kefu'] = '';
        $data['whatsapp_kefu'] = '';
        $data['video_rule'] = $config['video_rule'];
        if($config['tg_kefu']){
            $randomKey = array_rand($config['tg_kefu']);
            $data['tg_kefu'] = $config['tg_kefu'][$randomKey];
        }
        if($config['whatsapp_kefu']){
            $randomKey = array_rand($config['whatsapp_kefu']);
            $data['whatsapp_kefu'] = $config['whatsapp_kefu'][$randomKey];
        }
        return success("obter sucesso",$data);    //获取成功
    }
}
