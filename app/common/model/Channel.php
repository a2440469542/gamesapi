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

class Channel extends Base
{
    protected $pk = 'cid';
    public static function add($data){
        if(isset($data['cid']) && $data['cid'] > 0){
            $row = self::where('cid',$data['cid'])->update($data);
            cache('channel_'.$data['cid'],null);
        }else{
            $count = self::where("name",'=',$data["name"])->count();
            if($count>0){
                return error("渠道名称已存在");
            }
            $data['add_time'] = time();
            $row = $id = self::insertGetId($data);
            if($id > 0){
                self::createPartition('cp_user',$id);
                self::createPartition('cp_bill',$id);
                self::createPartition('cp_game_log',$id);
                self::createPartition('cp_user_stat',$id);
                self::createPartition('cp_order',$id);
                self::createPartition('cp_cash',$id);
            }
        }
        return $row;
    }
    public function getAddTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    protected static function checkTablePartition($tableName, $partitionName) {
        // 查询分区信息
        $sql = "SELECT PARTITION_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND PARTITION_NAME = :partition";
        $result = Db::query($sql, ['table' => $tableName, 'partition' => $partitionName]);
        // 判断是否存在分区
        if (!empty($result)) {
            return true;
        } else {
            return false;
        }
    }
    protected static function createPartition($tableName, $id) {
        if(!self::checkTablePartition($tableName, 'p' . $id)) {
            $sql = "ALTER TABLE `{$tableName}`
                            ADD PARTITION (
                                PARTITION p{$id} VALUES IN ({$id})
                            );";
            Db::execute($sql);
        }
    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public static function lists($where=[], $limit=10, $order='cid desc'){
        $where[] = ['is_del',"=",0];
        $list = self::where($where)
            ->order($order)
            ->select();
        return $list;
    }
    public function info($cid=0,$url=''){
        if($cid > 0){
            $info = cache('channel_'.$cid);
        }else{
            $info = [];
        }
        if(empty($info)){
            if($cid > 0){
                $info = self::where('cid',$cid)->find();
            }else{
                $info = self::where('url',$url)->find();
            }
            if($info){
                cache('channel_'.$cid,$info->toArray(),0);
            }
        }
        return $info;
    }
    public function getDetail($name){
        $info = self::where('name','=',$name)->find();
        return $info;
    }
}