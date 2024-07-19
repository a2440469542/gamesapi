<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/1
 * Time: 20:46
 */
namespace app\admin\model;
use think\captcha\facade\Captcha;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\AddField;
use think\facade\Cache;

class Agent extends Base
{
    protected $pk = 'id';
    protected $json = ['rule'];
    /**
     * @Field("id,aid,user_name,rid,password,nickname,mobile,avatar,status")
     */
    public static function add(){

    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->paginate($limit);
        return $list;
    }
    public function set_channel($id,$pid){
        $data = [
            'pid' => $pid
        ];
        $res = self::where('id',$id)->update($data);
        return $res;
    }
}