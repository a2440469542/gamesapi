<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/1
 * Time: 20:46
 */
namespace app\common\model;
use app\admin\model\Base;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\AddField;
use think\facade\Cache;
use think\facade\Db;

class User extends Base
{
    protected $pk = 'uid';
    public function getRegTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function getLastLoginTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function add($data){
        $this->setPartition($data['cid']);
        if(isset($data['uid']) && $data['uid'] > 0){
            $info = self::where('uid',"=",$data['uid'])->partition($this->partition)->find();
            if($data['pwd'] !== '' && strlen($data['pwd']) != 32){
                $pwd = md5($data['pwd']);
                if($pwd !== $info['pwd']){
                    $data['pwd'] = $pwd;
                }else{
                    unset($data['pwd']);
                }
            }
            $row = self::where('uid',"=",$data['uid'])->partition($this->partition)->update($data);
        }else{
            $count = self::where('user',"=",$data['user'])->partition($this->partition)->count();
            if($count>0){
                return ['code' => 500, 'msg' => 'O nome de usuário existe'];    //用户已存在
            }
            $data['reg_time'] = time();
            $data['reg_ip'] = get_real_ip__();
            $data['pwd'] = md5($data['pwd']);
            $data['inv_code'] = $this->get_inv_code();
            $row = self::partition($this->partition)->insertGetId($data);
        }
        if($row){
            return ['code' => 0, 'msg' => '操作成功','uid' => $row];
        }else{
            return ['code' => 500, 'msg' => 'o login falhou'];
        }
    }
    public function update_pwd($uid,$pwd){
        $data['pwd'] = md5($pwd);
        return $row = self::where('uid',"=",$uid)->partition($this->partition)->update($data);
    }
    public function set_kol($uid,$is_kol){
        $data['is_kol'] = $is_kol;
        return $row = self::where('uid',"=",$uid)->partition($this->partition)->update($data);
    }
    /**
     * @param $data  Object   用户登录信息
     */
    public function login($data){
        $user = self::where('mobile',$data['mobile'])->partition($this->partition)->find();
        if($user) {
            $password = md5($data['pwd']);
            if ($user['pwd'] == $password){
                do {
                    $token = "api_".bin2hex(random_bytes(16));
                } while (Cache::has($token));
                $user['token'] = $token;
                $updata['last_login_time'] = time();
                $updata['last_login_ip'] = get_real_ip__();
                self::where('uid',$user->uid)->partition($this->partition)->update($updata);
                Cache::set($token, $user->toArray(), 0); // 设置缓存，过期时间为1天
                $old_token = Cache::get($user['cid']."_".$user['uid']);
                if($old_token) {
                    Cache::delete($old_token);
                }
                Cache::set($user['cid']."_".$user['uid'],$token);
                return success('Logem bem sucedido',$user);
            }else{
                return error('O nome de usuário ou a senha é incorrecta, por favor reintroduza!');
            }
        }else{
            return error('o usuário não existe!');
        }
    }
    protected function get_inv_code(){
        $inv_code = strtoupper(randomNumeric(6));
        if(self::where('inv_code',$inv_code)->partition($this->partition)->count() > 0){
            return $this->get_inv_code();
        }
        return $inv_code;
    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public function lists($where=[], $limit=10, $order='uid desc'){
        $list = self::where($where)
            ->partition($this->partition)
            ->order($order)
            ->paginate($limit)->toArray();
        return $list;
    }
    public function getInfo($uid)
    {
        $info = self::where('uid', "=", $uid)
            ->partition($this->partition)
            ->find();
        return $info;
    }
    public function decWater($uid,$num)
    {
        $info = self::where('uid', "=", $uid)
            ->partition($this->partition)
            ->dec("water",$num)->update();
        return $info;
    }
    public function get_inv_info($inv_code){
        $info = self::where('inv_code', "=", $inv_code)
            ->partition($this->partition)
            ->find();
        return $info;
    }
    public function get_child_num($where){
        return self::partition($this->partition)->alias('u')->where($where)->count();
    }
    //创建一个修改用户信息的方法
    public function update_user($data){
        $row = self::where('id',"=",$data['id'])->partition($this->partition)->update($data);
        return $row;
    }
    public function create_rebot($num,$cid){
        $info = self::where('is_rebot',"=",1)->partition($this->partition)->order('uid desc')->find();
        $mobile = 8888801000;
        if($info && (int) $info['mobile'] >= (int) ('55'.$mobile)){
            $mobile = (int) $info['mobile'] + 1;
        }
        $insert = $data = [];
        $time = time();
        $ip = get_real_ip__();
        for($i=0;$i<$num;$i++) {
            $pwd = str_rand(6,2);
            $data[] =[
                'mobile' => $mobile,
                'pwd' => $pwd
            ];
            $insert[] = [
                'cid' => $cid,
                'user' => '55'.$mobile,
                'mobile' => '55'.$mobile,
                'pwd' => md5($pwd),
                'inv_code' => $this->get_inv_code(),
                'money' => 500,
                'reg_time' => $time,
                'reg_ip' => $ip,
                'last_login_time' => $time,
                'last_login_ip' => $ip,
                'is_rebot' => 1
            ];
            $mobile++;
        }
        $row =  self::partition($this->partition)->insertAll($insert);
        if($row){
            return $data;
        }else{
            return [];
        }
    }
    public function team($where){
        $list = self::field('reg_time,inv_code')->where($where)
            ->partition($this->partition)
            ->select()->toArray();
        foreach ($list as &$v){
            $count =  UserStat::field("sum(cz_money) as cz_money, sum(bet_money) as bet_money")
                ->where($where)
                ->partition($this->partition)
                ->find();
            if($count) {
                $v['cz_money'] = round($count['cz_money'] ?? 0.00,2) ?? '0.00';
                $v['bet_money'] = round($count['bet_money'] ?? 0.00,2) ?? '0.00';
            }else{
                $v['cz_money'] = '0.00';
                $v['bet_money'] = '0.00';
            }
            $v['reg_time'] = date('Y-m-d',$v['reg_time']);
        }
        return $list;
    }
}