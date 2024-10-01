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

class Cash extends Base
{
    protected $pk = 'id';
    public function add($cid,$uid,$order_sn,$type,$account,$pix,$name,$money,$real_money){
        $data = [
            'uid' => $uid,
            'cid' => $cid,
            'order_sn' => $order_sn,
            'type' => $type,
            'account' => $account,
            'pix' => $pix,
            'name' => $name,
            'money' => $money,
            'real_money' => $real_money,
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
        if($this->partition){
            $list = self::alias("c")
                ->field("c.*,u.mobile,u.inv_code,ch.name as cname")
                ->leftJoin("cp_user PARTITION({$this->partition}) `u`","c.uid = u.uid")
                ->leftJoin("cp_channel ch",'c.cid = ch.cid')
                ->where($where)
                ->order($order)->partition($this->partition)->paginate($limit)->toArray();
        }else{
            $list = self::alias("c")
                ->field("c.*,u.mobile,u.inv_code,ch.name as cname")
                ->leftJoin("cp_user `u`","c.uid = u.uid")
                ->leftJoin("cp_channel ch",'c.cid = ch.cid')
                ->where($where)
                ->order($order)->paginate($limit)->toArray();
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
    //获取当前用户是否有提现记录
    public function hasCashRecord($uid): bool
    {
        $count = self::where('uid', '=', $uid)->where('status', 'BETWEEN', [0,1])->partition($this->partition)->count();
        return $count > 0;
    }
    //获取当日用户提现次数以及提现金额
    public function cash_total($uid){
        $time = strtotime(date("Y-m-d",time()));
        $total = self::field('COUNT(id) as num,SUM(money) as money')
            ->where("uid","=",$uid)
            ->where('status', '=', 2)
            ->where('add_time','>=',$time)
            ->partition($this->partition)->find();
        return $total;
    }
    public function get_cash_num($uid){
        $count = self::where('uid',"=",$uid)->where("status",'=',2)->partition($this->partition)->count();
        return $count;
    }
}