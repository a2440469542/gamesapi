<?php

namespace app\common\model;
use app\admin\model\Base;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use hg\apidoc\annotation\Param;
class StarCoin extends Base
{
    protected $pk = 'id';
    public function getUpdateTimeAttr($value){
        if($value > 0){
            return date("Y-m-d H:i:s",$value);
        }else{
            return "";
        }
    }
    /**
     * @Field("id,min,max,money,admin_name,update_time")
     */
    public static function lists($where=[],$field='*',$orderBy="id desc"){
        $list = self::alias("g")->field($field)->where($where)->order($orderBy)->select();
        return $list;
    }
    /**
     * @param $data         数据
     * @param $admin_name   管理员名
     * @param $sid          活动ID
     * @Field("min,max,money")
     * @return bool
     **/
    public static function add($data,$admin_name,$sid){
        $star_coin = new StarCoin();
        $info = [];
        foreach ($data as $val){
            $info[] = [
                'min' => $val['min'],
                'max' => $val['max'],
                'money' => $val['money'],
                'admin_name' => $admin_name,
                'update_time' => time(),
            ];
        }
        $star_coin->saveAll($info);
        return true;
    }
}