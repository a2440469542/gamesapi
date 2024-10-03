<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

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
     * @Apidoc\Param("code", type="string",require=true, desc="验证码")
     * @Apidoc\Param("inv_code", type="string",require=true, desc="邀请码")
     * @Apidoc\Returned(type="object",desc="用户相关字段",table="cp_user")
     */
    public function register(){
        $mobile = input("mobile");
        $pwd = input("pwd");
        $inv_code = input("inv_code");
        $code = input("code",'');
        if(empty($mobile) && empty($pwd)){
            return error("Erro de parâmetro",500);   //参数错误
        }
        if(!isPhoneNumber($mobile)){
            return error("Número de telefone incorreto",500);    //手机号格式错误
        }
        $config = get_config();
        if(isset($config['sms_open']) && $config['sms_open'] == 1 && $mobile){
            if(empty($code)) {return error("Por favor, preenche o código de verificação",500);}  //请填写验证码
            $cache_code = Cache::get('code_'.$mobile);
            if($code != $cache_code){
                return error("Erro de código de verificação");  //验证码错误
            }
            Cache::delete('code_'.$mobile);
        }
        $ip = get_real_ip__();
        $black = app('app\common\model\BankBlack')->where('pix',"=",$ip)->count();
        if($black > 0) {return error("Refusa-se a registrar",500);}  //ip禁止
        $data = [
            'cid' => $this->cid,
            'user' => $mobile,
            'mobile' => $mobile,
            'pwd' => $pwd,
            'last_login_time' => time(),
            'last_login_ip' => get_real_ip__(),
        ];
        $row = app('app\common\logic\UserLogic')->register($inv_code,$data,$this->cid);
        if($row['code'] > 0) {
            return error($row['msg'],$row['code']);   //注册失败
        }
        $data['token'] = $row['token'];
        $data['uid'] = $row['uid'];
        Cache::set($row['token'], $data, 0); // 设置缓存，过期时间为1天
        return success('Registro bem sucedido',$data);   //注册成功
    }
    /**
     * @Apidoc\Title("用户邮箱注册接口")
     * @Apidoc\Desc("用户邮箱注册接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户邮箱注册")
     * @Apidoc\Param("email", type="string",require=true, desc="邮箱")
     * @Apidoc\Param("pwd", type="string",require=true, desc="密码")
     * @Apidoc\Param("code", type="string",require=true, desc="验证码")
     * @Apidoc\Param("inv_code", type="string",require=true, desc="邀请码")
     * @Apidoc\Returned(type="object",desc="用户相关字段",table="cp_user")
     */
    public function email_register(){
        $email = input("email");
        $pwd = input("pwd");
        $inv_code = input("inv_code");
        $code = input("code",'');
        if(empty($email) && empty($pwd)){
            return error("Erro de parâmetro",500);   //参数错误
        }
        if(!isEmail($email)) {return error("E-mail incorreto",500);}  //邮箱格式错误
        $ip = get_real_ip__();
        $black = app('app\common\model\BankBlack')->where('pix',"=",$ip)->count();
        if($black > 0) {return error("Refusa-se a registrar",500);}  //ip禁止
        $data = [
            'cid' => $this->cid,
            'user' => $email,
            'email' => $email,
            'pwd' => $pwd,
            'last_login_time' => time(),
            'last_login_ip' => get_real_ip__(),
        ];
        $row = app('app\common\logic\UserLogic')->register($inv_code,$data,$this->cid);
        if($row['code'] > 0) {
            return error($row['msg'],$row['code']);   //注册失败
        }
        $data['token'] = $row['token'];
        $data['uid'] = $row['uid'];
        Cache::set($row['token'], $data, 0); // 设置缓存，过期时间为1天
        return success('Registro bem sucedido',$data);   //注册成功
    }
    /**
     * @Apidoc\Title("短信注册登录验证码")
     * @Apidoc\Desc("短信注册登录验证码")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("短信注册登录验证码")
     * @Apidoc\Param("mobile", type="string",require=false, desc="手机号")
     */
    public function get_code(){
        $mobile = input("mobile");
        if(empty($mobile)){
            return error("Erro de parâmetro",500);   //缺少参数
        }
        $code = rand(100000,999999);
        $row = app('app\service\sms\Sms')->send_sms($mobile,$code);
        if($row['code'] == 200){
            Cache::set('code_'.$mobile,$code,300);
            return success("Enviado com sucesso");      //发送成功
        }else{
            return error($row['message']);
        }
    }
    /**
     * @Apidoc\Title("用户登录")
     * @Apidoc\Desc("用户登录接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户登录接口")
     * @Apidoc\Param("mobile", type="string",require=false, desc="手机号")
     * @Apidoc\Param("email", type="string",require=false, desc="邮箱")
     * @Apidoc\Param("pwd", type="string",require=false, desc="密码")
     * @Apidoc\Returned(type="object",desc="用户相关信息",table="cp_user")
     */
    public function user_login(){
        $mobile = input("mobile");
        $email = input("email");
        $pwd = input("pwd");
        if(empty($pwd)){
            return error("Erro de parâmetro",500);   //缺少参数
        }
        if(empty($mobile) && empty($email)){
            return error("Erro de parâmetro",500);   //缺少参数
        }
        $data = [
            'user' => $mobile ?? $email,
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
