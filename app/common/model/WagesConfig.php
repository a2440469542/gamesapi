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

class WagesConfig extends Base
{
    protected $pk = 'id';

    public static function add($data){
        if(isset($data['cid']) && $data['cid']  > 0){
            $row = self::where('cid',"=",$data['cid'])->update($data);
        }else{
            $row = self::insert($data);
        }
        if($row){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }
    public function getInfo($cid){
        $info = self::where('cid',"=",$cid)->find();
        return $info;
    }
}