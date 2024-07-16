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

class Order extends Base
{
    protected $pk = 'id';
    public function add($cid,$uid,$order_sn,$money,$gifts=0){
        $data = [
            'uid' => $uid,
            'cid' => $cid,
            'order_sn' => $order_sn,
            'money' => $money,
            'gifts' => $gifts,
            'status' => 1,
            'add_time' => time(),
        ];
        return self::partition($this->partition)->insertGetId($data);
    }
    public function getAddTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::alias("o")
            ->field("o.*,u.mobile,u.inv_code")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","o.uid = u.uid")
            ->where($where)
            ->order($order);
        if($this->partition){
            $list = self::alias("o")
                ->field("o.*,u.mobile,u.inv_code")
                ->leftJoin("cp_user PARTITION({$this->partition}) `u`","o.uid = u.uid")
                ->where($where)
                ->order($order)->partition($this->partition)
                ->paginate($limit)->toArray();
        }else{
            $list = self::alias("o")
                ->field("o.*,u.mobile,u.inv_code")
                ->leftJoin("cp_user `u`","o.uid = u.uid")
                ->where($where)
                ->order($order)
                ->paginate($limit)->toArray();
        }
        return $list;
    }
    public function getList($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->partition($this->partition)
            ->paginate($limit)->toArray();
        return $list;
    }
    public function getInfo($order_sn){
        $row = self::where('order_sn',"=",$order_sn)->partition($this->partition)->find();
        return $row;
    }
    public function update_order($data){
        $row = self::where('id',"=",$data['id'])->partition($this->partition)->update($data);
        return $row;
    }
}