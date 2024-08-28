<?php

namespace app\common\model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use hg\apidoc\annotation\Param;
class StarFile extends Base
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
     * @Field("id,sid,filename,file,admin_name,update_time,status")
     */
    public static function lists($where=[],$field='*',$orderBy="id desc"){
        $list = self::field($field)->where($where)->order($orderBy)->select();
        return $list;
    }
    /**
     * @param $data         数据
     * @param $admin_name   管理员名
     * @param $sid          活动ID
     * @Field("filename,file")
     * @return bool
     **/
    public static function add($data,$admin_name,$sid){
        $star_file = new StarFile;
        $info = [];
        foreach ($data as $val){
            $info[] = [
                'sid' => $sid,
                'filename' => $val['filename'],
                'file' => $val['file'],
                'admin_name' => $admin_name,
                'update_time' => time(),
                'status' => 0,
            ];
        }
        $star_file->saveAll($info);
        return true;
    }
    public static function edit(){

    }
}