<?php

namespace app\agent\controller;
use app\BaseController;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;

/**
 * 登录注册相关
 * @Apidoc\Title("登录注册相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(5)
 */
class Login extends BaseController
{
    /**
     * @Apidoc\Title("代理注册接口")
     * @Apidoc\Desc("代理注册接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("代理注册")
     * @Apidoc\Param("mobile", type="string",require=true, desc="手机号")
     * @Apidoc\Param("pwd", type="string",require=true, desc="密码")
     * @Apidoc\Returned(type="object",desc="用户相关字段",table="cp_agent")
     */
    public function register(){
        $mobile = input("mobile");
        $pwd = input("pwd");
        $inv_code = input("inv_code");
        if(empty($mobile) || empty($pwd)){
            return error("参数错误",500);   //参数错误
        }
        if(!isPhoneNumber($mobile)){
            return error("手机号格式错误",500);    //手机号格式错误
        }
        $data = ['mobile' => $mobile, 'pwd' => $pwd];
        $UserModel = app('app\agent\model\Agent');
        $row = $UserModel->register($data);
        if($row === false){
            return error('注册失败',500);
        }
        //数据统计
        do {
            $token = "agent_".bin2hex(random_bytes(16));
        } while (Cache::has($token));
        $data['token'] = $token;
        Cache::set($token, $data, 0); // 设置缓存，过期时间为1天
        return success('注册成功',$data);   //注册成功
    }
    /**
     * @Apidoc\Title("用户登录")
     * @Apidoc\Desc("用户登录接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户登录接口")
     * @Apidoc\Param("mobile", type="string",require=false, desc="手机号")
     * @Apidoc\Param("pwd", type="string",require=false, desc="密码")
     * @Apidoc\Returned(type="object",desc="用户相关信息",table="cp_user")
     */
    public function user_login(){
        $mobile = input("mobile");
        $pwd = input("pwd");
        if(empty($mobile) || empty($pwd)){
            return error("缺少参数",500);   //缺少参数
        }
        $data = [
            'mobile' => $mobile,
            'pwd' => $pwd,
        ];
        $UserModel = app('app\agent\model\Agent');
        return $UserModel->login($data);
    }
    /**
     * @Apidoc\Title("用户退出登录")
     * @Apidoc\Desc("用户退出登录接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户退出登录接口")
     */
    public function logout(){
        $token = $this->request->header('authorization');
        Cache::delete($token); // 设置缓存，过期时间为1天
        return success('Logout successfully');   //注销成功
    }
}
