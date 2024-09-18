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
use think\facade\Db;

class ScoreStat extends Base
{
    protected $pk = 'id';
    /**
     * @Field("id,user,aid,admin_name,tk_id,pid,money")
     * @AddField("token",type="string",desc="用户token")
     */
    public function add($data){
        $date = date("Y-m-d",time());
        $where = [['date',"=",$date]];
        $stat = self::where($where)->find();
        if(empty($stat)){
            $data['date'] = $date;
            self::insert($data);
        }else{
            $update = [];
            foreach($data as $key=>$val){
                $update[$key] = Db::raw('`'.$key.'` + '.$val);
            }
            self::where("id","=",$stat['id'])->update($update);
        }
        return true;
    }
    /**
     * @AddField("role_name",type="string",desc="用户权限名称")
     */
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        foreach($list['data'] as &$val){
            $val['order_num'] = UserStat::where("date","=",$val['date'])->where("cz_num",">",0)->count();
            $val['bet_num'] = UserStat::where("date","=",$val['date'])->where("bet_money",">",0)->count();
            $val['total_score'] = $val['sign_score'] + $val['order_score'] + $val['bet_score'];
        }
        return $list;
    }
}