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

class Wages extends Base
{
    protected $pk = 'id';

    public function add($user,$money,$type=1,$wages_type=1){
        $data = [
            'cid' => $user['cid'],
            'uid' => $user['uid'],
            'mobile' => $user['mobile'],
            'inv_code' => $user['inv_code'],
            'money' => $money,
            'type' => $type,
            'wages_type' => $wages_type,
            'add_time' => time(),
        ];
        self::partition($this->partition)->insert($data);
    }

    /**
     * @param $uid    int  用户ID
     * @return array  工资信息
     */
    public function get_money($uid){
        $data['bozhu'] = self::where('uid',"=",$uid)->where("type","=",1)->partition($this->partition)->sum('money');
        $data['daili'] = self::where('uid',"=",$uid)->where("type","=",2)->partition($this->partition)->sum('money');
        return $data;
    }
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->partition($this->partition)
            ->paginate($limit)->toArray();
        return $list;
    }
    //获取工资领取人数
    public function wages_num(){
        $data['bozhu'] = self::where('type','=',1)->partition($this->partition)->group('uid')->count();
        $data['daili'] = self::where('type','=',2)->partition($this->partition)->group('uid')->count();
        return $data;
    }
    //获取工资领取总数
    public function wages_money(){
        $data['bozhu'] = self::where("type","=",1)->partition($this->partition)->sum('money');
        $data['daili'] = self::where("type","=",2)->partition($this->partition)->sum('money');
        return $data;
    }
}