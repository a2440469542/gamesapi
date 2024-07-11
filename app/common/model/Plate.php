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

class Plate extends Base
{
    protected $pk = 'id';
    protected $json = ['plate_line'];
    protected $jsonAssoc = true;
    public static function add($data){
        /*if($data['is_rebot'] == 1){
            $count = self::where('is_rebot',"=",1)->count();
            if($count > 0)  return error("测试线路只能存在一个");
        }*/
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
            ->select()->toArray();
        return $list;
    }
    public function getInfo($pid){
        $info = self::where('id',"=",$pid)->find();
        return $info;
    }
}