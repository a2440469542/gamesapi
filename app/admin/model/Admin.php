<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/1
 * Time: 20:46
 */
namespace app\admin\model;
use think\captcha\facade\Captcha;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\AddField;
use think\facade\Cache;

class Admin extends Base
{
    protected $pk = 'id';
    protected $json = ['rule'];
    /**
     * @Field("id,aid,user_name,role_id,password,nickname,mobile,avatar,status")
     */
    public static function add(){

    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public static function lists($where=[], $limit=10, $order='id desc'){
        $list = self::alias("a")
            ->field("a.*,r.name")
            ->leftJoin('roles r','a.rid = r.rid')
            ->where($where)
            ->order($order)
            ->paginate($limit);
        return $list;
    }
    /**
     * @Field("id,user_name,nickname")
     * @AddField("token",type="string",desc="用户token")
     */
    public function login($data){
        //if (!Captcha::check($data['code'])) return error('验证码不正确');
        $admin = self::where('user_name',$data['user_name'])->find();
        if($admin) {
            if ($admin['status'] == 2) return error('帐号被禁用，请联系管理员');
            $password = md5(md5($data['password']) . $admin['salt']);
            if ($admin['password'] == $password){
                do {
                    $token = bin2hex(random_bytes(32));
                } while (Cache::has($token));
                $admin['token'] = $token;
                $updata['last_login_at'] = date("Y-m-d H:i:s",time());
                $updata['last_login_ip'] = request()->ip();
                self::where('id',$admin->id)->update($updata);
                Cache::set($token, $admin->toArray(), 24*60*60); // 设置缓存，过期时间为1天
                session('admin', $admin);
                return success('登录成功',$admin);
            }else{
                return error('用户名或者密码错误，重新输入!');
            }
        }else{
            return error('用户不存在!');
        }
    }
}