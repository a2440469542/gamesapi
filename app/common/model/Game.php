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

class Game extends Base
{
    protected $pk = 'gid';
    public static function add($data){
        if(isset($data['gid']) && $data['gid'] > 0){
            $row = self::where('gid',"=",$data['gid'])->update($data);
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
    public function lists($where=[], $limit=10, $order='gid desc'){
        $list = self::where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $list;
    }
}