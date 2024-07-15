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

class BankBlack extends Base
{
    protected $pk = 'id';

    public function add($cid,$uid,$type,$mobile,$pix,$name,$id=0){
        $data = [
            'uid' => $uid,
            'cid' => $cid,
            'type' => $type,
            'mobile' => $mobile,
            'pix' => $pix,
            'name' => $name,
            'add_time' => time(),
        ];
        if($id>0){
            return self::where("id","=",$id)->update($data);
        }else{
            return self::insertGetId($data);
        }

    }
    public function getList($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $list;
    }
    public function getInfo($cid,$uid){
        $row = self::where('cid',"=",$cid)->where("uid","=",$uid)->find();
        return $row;
    }
}