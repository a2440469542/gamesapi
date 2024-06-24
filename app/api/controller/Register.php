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
     * @Apidoc\Title("用户注册接口")
     * @Apidoc\Desc("用户注册接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户注册")
     * @Apidoc\Param("mobile", type="string",require=true, desc="手机号")
     * @Apidoc\Param("pwd", type="string",require=true, desc="密码")
     * @Apidoc\Param("channel", type="string",require=true, desc="渠道名称")
     */
    public function register(){
        $mobile = input("mobile");
        $pwd = input("pwd");
        $channel_code = input("channel",'');
        if(empty($mobile) || empty($pwd) || empty($channel_code)){
            return error("Erro de parâmetro",500);   //参数错误
        }
        if(!isPhoneNumber($mobile)){
            return error("Número de telefone incorreto",500);    //手机号格式错误
        }
        $channel = model('app\common\model\Channel')->getDetail($channel_code);
        if(empty($channel)) {
            return error("O canal não existe",500);   //参数错误
        }
        $data = [
            'cid' => $channel['cid'],
            'user' => $mobile,
            'mobile' => $mobile,
            'pwd' => $pwd,
            'last_login_time' => time(),
            'last_login_ip' => get_real_ip__(),
            'is_rebot' => 1
        ];
        $UserModel = model('app\common\model\User',$channel['cid']);
        $row = $UserModel->add($data);
        if($row['code'] > 0){
            return error($row['msg'],500);
        }
        return success('Registro bem sucedido',$data);   //注册成功
    }
}
