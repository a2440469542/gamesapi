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

class GoodsOrder extends Base
{
    protected $pk = 'id';
    public function getAddTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function getSendTimeAttr($value): string
    {
        return $value > 0 ? date("Y-m-d H:i:s",$value) : '';
    }
    public static function add($data){
        $row = self::insert($data);
    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $list;
    }

    /**
     * @param $cid  渠道ID
     * 根据渠道获取相应的广告
     */
    public function getList($cid){
        $list = self::where('cid',"=",$cid)->order('id desc')->select()->toArray();
        return $list;
    }
}