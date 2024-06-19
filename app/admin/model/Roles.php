<?php

namespace app\admin\model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use hg\apidoc\annotation\Param;

class Roles extends Base
{
    protected $pk = 'rid';
    protected $json = ['rule'];
    protected $jsonAssoc = true;


    /**
     * @Field("role_id,role_name,status")
     * @AddField("rule",type="array",desc="菜单ID列表")
     */
    public static function add($data){
        if(isset($data['rid']) && $data['rid'] > 0){
            $res = self::where("rid","=",$data['rid'])->update($data);
        }else{
            $res = self::insert($data);
        }
        return $res;
    }
    /**
     * @Field("role_id,role_name,rule,addtime,status")
     */
    public static function lists(){
        $list = self::order("rid desc")->select()->toArray();
        return $list;
    }
}