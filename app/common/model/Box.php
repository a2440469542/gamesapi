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

class Box extends Base
{
    protected $pk = 'id';
    protected $json = ['user_num'];
    protected $jsonAssoc = true;

    public static function add($data){
        $cid = $data['cid'];
        $channel['cz_money']  = $data['cz_money'];
        $channel['bet_money'] = $data['bet_money'];
        channel::where('cid',"=",$cid)->update($channel);
        $box = self::where('cid',"=",$cid)->column('box');
        $del = $update = $insert = $data_id = [];
        foreach($data['box'] as $k=>$v){
            if($v['id'] > 0){
                $update[] = $v;
                $data_id[] = $v['id'];
            }else {
                $insert[] = $v;
            }
        }
        foreach ($box as $item) {
            if(!in_array($item,$data_id)){
                $del[] = $item;
            }
        }
        if($del){
            self::where('id',"in",$del)->delete();
        }
        $row = self::insertAll($insert);
        $row = self::saveAll($update);
        if($row){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public function lists($where=[], $limit=10, $order='id asc'){
        $list = self::where($where)
            ->order($order)
            ->select()->toArray();
        return $list;
    }
    public function getInfo($id){
        $info = self::where('id',"=",$id)->find();
        return $info;
    }
}