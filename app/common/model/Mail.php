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

class Mail extends Base
{
    protected $pk = 'id';
    public function add($cid,$uid,$content,$money=0){
        $data = [
            'uid' => $uid,
            'cid' => $cid,
            'content' => $content,
            'money' => $money,
            'date' => date('Y-m-d H:i:s',time()),
        ];
        return self::partition($this->partition)->insertGetId($data);
    }

    public function getList($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $list;
    }
}