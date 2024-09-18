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

class Racs extends Base
{
    protected $pk = 'id';
    protected $json = ['channel'];
    protected $jsonAssoc = true;
    public static function add($data){
        if(isset($data['id']) && $data['id'] > 0){
            $row = self::where('id',"=",$data['id'])->update($data);
        }else{
            $row = self::insert($data);
        }
        if($row){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public function lists($where=[], $limit=10, $order='id desc',$cid=0){
        if($cid > 0){
            $list = self::where($where)
                ->whereRaw('JSON_CONTAINS(channel, "'.$cid.'")')
                ->order($order)
                ->paginate($limit)->toArray();
        }else{
            $list = self::where($where)
                ->order($order)
                ->paginate($limit)->toArray();
        }
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