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

class Bill extends Base
{
    protected $pk = 'id';
    const   GAME_BET = 100;    //游戏下注输赢
    const   PAY_MONEY = 101;   //用户充值
    const   CASH_MONEY = 102;  //用户提现
    const   BOX_MONEY = 103;  //开启宝箱获得
    const   ADMIN_MONEY = 104;  //管理员操作

    public function getTypeText($type=0): array|string
    {
        $type_text = [
            self::GAME_BET          => 'Vitórias e derrotas em apostas em jogos',    //游戏下注输赢
            self::PAY_MONEY         => 'Recarga do usuário',                         //用户充值
            self::CASH_MONEY        => 'Retirada do usuário',                        //用户提现
            self::BOX_MONEY         => 'Abra o baú do tesouro para obter',           //宝箱
            self::ADMIN_MONEY       => 'Operações de Administrador',                 //管理员操作
        ];
        if(isset($type_text[$type])){
            return $type_text[$type];
        }
        return $type_text;
    }
    public function getTypeTextAttr($value=''): array|string
    {
        $type_text = [
            self::GAME_BET    => '游戏下注输赢',    //游戏下注输赢
            self::PAY_MONEY   => '用户充值',       //用户充值
            self::CASH_MONEY  => '用户提现',       //用户提现
            self::BOX_MONEY   => '宝箱奖励',       //宝箱
            self::ADMIN_MONEY => '管理员修改',     //管理员操作
        ];
        if(isset($type_text[$value])){
            return $type_text[$value];
        }
        return $type_text;
    }
    public function getAddTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function addIntvie($user,$type,$money): array
    {
        $before_money = $user['money'];         //账变前的金额
        $after_money = $user['money'] + $money; //账变后的金额
        $type_text = $this->getTypeText($type);
        $data = [
            'cid' => $user['cid'],
            'uid' => $user['uid'],
            'type' => $type,
            'before_money' => $before_money,
            'after_money' => $after_money,
            'money' => $money,
            'desc' => $type_text,
            'add_time' => time(),
        ];
        // 开启事务
        // 提交事务
        $update = [
            'money' => Db::raw('`money` + '.$money),
        ];
        if($type == self::PAY_MONEY) {
            $channel = model('app\common\model\Channel')->info($user['cid']);
            $update['water'] = $channel['ct_multiple'] * $money;
        }
        $this->setPartition($user['cid']);
        User::where("uid","=",$user['uid'])->partition($this->partition)->update($update);
        $user['money'] = $after_money;
        $bid = self::partition($this->partition)->insertGetId($data);
        return ['code'=>0,'msg'=>'操作成功','user'=>$user,'bid'=>$bid];
    }
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::alias("b")
            ->field("b.*,u.mobile")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","b.uid = u.uid")
            ->where($where)
            ->order($order)
            ->partition($this->partition)
            ->append(['type_text'])
            ->paginate($limit)->toArray();
        return $list;
    }
}