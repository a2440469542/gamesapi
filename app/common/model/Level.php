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

class Level extends Base
{
    protected $pk = 'id';
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
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $list;
    }
    public function getList(){
        $list = self::order('level asc')->select()->toArray();
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