<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

/**
 * 用户签到接口
 * @Apidoc\Title("用户签到接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(5)
 */
class Sign extends Base
{
    /**
     * @Apidoc\Title("签到配置信息")
     * @Apidoc\Desc("签到配置信息")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("签到配置信息")
     * @Apidoc\Returned("list",type="array",desc="签到配置",table="cp_sign_config")
     * @Apidoc\Returned("is_sign",type="int",desc="是否签到：0=未签到；1=已签到")
     * @Apidoc\Returned("num",type="int",desc="签到次数")
     */
    public function list(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $list = app('app\common\model\SignConfig')->lists();
        if(!$list){
            return error("Verifique que não está configurado");  //签到未配置
        }
        $sign = app('app\common\model\Sign')->where("cid",'=',$cid)->where('uid','=',$uid)->find();
        $data['is_sign'] = 0;
        $data['num'] = 0;
        if($sign){
            $data['num'] = $sign['num'];
            if(date('Y-m-d',time()) === date('Y-m-d',$sign['last_time'])){
                $data['is_sign'] = 1;
            }
        }
        $data['list'] = $list;
        return success("obter sucesso",$data);//获取成功
    }
    /**
     * @Apidoc\Title("签到")
     * @Apidoc\Desc("签到")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("签到")
     * @Apidoc\Returned("is_sign",type="int",desc="是否签到：0=未签到；1=已签到")
     * @Apidoc\Returned("num",type="int",desc="签到次数")
     */
    public function sign(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $UserModel = model('app\common\model\User', $cid);
        $user = $UserModel->getInfo($uid);
        $sign = app('app\common\model\Sign')->where("cid",'=',$cid)->where('uid','=',$uid)->find();
        if($sign && date('Y-m-d',time()) === date('Y-m-d',$sign['last_time'])){
            return error("Already signed in, please do not sign in again");  //已签到，请勿重复签到
        }
        $redis = Cache::store('redis')->handler();
        $lockKey = "user_sign_lock_{$cid}_{$uid}";
        if ($redis->exists($lockKey)) {
            return error('O pedido está sendo atualmente processado, por favor tente de novo mais tarde');
        }
        $redis->set($lockKey, true, 5); // 设置锁，60秒后过期
        $OrderModel = model('app\common\model\Order',$cid);
        $today_order = $OrderModel->get_today_order($uid);
        if($today_order<1){
            return error("Eu não recharguei hoje, então não posso assinar");  //今日未充值，无法签到
        }
        try {
            $num = 1;
            if($sign){
                $num = $sign['num'] + 1;
            }
            $sign_config = app('app\common\model\SignConfig')->where('day','=',$num)->find();
            if(!$sign_config) {
                return error("Verifique que não está configurado");  //签到未配置
            }
            $money = $sign_config['money'];
            $multiple = $sign_config['multiple'];
            $config = get_config();
            $score = 0;
            if($config['day_sign'] > 0) $score = $config['day_sign'];
            Db::startTrans();
            try {
                $BillModel = model('app\common\model\Bill', $cid);
                $BillModel->addIntvie($user, $BillModel::SIGN_MONEY, $money, 0, $multiple);
                if($sign){
                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                    if($yesterday === date('Y-m-d',$sign['last_time'])){
                        $sign->day += 1;
                    }else{
                        $sign->day = 1;
                    }
                    $sign->num = $num;
                    $sign->last_time = time();
                    $sign->score += $score;
                    $sign->save();
                }else{
                    $data = [
                        'cid' => $cid,
                        'uid' => $uid,
                        'num' => 1,
                        'day' => 1,
                        'last_time' => time(),
                        'sign_time' =>  date('Y-m-d H:i:s',time()),
                        'score' => $score
                    ];
                    app('app\common\model\Sign')->insert($data);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return error($e->getMessage(), '500');
            }
        }finally {
            $redis->del($lockKey); // 处理完成后删除锁
        }
        $data['num'] = $num;
        $data['is_sign'] = 1;
        return success("assinar com sucesso",$data);//签到成功
    }
}