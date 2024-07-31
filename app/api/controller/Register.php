<?php

namespace app\api\controller;
use app\BaseController;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;

/**
 * 测试账号注册相关
 * @Apidoc\Title("测试账号注册相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(10)
 */
class Register extends BaseController
{
    /**
     * @Apidoc\Title("测试账号注册接口")
     * @Apidoc\Desc("测试账号注册接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("测试账号注册")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("num", type="int",require=true, desc="用户数量")
     * @Apidoc\Returned("mobile",type="string",desc="试玩手机号")
     * @Apidoc\Returned("pwd",type="string",desc="试玩密码")
     */
    public function register(){
        $name = input("name");
        $num = input("num",1);
        if(!$name){
            return  error("缺少参数name");
        }
        $channel = app('app\common\model\Channel');
        $cid = $channel->where('name',$name)->value('id');
        if(!$cid){
            return  error("渠道不存在");
        }
        $userModel = app('app\common\model\User');
        $userModel->setPartition($cid);
        $res = $userModel->create_rebot($num,$cid);
        return success('Registro bem sucedido',$res);   //注册成功
    }
}
