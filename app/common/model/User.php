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
    public function cash_user($where=[], $limit=10, $order='uid desc'){
        $list = self::alias('u')
            ->field('u.*,SUM(c.money) AS cash_money')
            ->leftJoin("cp_cash PARTITION({$this->partition}) `c`","u.uid = c.uid")
            ->where($where)
            ->partition($this->partition)
            ->order($order)
            ->group('u.uid')
            ->having('SUM(c.money) > 0')
            ->paginate($limit)->toArray();
        return $list;
    }
    public function getInfo($uid)
    {
        $info = self::where('uid', "=", $uid)
            ->partition($this->partition)
            ->find();
        $level = app('app\common\model\Level')->level($info['exp']);
        if($level && $level != $info['level']){
            self::where('uid', "=", $uid)
                ->partition($this->partition)
                ->update(['level'=>$level]);
            $info['level'] = $level;
        }
        return $info;
    }
    public function getMobile($uid)
    {
        $info = self::where('uid', "=", $uid)->partition($this->partition)->value('user');
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
        $row = self::where('uid',"=",$data['uid'])->partition($this->partition)->update($data);
        return $row;
    }
    public function create_rebot($num,$cid){
        $info = self::where('is_rebot',"=",1)->partition($this->partition)->order('uid desc')->find();
        $mobile = 558888801000;
        if($info && (int) $info['mobile'] >= $mobile){
            $mobile = (int) $info['mobile'] + 1;
        }
        $insert = $data = [];
        $time = time();
        $ip = get_real_ip__();
        for($i=0;$i<$num;$i++) {
            $pwd = str_rand(6,2);
            $data[] =[
                'mobile' => substr($mobile, 2),
                'pwd' => $pwd
            ];
            $insert[] = [
                'cid' => $cid,
                'user' => $mobile,
                'mobile' => $mobile,
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
    public function team($where,$limit){
        $list = self::field('uid,reg_time,inv_code')->where($where)
            ->partition($this->partition)
            ->paginate($limit)->order('uid desc')->toArray();
        foreach ($list['data'] as &$v){
            $count =  UserStat::field("sum(cz_money) as cz_money, sum(bet_money) as bet_money")
                ->where("uid","=",$v['uid'])
                ->partition($this->partition)
                ->find();
            if($count) {
                $v['cz_money'] = round($count['cz_money'] ?? 0.00,2) ?? '0.00';
                $v['bet_money'] = round($count['bet_money'] ?? 0.00,2) ?? '0.00';
            }else{
                $v['cz_money'] = '0.00';
                $v['bet_money'] = '0.00';
            }
            $v['date'] = date('Y-m-d',strtotime($v['reg_time']));
        }
        return $list;
    }
    //统计注册人数
    public function reg_num($cid,$date=''){
        $this->setPartition($cid);
        if($date){
            $sttime = strtotime($date);
            $ettime = $sttime + 24*60*60;
            return self::where('is_rebot','=',0)
                ->where('reg_time','between',[$sttime,$ettime])
                ->partition($this->partition)->count();
        }else{
            return self::where('is_rebot','=',0)->partition($this->partition)->count();
        }
    }
    //统计用户余额
    public function user_money($cid){
        $this->setPartition($cid);
        return self::where('is_rebot','=',0)->partition($this->partition)->sum('money');
    }
    public function get_child($uid){
        $list = self::field('uid,cid,pid,ppid,pppid,user,mobile,money,lock_money,water')->where('pid','=',$uid)
            ->partition($this->partition)
            ->select()->toArray();
        return $list;
    }
    public function update_data($where,$data){
        $row = self::where($where)->partition($this->partition)->update($data);
        return $row;
    }

    public function bind_child_user($where,$data){
        $row = self::where($where)->partition($this->partition)->update($data);
        return $row;
    }
    public function bind_user($uid,$data){
        $row = self::where('pid','=',$uid)->where('uid','=',$uid)->partition($this->partition)->update($data);
        return $row;
    }
}