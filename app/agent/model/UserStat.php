<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/1
 * Time: 20:46
 */
namespace app\agent\model;
use app\admin\model\Base;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\AddField;
use think\facade\Db;

class UserStat extends Base
{
    protected $pk = 'id';
    public function lists($where, $limit=10, $orderBy='date desc'){
        $filed = 'date,sum(invite_user) as invite_user,
            sum(cz_money) as cz_money,
            sum(bet_money) as bet_money,
            sum(cash_money) as cash_money';
        $list = self::field($filed)
            ->where($where)
            ->partition($this->partition)
            ->group('date')
            ->select()->toArray();
        foreach($list as &$val){
            $val['cz_num'] = $this->get_cz_num($val['date']);
            $val['cash_num'] = $this->get_cash_num($val['date']);
            $wages_num = $this->get_wages_num($val['date']);
            $val['bozhu_num'] = $wages_num['bozhu'];
            $val['daili_num'] = $wages_num['daili'];
            $val['n3_num'] = $wages_num['n3'];
            $wages_money = $this->get_wages_money($val['date']);
            $val['bozhu_money'] = $wages_money['bozhu'];
            $val['daili_money'] = $wages_money['daili'];
            $val['n3_money'] = $wages_money['n3'];
        }
        return $list;
    }
    //根据条件获取当前下级的统计信息
    public function get_cz_num($date){
        return self::where("cz_money",">",0)
            ->where("date",'=',$date)
            ->partition($this->partition)
            ->group('uid')->count();
    }
    public function get_cash_num($date){
        return self::where("cash_money",">",0)
            ->where("date",'=',$date)
            ->partition($this->partition)
            ->group('uid')->count();
    }
    public function get_wages_num($date){
        $sttime = strtotime($date);
        $ettime = $sttime + 60*60;
        $data['bozhu'] = self::where('add_time',"between",[$sttime,$ettime])->where("type","=",1)->partition($this->partition)->group('uid')->count();
        $data['daili'] = self::where('add_time',"between",[$sttime,$ettime])->where("type","=",2)->partition($this->partition)->group('uid')->count();
        $data['n3'] = self::where('add_time',"between",[$sttime,$ettime])->where("type","=",3)->partition($this->partition)->group('uid')->count();
        return $data;
    }
    public function get_wages_money($date){
        $sttime = strtotime($date);
        $ettime = $sttime + 60*60;
        $data['bozhu'] = self::where('add_time',"between",[$sttime,$ettime])->where("type","=",1)->partition($this->partition)->sum('money');
        $data['daili'] = self::where('add_time',"between",[$sttime,$ettime])->where("type","=",2)->partition($this->partition)->sum('money');
        $data['n3'] = self::where('add_time',"between",[$sttime,$ettime])->where("type","=",3)->partition($this->partition)->sum('money');
        return $data;
    }
}