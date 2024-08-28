<?php

namespace app\common\model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use hg\apidoc\annotation\Param;
class LuckyStar extends Base
{
    protected $pk = 'id';
    public function getStartTimeAttr($value){
        if($value > 0){
            return date("Y-m-d H:i:s",$value);
        }else{
            return "";
        }
    }
    public function getEndTimeAttr($value){
        if($value > 0){
            return date("Y-m-d H:i:s",$value);
        }else{
            return "";
        }
    }
    /**
     * @Field("id,name,time_type,start_time,end_time,sort,rule,admin_name,status")
     */
    public static function lists($where=[],$limit=10,$field='*',$orderBy="id desc"){
        $where[] = ["is_del","=",0];
        $list = self::alias("g")->field($field)->where($where)->order($orderBy)->paginate($limit);
        return $list;
    }
    /**
     * @Field("name,time_type,start_time,end_time,sort,rule,admin_name,status,update_time,condition,num,short_url,is_upper_limit,upper_limit,multiple")
     */
    public static function add($data){
        $lucky_star = new LuckyStar;
        $row = [
            'name' => $data["name"],
            'time_type' => $data["time_type"],
            'start_time' => $data["start_time"],
            'end_time' => $data["end_time"],
            'sort' => $data["sort"],
            'rule' => $data["rule"],
            'admin_name' => $data["admin_name"],
            'update_time' => time(),
            'status' => isset($data["status"]) ? $data["status"] : 0,
            'condition' => $data["condition"],
            'num' => $data["num"] ?? 1,
            'short_url' => $data["short_url"] ?? '',
            'is_upper_limit' => $data['is_upper_limit'] ?? 0,
            'upper_limit' => $data["upper_limit"] ?? 0,
            'multiple' => $data["multiple"] ?? 0,
        ];
        $id = LuckyStar::insertGetId($row);
        /*$lucky_star->name           = $data["name"];
        $lucky_star->time_type      = $data["time_type"];
        $lucky_star->start_time     = $data["start_time"];
        $lucky_star->end_time       = $data["end_time"];
        $lucky_star->sort           = $data["sort"];
        $lucky_star->rule           = $data["rule"];
        $lucky_star->admin_name     = $data["admin_name"];
        $lucky_star->admin_name     = time();
        $lucky_star->status         = isset($data["status"]) ? $data["status"] : 0;
        $lucky_star->condition      = $data["condition"];
        $lucky_star->num            = $data["num"] || 1;
        $lucky_star->short_url      = $data["short_url"] || '';
        $lucky_star->upper_limit    = $data["upper_limit"] || 0;
        $lucky_star->multiple       = $data["multiple"] || 0;
        $lucky_star->save();
        $id = $lucky_star->id;*/
        $file = StarFile::add($data['files'],$data["admin_name"],$id);
        $phrase = StarPhrase::add($data["phrase"],$data["admin_name"],$id);
        $coin = StarCoin::add($data["coin"],$data["admin_name"],$id);
       /* $path = APP_PATH;
        $command =  "php {$path}think importMobile {$id} > {$path}/star_file_log/{$id}.log 2>&1 & echo $! &";
        $PID = shell_exec($command);*/
        return true;
    }
    /**
     * @Field("id,name,time_type,start_time,end_time,sort,rule,admin_name,status,update_time,condition,num,short_url,is_upper_limit,upper_limit,multiple")
     */
    public static function edit($data){
        $lucky_star = self::where("id","=",$data["id"])->find();
        if(empty($lucky_star)){
            return ["code"=>500,"msg"=>'无此数据'];
        }
        if($lucky_star["status"] == 1){
            return ["code" => 200,"msg"=>"修改成功"];
        }
        unset($data["files"],$data["phrase"],$data["coin"]);
        if(isset($data["start_time"])){
            $data["start_time"] = strtotime($data["start_time"]);
        }
        if(isset($data["end_time"])){
            $data["end_time"] = strtotime($data["end_time"]);
        }
        $lucky_star->where("id","=",$data["id"])->save($data);
        /*$path = APP_PATH;
        $command =  "php {$path}think importMobile {$data["id"]} > {$path}/star_file_log/{$data["id"]}.log 2>&1 & echo $! &";
        $PID = shell_exec($command);*/
        return ["code"=>200,"msg"=>'修改成功'];
    }
    /**
     * @Field("id,name,time_type,start_time,end_time,sort,rule,admin_name,status,update_time,condition,num,short_url,is_upper_limit,upper_limit,multiple")
     */
    public static function info(){
        $info =  LuckyStar::where("status","=",1)->where("is_del","=",0)->find();
        return $info;
    }
}