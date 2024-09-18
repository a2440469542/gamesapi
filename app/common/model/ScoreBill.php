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

class ScoreBill extends Base
{
    protected $pk = 'id';
    const   SCORE_SIGN = 100;    //签到
    const   SCORE_PAY = 101;   //用户充值
    const   SCORE_BET = 102;  //用户下注
    const   EXCHANGE_GOODS = 103;  //商品兑换
    const   ADMIN_SCORE = 104;  //管理员操作
    const   RACS_SCORE = 105;  //比赛奖励
    public function getTypeText($type=0): array|string
    {
        $type_text = [
            self::SCORE_SIGN  => 'Signar',    //游戏下注输赢
            self::SCORE_PAY   => 'Recargar',  //用户充值
            self::SCORE_BET   => 'Aposto',    //用户下注
            self::EXCHANGE_GOODS => 'Troca de mercadorias',  //商品兑换
            self::ADMIN_SCORE => 'Operação de administrador',  //管理员操作
            self::RACS_SCORE => 'Recompensas da concorrência',  //比赛奖励
        ];
        if(isset($type_text[$type])){
            return $type_text[$type];
        }
        return $type_text;
    }
    public function getTypeAttr($value=''): array|string
    {
        $type_text = [
            self::SCORE_SIGN  => '签到',        //游戏下注输赢
            self::SCORE_PAY   => '用户充值',     //用户充值
            self::SCORE_BET   => '用户下注',     //用户提现
            self::EXCHANGE_GOODS => '商品兑换',  //商品兑换
            self::ADMIN_SCORE => '管理员操作',  //管理员操作
            self::RACS_SCORE => '比赛奖励',     //比赛奖励
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
    public function addIntvie($userInfo,$cid,$uid,$type,$score): array
    {
        $before_score = 0;     //账变前的金额
        $after_score = $score; //账变后的金额
        if($userInfo){
            $before_score = $userInfo['score'];
            $after_score = $userInfo['score'] + $score;
        }

        $type_text = $this->getTypeText($type);
        $data = [
            'cid' => $cid,
            'uid' => $uid,
            'type' => $type,
            'before_score' => $before_score,
            'after_score' => $after_score,
            'score' => $score,
            'desc' => $type_text,
            'add_time' => time(),
        ];
        // 开启事务
        // 提交事务
        if($userInfo) {
            $update['score'] = Db::raw('`score` + '.$score);
            if($type == self::SCORE_SIGN){
                $update['get_sign_score'] = Db::raw('`get_sign_score` + '.$score);
            }elseif($type == self::SCORE_PAY){
                $update['get_order_score'] = Db::raw('`get_order_score` + '.$score);
            }elseif($type == self::SCORE_BET){
                $update['get_bet_score'] = Db::raw('`get_bet_score` + '.$score);
            }
            UserInfo::where("cid","=",$cid)->where('uid',$uid)->update($update);
        }else{
            $insertData = [
                'cid' => $cid,
                'uid' => $uid,
                'score' => $score,
            ];
            if($type == self::SCORE_SIGN){
                $insertData['get_sign_score'] = $score;
            }elseif($type == self::SCORE_PAY){
                $insertData['get_order_score'] = $score;
            }elseif($type == self::SCORE_BET){
                $insertData['get_bet_score'] = $score;
            }
            UserInfo::insertGetId($insertData);
        }
        $bid = self::insertGetId($data);
        return ['code'=>0,'msg'=>'操作成功','userInfo'=>$userInfo];
    }
    public function lists($where=[], $limit=10, $order='id desc'){
        $list = self::alias("b")
            ->field("b.*,u.mobile,u.inv_code")
            ->leftJoin("user `u`","b.uid = u.uid")
            ->where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $list;
    }
}