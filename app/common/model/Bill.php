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
    const   WAGES_BOZHU = 105;  //博主工资
    const   WAGES_DAILI = 106;  //代理工资
    const   CASH_RETURN = 107;  //用户提现失败返回
    const   GAME_DEPOSIT = 108;  //游戏上分
    const   GAME_WITHDRAW = 109; //游戏下分
    const   WAGES_N3 = 111;     //N3工资
    const   LOCK_MONEY = 112;     //冻结余额
    const   UNLOCK_MONEY = 203;   //解除冻结金额

    const   RANK_MONEY = 113;     //排行榜奖励
    const   RANK_INV_MONEY = 114;     //邀请排行榜奖励
    const   DAY_LEVEL_MONEY = 115;     //每日返现金额
    const   WEEK_LEVEL_MONEY = 116;     //每周返现金额
    const   SIGN_MONEY = 200;     //签到奖励
    const   STAR_MONEY = 201;     //幸运星奖励
    const   RACS_MONEY = 202;     //比赛奖励
    public function getTypeText($type=0): array|string
    {
        $type_text = [
            self::GAME_BET          => 'Vitórias e derrotas em apostas em jogos',    //游戏下注输赢
            self::PAY_MONEY         => 'Recarga do usuário',                         //用户充值
            self::CASH_MONEY        => 'Retirada do usuário',                        //用户提现
            self::BOX_MONEY         => 'Abra o baú do tesouro para obter',           //宝箱
            self::ADMIN_MONEY       => 'Operações de Administrador',                 //管理员操作
            self::WAGES_BOZHU       => 'O salário do blogueiro',                     //博主工资
            self::WAGES_DAILI       => 'Salário da agência',                         //代理工资
            self::CASH_RETURN       => 'Retorno de fracasso de retirada',            //用户提现失败返回
            self::GAME_DEPOSIT      => 'Pontos de jogo',                             //游戏上分
            self::GAME_WITHDRAW     => 'Game Lower Division',                        //游戏下分
            self::WAGES_N3          => 'Salário N3',                                 //N3工资
            self::LOCK_MONEY        => 'Congelar o equilíbrio',                      //冻结余额
            self::UNLOCK_MONEY      => 'Quantidade inestimável',                     //解除冻结金额
            self::RANK_MONEY        => 'Recompensar',                                //排行榜奖励
            self::RANK_INV_MONEY    => 'Recompensas do taboleiro de convites',       //邀请排行榜奖励
            self::DAY_LEVEL_MONEY   => 'Valor de reembolso diário',                  //每日返现金额
            self::WEEK_LEVEL_MONEY  => 'Valor de reembolso semanal',                 //每周返现金额
            self::SIGN_MONEY        => 'assinar em recompensa',                      //签到奖励
            self::STAR_MONEY        => 'Lucky Star Reward',                          //幸运星奖励
            self::RACS_MONEY        => 'Recompensas da concorrência',                //比赛奖励
        ];
        if(isset($type_text[$type])){
            return $type_text[$type];
        }
        return $type_text;
    }
    public function getTypeAttr($value=''): array|string
    {
        $type_text = [
            self::GAME_BET    => '游戏下注输赢',     //游戏下注输赢
            self::PAY_MONEY   => '用户充值',        //用户充值
            self::CASH_MONEY  => '用户提现',        //用户提现
            self::BOX_MONEY   => '宝箱奖励',        //宝箱
            self::ADMIN_MONEY => '管理员修改',      //管理员操作
            self::WAGES_BOZHU => '博主工资',       //博主工资
            self::WAGES_DAILI => '代理工资',       //代理工资
            self::CASH_RETURN => '提现失败返回',    //提现失败返回
            self::GAME_DEPOSIT => '游戏上分',      //游戏上分
            self::GAME_WITHDRAW => '游戏下分',     //游戏下分
            self::WAGES_N3      => 'N3工资',      //N3工资
            self::LOCK_MONEY    => '余额冻结',     //冻结余额
            self::UNLOCK_MONEY  => '解冻金额',     //解冻金额
            self::RANK_MONEY    => '排行榜奖励',   //排行榜奖励
            self::RANK_INV_MONEY  => '排行榜奖励',   //邀请排行榜奖励
            self::DAY_LEVEL_MONEY  => '每日返现金额',   //每日返现金额
            self::WEEK_LEVEL_MONEY  => '每周返现金额',   //每周返现金额
            self::SIGN_MONEY  => '签到奖励',       //签到奖励
            self::STAR_MONEY  => '幸运星奖励',     //幸运星奖励
            self::RACS_MONEY  => '比赛奖励',       //比赛奖励
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
    public function addIntvie($user,$type,$money,$gifts=0,$multiple=0,$bet=0): array
    {
        $before_money = $user['money'];                  //账变前的金额
        $after_money = $user['money'] + $money + $gifts; //账变后的金额
        $type_text = $this->getTypeText($type);
        $data = [
            'cid' => $user['cid'],
            'uid' => $user['uid'],
            'type' => $type,
            'before_money' => $before_money,
            'after_money' => $after_money,
            'money' => $money + $gifts,
            'desc' => $type_text,
            'add_time' => time(),
        ];
        // 开启事务
        // 提交事务
        $update = [
            'money' => Db::raw('`money` + '.$money + $gifts),
        ];
        if($type == self::PAY_MONEY) {
            $channel = model('app\common\model\Channel')->info($user['cid']);
            $water = $channel['ct_multiple'] * $money + $gifts * $multiple;
            $update['water'] = Db::raw('`water` + '.$water);
        }else if($type == self::LOCK_MONEY) {
            $update['lock_money'] = Db::raw('`lock_money` + '.abs($money));
        }else if($type == self::UNLOCK_MONEY){
            $update['lock_money'] = Db::raw('`lock_money` - '.$money);
        }else if($type ==self::RANK_MONEY ||
            $type == self::RANK_INV_MONEY ||
            $type == self::DAY_LEVEL_MONEY ||
            $type == self::WEEK_LEVEL_MONEY ||
            $type == self::SIGN_MONEY ||
            $type == self::STAR_MONEY ||
            $type == self::RACS_MONEY
        ){
            $update['water'] = Db::raw('`water` + '.($money * $multiple));
        }else if($type === self::GAME_BET){
            $update['exp'] = Db::raw('`exp` + '.$bet);
            if($user['water'] > 0){
                if($bet > $user['water']){
                    $update['water'] = 0;
                }else{
                    $update['water'] = Db::raw('`water` - '.$bet);
                }
            }
        }
        $this->setPartition($user['cid']);
        User::where("uid","=",$user['uid'])->partition($this->partition)->update($update);
        $user['money'] = $after_money;
        $bid = self::partition($this->partition)->insertGetId($data);
        return ['code'=>0,'msg'=>'操作成功','user'=>$user,'bid'=>$bid];
    }
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::alias("b")
            ->field("b.*,u.mobile,u.inv_code")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","b.uid = u.uid")
            ->where($where)
            ->order($order)
            ->partition($this->partition)
            ->paginate($limit)->toArray();
        return $list;
    }
}