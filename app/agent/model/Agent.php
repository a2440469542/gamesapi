<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/1
 * Time: 20:46
 */
namespace app\agent\model;
use app\admin\model\Base;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\AddField;
use think\facade\Cache;
use think\facade\Db;

class Agent extends Base
{
    protected $pk = 'id';
    protected $json = ['pid'];
    protected $jsonAssoc = true;
    public function getAddTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public static function register($data){
        $count = self::where("mobile",'=',$data["mobile"])->count();
        if($count>0){
            return error("用户已存在");
        }
        $ip = get_real_ip__();
        $data['pwd'] = md5($data['pwd']);
        $data['reg_time'] = date('Y-m-d H:i:s',time());
        $data['reg_ip'] = $ip;
        $data['last_login_time'] = date('Y-m-d H:i:s',time());
        $data['last_login_ip'] = $ip;
        $row = self::insertGetId($data);
        if($row){
            return $data;
        }else{
            return false;
        }
    }
    /**
     * @param $data  Object   用户登录信息
     */
    public function login($data){
        $user = self::where('mobile',$data['mobile'])->find();
        if($user) {
            $password = md5($data['pwd']);
            if ($user['pwd'] == $password){
                do {
                    $token = "agent_".bin2hex(random_bytes(16));
                } while (Cache::has($token));
                $user['token'] = $token;
                $updata['last_login_time'] = date('Y-m-d H:i:s',time());
                $updata['last_login_ip'] = get_real_ip__();
                self::where('id',$user->id)->update($updata);
                Cache::set($token, $user->toArray(), 0); // 设置缓存，过期时间为1天
                $old_token = Cache::get($user['id']);
                if($old_token) {
                    Cache::delete($old_token);
                }
                Cache::set($user['id'],$token);
                return success('登录成功',$user);
            }else{
                return error('密码错误');
            }
        }else{
            return error('账号不存在');
        }
    }
}