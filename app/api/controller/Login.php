<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;

/**
 * 登录注册相关
 * @Apidoc\Title("登录注册相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(5)
 */
class Login extends Base
{
    /**
     * @Apidoc\Title("用户注册接口")
     * @Apidoc\Desc("用户注册接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户注册")
     * @Apidoc\Param("mobile", type="string",require=true, desc="手机号")
     * @Apidoc\Param("pwd", type="string",require=true, desc="密码")
     * @Apidoc\Param("inv_code", type="string",require=true, desc="邀请码")
     * @Apidoc\Returned(type="object",desc="用户相关字段",table="cp_user")
     */
    public function register(){
        $mobile = input("mobile");
        $pwd = input("pwd");
        $inv_code = input("inv_code");
        if(empty($mobile) || empty($pwd)){
            return error("Erro de parâmetro",500);   //参数错误
        }
        if(!isPhoneNumber($mobile)){
            return error("Número de telefone incorreto",500);    //手机号格式错误
        }
        $data = [
            'cid' => $this->cid,
            'user' => $mobile,
            'mobile' => $mobile,
            'pwd' => $pwd,
            'last_login_time' => time(),
            'last_login_ip' => get_real_ip__(),
        ];
        $UserModel = model('app\common\model\User',$this->cid);
        $user = null;
        if($inv_code){
            $user = $UserModel->get_inv_info($inv_code);    //获取上级的信息
            if($user){
                $data['pid']   = $user['uid'];
                $data['ppid']  = $user['pid'];
                $data['pppid'] = $user['ppid'];
            }
        }
        $row = $UserModel->add($data);
        if($row['code'] > 0){
            return error($row['msg'],500);
        }
        $uid = $row['uid'];
        //数据统计
        if($user != null){
            $UserStatModel = model('app\common\model\UserStat',$this->cid);
            $stat = ['invite_user' => 1];
            $UserStatModel->add($user,$stat);
        }
        do {
            $token = "api_".bin2hex(random_bytes(16));
        } while (Cache::has($token));
        $data['token'] = $token;
        $data['uid'] = $uid;
        Cache::set($token, $data, 0); // 设置缓存，过期时间为1天
        return success('Registro bem sucedido',$data);   //注册成功
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
            return error("Erro de parâmetro",500);   //缺少参数
        }
        $data = [
            'mobile' => $mobile,
            'pwd' => $pwd,
        ];
        $UserModel = model('app\common\model\User',$this->cid);
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
