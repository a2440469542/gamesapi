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

class BoxLog extends Base
{
    protected $pk = 'id';
    protected $json = ['task'];
    protected $jsonAssoc = true;
    public static function add($cid,$uid,$task,$money=0,$num=0){
        $data = [
            'cid' => $cid,
            'uid' => $uid,
            'task' => $task,
            'money' => $money,
            'num' => $num,
            'add_time' => time()
        ];
        return self::insert($data);
    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->select()->toArray();
        return $list;
    }
    public function getLastTask($cid,$uid){
        $where = [
            ['uid','=',$uid],
            ['cid','=',$cid]
        ];
        $info = self::where($where)->order('id desc,add_time desc')->find();
        if($info){
            return $info->toArray();
        }
        return [];
    }
}