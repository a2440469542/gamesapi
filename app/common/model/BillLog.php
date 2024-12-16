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

class BillLog extends Base
{
    protected $pk = 'id';
    public function getAddTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function addIntvie(){}
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::alias("b")
            ->field("b.*,c.name")
            ->leftJoin("cp_channel `c`","b.cid = c.cid")
            ->where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $list;
    }
}