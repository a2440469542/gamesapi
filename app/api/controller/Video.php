<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
/**
 * 视频相关接口
 * @Apidoc\Title("视频相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(15)
 */
class Video extends Base
{
    /**
     * @Apidoc\Title("视频相关信息获取")
     * @Apidoc\Desc("视频相关信息获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("视频相关信息获取")
     * @Apidoc\Returned("url",type="string",desc="视频地址")
     * @Apidoc\Returned("tiktok_url",type="string",desc="TIKTOK")
     * @Apidoc\Returned("kwain_url",type="string",desc="KWAI")
     * @Apidoc\Returned("insgram_url",type="string",desc="INSGRAM")
     * @Apidoc\Returned("tg_kefu",type="string",desc="tg客服")
     * @Apidoc\Returned("whatsapp_kefu",type="string",desc="whats客服")
     * @Apidoc\Returned("video_rule",type="string",desc="规则")
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
