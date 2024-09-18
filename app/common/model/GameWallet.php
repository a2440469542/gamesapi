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

class GameWallet extends Base
{
    protected $pk = 'id';
    /**
     * @param $cid          int     渠道ID
     * @param $uid          int     用户ID
     * @param $pid       string     游戏平台ID
     * @param $lid       string     游戏平台线路ID
     * @param $user         int     游戏平台用户名
     * @param $player_id    int     游戏平台ID
     * @param $is_login     int     是否需要登录
     * @return mixed
     */
    public function add($cid,$pid,$lid,$uid,$mobile,$inv_code,$deposit,$dorder_sn,$d_tx): mixed
    {
        $data = [
            'cid'       => $cid,
            'uid'       => $uid,
            'pid'       => $pid,
            'lid'       => $lid,
            'mobile'    => $mobile,
            'inv_code'  => $inv_code,
            'deposit'   => $deposit,
            'dorder_sn' => $dorder_sn,
            'd_tx'      => $d_tx,
            'add_time'  => date("Y-m-d H:i:s")
        ];
        $this->setPartition($cid);
        return self::partition($this->partition)->insertGetId($data);
    }
    public function edit($id,$data){
        return self::where("id","=",$id)->partition($this->partition)->update($data);
    }
    public function getList($where=[], $limit=10, $order='id desc'){
        $list = self::alias("gl")
            ->field("gl.*,u.inv_code")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","gl.uid = u.uid")
            ->where($where)
            ->order($order)
            ->partition($this->partition)
            ->paginate($limit)->toArray();
        return $list;
    }
    public function getInfo($uid,$pid){
        $info = self::where("uid","=",$uid)
            ->where("pid","=",$pid)
            ->where("status","=",0)
            ->partition($this->partition)->find();
        return $info;
    }
}