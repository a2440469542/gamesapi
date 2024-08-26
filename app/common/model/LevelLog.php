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

class LevelLog extends Base
{
    protected $pk = 'id';
    public static function add($cid,$uid,$level,$money,$type = 1){
        $data = [
            'cid' => $cid,
            'uid' => $uid,
            'level' => $level,
            'money' => $money,
            'type' => $type,
            'add_time' => date("Y-m-d H:i:s",time())
        ];
        $row = self::insert($data);
        if($row){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
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
    public function level($exp){
        return self::where('exp',"<=",$exp)->order('exp','desc')->value('level');
    }

    public function stat($where=[], $limit=10, $order='id desc'){
        $list = self::alias('l')
            ->leftJoin('user u','u.level = l.level')
            ->field('l.id,l.level,count(u.uid) as num')
            ->where($where)
            ->order($order)
            ->group('l.id')
            ->paginate($limit)->toArray();
        return $list;
    }
}