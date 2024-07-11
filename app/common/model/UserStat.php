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

class UserStat extends Base
{
    protected $pk = 'id';
    /**
     * @Field("id,user,aid,admin_name,tk_id,pid,money")
     * @AddField("token",type="string",desc="用户token")
     */
    public function add($user,$data){
        $uid = $user['uid'];
        $date = date("Y-m-d",time());
        $where = [
            ['date',"=",$date],
            ["uid","=",$uid]
        ];
        $this->setPartition($user['cid']);
        $stat = self::where($where)->partition($this->partition)->find();
        if(empty($stat)){
            $data['date'] = $date;
            $data['cid'] = $user['cid'];
            $data['uid'] = $user['uid'];
            $data['mobile'] = $user['mobile'];
            $data['inv_code'] = $user['inv_code'];
            self::insert($data);
        }else{
            $update = [];
            foreach($data as $key=>$val){
                if($key === 'cid') continue;
                if($key === 'uid') continue;
                if($key === 'mobile') continue;
                $update[$key] = Db::raw('`'.$key.'` + '.$val);
                //$update[$key] = $stat[$key] + $val;
            }
            $update['date'] = $date;
            $update['cid'] = $user['cid'];
            $update['uid'] = $user['uid'];
            $update['mobile'] = $user['mobile'];
            $update['inv_code'] = $user['inv_code'];
            self::where("id","=",$stat['id'])->partition($this->partition)->update($update);
        }
        return true;
    }
    public function lists($where, $limit=10, $orderBy='id desc'){
        $uid = self::field("uid,cid,mobile,sum(invite_user) as invite_user")
            ->where($where)
            ->partition($this->partition)
            ->group('uid')
            ->select()->toArray();
        $list = [];
        foreach($uid as $key=>$val){
            $filed = 'sum(cz_money) as cz_money, 
            AVG(cz_money) as avg_cz_money,
            sum(cz_num) as cz_num, 
            sum(bet_money) as bet_money, 
            sum(win_money) as win_money, 
            sum(cash_money) as cash_money,
            sum(cash_num) as cash_num ,
            sum(box_money) as box_money';
            $summary = self::alias("us")
                ->leftJoin("cp_user PARTITION({$this->partition}) `u`","us.uid = u.uid")
                ->where('u.pid', '=', $val['uid'])
                ->field($filed)
                ->partition($this->partition)
                ->find()->toArray();
            $val['cz_money'] = round($summary['cz_money'] ?? '0.00',2);
            $val['avg_cz_money'] = round($summary['avg_cz_money'] ?? '0.00',2);
            $val['cz_num'] = round($summary['cz_num'] ?? '0.00',2);
            $val['bet_money'] = round($summary['bet_money'] ?? '0.00',2);
            $val['win_money'] = round($summary['win_money'] ?? '0.00',2);
            $val['cash_money'] = round($summary['cash_money'] ?? '0.00',2);
            $val['cash_num'] = round($summary['cash_num'] ?? '0.00',2);
            $val['box_money'] = round($summary['box_money'] ?? '0.00',2);
            $list[] = $val;
        }
        return $list;
    }
    //根据条件获取当前下级的统计信息
    /**
     * @Field("uid,date,bet_money,cz_money")
     */
    public function team($where){
        $list = self::alias("us")
            ->field("us.date,us.uid,us.bet_money,us.cz_money,u.inv_code")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","us.uid = u.uid")
            ->where($where)
            ->partition($this->partition)
            ->select();
        return $list;
    }
    //根据条件获取当前下级满足宝箱的人数
    public function get_box_num($where,$cz_money,$bet){
        $count = self::alias("us")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","us.uid = u.uid")
            ->where($where)
            ->partition($this->partition)
            ->group('us.uid')
            ->having("sum(cz_money) >= {$cz_money} AND sum(bet_money) >= {$bet}")
            ->count();
        return $count;
    }
    //根据条件获取当前下级的存款人数
    public function get_deposit_num($where){
        $where[] = ['cz_money',">",0];
        $count = self::alias("us")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","us.uid = u.uid")
            ->where($where)
            ->partition($this->partition)
            ->group('us.uid')
            ->count();
        write_log("查询统计：".$count,'wages');
        return $count;
    }
    //根据条件获取当前下级的总存款和总投注
    public function get_deposit_and_bet($where){
        $result = self::alias("us")
            ->field("sum(cz_money) as cz_money, sum(bet_money) as bet_money")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","us.uid = u.uid")
            ->where($where)
            ->partition($this->partition)
            ->find();
        return $result;
    }
    //获取当前渠道总投注额
    public function get_total_bet(){
        return self::partition($this->partition)->sum('bet_money');
    }
    //获取某个用户的数据汇总
    public function get_user_summary($uid){
        $filed = 'uid,mobile,
        sum(invite_user) as invite_user,
        sum(cz_money) as total_deposit, 
        sum(cz_num) as cz_num, 
        sum(bet_money) as bet_money, 
        sum(win_money) as win_money, 
        sum(cash_money) as cash_money,
        sum(cash_num) as cash_num ,
        sum(box_money) as box_money';
        $summary = self::where('uid', '=', $uid)
            ->field($filed)
            ->partition($this->partition)
            ->find();
        return $summary;
    }
    //充值人数
    public function get_cz_num(){
        return self::where("cz_money",">",0)->partition($this->partition)->group('uid')->count();
    }
    //获取充值金额
    public function get_total_money(){
        $filed = '`us`.uid,`us`.mobile,
        sum(invite_user) as invite_user,
        sum(cz_money) as cz_money, 
        sum(cz_num) as cz_num, 
        sum(bet_money) as bet_money, 
        sum(win_money) as win_money, 
        sum(cash_money) as cash_money,
        sum(cash_num) as cash_num ,
        sum(box_money) as box_money';
        return self::alias('us')
            ->field($filed)
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","us.uid = u.uid")
            ->where("u.is_rebot","=",0)
            ->partition($this->partition)->find();
    }
    public function box_num(){
        return self::where("box_money",">",0)->partition($this->partition)->count();
    }
}