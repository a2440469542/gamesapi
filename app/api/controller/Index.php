<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
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
        foreach($ad as $k=>&$v){
            $v['img'] = SITE_URL.$v['img'];
        }
        return success("obter sucesso",$ad); //获取成功
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
        $channel['icon'] = SITE_URL.$channel['icon'];
        $channel['logo'] = SITE_URL.$channel['logo'];
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
}
