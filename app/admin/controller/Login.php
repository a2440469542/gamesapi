<?php
namespace app\admin\controller;
use app\BaseController;
use think\captcha\facade\Captcha;
use hg\apidoc\annotation as Apidoc;
use think\facade\Session;

/**
 * 标题也可以这样直接写
 * @Apidoc\Title("登录相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(2)
 */
class Login extends BaseController{
    public function index(){
        return view();
    }
    /**
     * @Apidoc\Title("获取验证码")
     * @Apidoc\Desc("获取验证码，直接调用链接显示验证码")
     * @Apidoc\Method("GET")
     * @Apidoc\Author("")
     * @Apidoc\Tag("验证码")
     */
    public function verify(){
        return Captcha::create('admin');
    }
    /**
     * @Apidoc\Title("后台用户登录")
     * @Apidoc\Desc("后台用户登录")
     * @Apidoc\Author("")
     * @Apidoc\Tag("登录")
     * @Apidoc\Method("POST")
     * @Apidoc\Param("code", type="string",require=true, desc="验证码")
     * @Apidoc\Param("user_name", type="string",require=true, desc="用户名")
     * @Apidoc\Param("password", type="string",require=true, desc="密码")
     * @Apidoc\Returned(type="object",ref="app\admin\model\Admin\login")
     */
    public function login(){
        $data = input('post.');
        return app("app\admin\model\Admin")->login($data);
    }
}