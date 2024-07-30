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
    protected $json = ['plate_line','activity'];
    protected $jsonAssoc = true;
    public static function add($data){
        if(!isset($data['plate_line'])) {
            unset($data['plate_line']);
        }
        if(isset($data['cid']) && $data['cid'] > 0){
            unset($data['add_time']);
            Cache::store('redis')->delete('channel_'.$data['cid']);
            $row = self::where('cid',$data['cid'])->update($data);
        }else{
            $count = self::where("name",'=',$data["name"])->count();
            if($count>0){
                return error("渠道名称已存在");
            }
            $data['add_time'] = time();
            $row = $id = self::insertGetId($data);
            if($id > 0){
                self::createPartition('cp_user',$id);       //用户表
                self::createPartition('cp_bill',$id);       //账单表
                self::createPartition('cp_game_log',$id);   //游戏记录表
                self::createPartition('cp_box_log',$id);    //游戏记录表
                self::createPartition('cp_user_stat',$id);  //数据统计表
                self::createPartition('cp_order',$id);      //充值表
                self::createPartition('cp_cash',$id);       //提现表
                self::createPartition('cp_wages',$id);      //工资表
                self::createPartition('cp_game_user',$id);  //游戏注册账号
                self::createPartition('cp_game_wallet',$id);//游戏上下分记录
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
            $sql = "ALTER TABLE `{$tableName}` ADD PARTITION ( PARTITION p{$id} VALUES IN ({$id}));";
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
            ->paginate($limit);
        return $list;
    }
    public function info($cid=0,$url=''){
        if($cid > 0){
            $info = Cache::store('redis')->get('channel_'.$cid);
        }else{
            $info = [];
        }
        if(empty($info)){
            if($cid > 0){
                $info = self::where('cid',$cid)->where("is_del",'=',0)->find();
            }else{
                $info = self::where('url',$url)->where("is_del",'=',0)->find();
            }
            if($info){
                Cache::store('redis')->set('channel_'.$cid,$info->toArray(),0);
                //cache('channel_'.$cid,$info->toArray(),0);
            }
        }
        return $info;
    }
    public function getDetail($name){
        $info = self::where('name','=',$name)->find();
        return $info;
    }
}