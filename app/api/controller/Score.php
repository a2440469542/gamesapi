<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

/**
 * 积分活动相关接口
 * @Apidoc\Title("积分活动相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(15)
 */
class Score extends Base
{
    /**
     * @Apidoc\Title("积分活动首页信息相关接口")
     * @Apidoc\Desc("积分活动首页信息相关接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("积分活动首页信息相关接口")
     * @Apidoc\Returned("day_sign", type="int",require=false, desc="每日签到可领取的金额")
     * @Apidoc\Returned("day_sign_desc", type="string",require=true, desc="每日签到积分说明")
     * @Apidoc\Returned("order_score", type="int",require=true, desc="充值RS1可获得多少积分")
     * @Apidoc\Returned("order_score_desc", type="string",require=true, desc="充值兑换积分倍数说明")
     * @Apidoc\Returned("bet_score", type="int",require=true, desc="投注r$1可获得多少积分")
     * @Apidoc\Returned("bet_score_desc", type="string",require=true, desc="投注兑换积分倍数说明")
     * @Apidoc\Returned("sign_score",type="int",desc="签到可领取的积分")
     * @Apidoc\Returned("un_get_order_score",type="int",desc="充值可领取的积分")
     * @Apidoc\Returned("un_get_bet_score",type="int",desc="下注可领取的积分")
     * @Apidoc\Returned("score",type="int",desc="积分")
     */
    public function score_info()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $config = get_config();
        $data['day_sign'] = $config['day_sign'] ?? 0;
        $data['day_sign_desc'] = $config['day_sign_desc'] ?? '';
        $data['order_score'] = $config['order_score'] ?? 0;
        $data['order_score_desc'] = $config['order_score_desc'] ?? '';
        $data['bet_score'] = $config['bet_score'] ?? 0;
        $data['bet_score_desc'] = $config['bet_score_desc'] ?? '';
        $user_info = Db::name('user_info')->where("cid","=",$cid)->where("uid","=",$uid)->find();
        $data['score'] = 0;
        $data['sign_score'] = 0;
        $data['un_get_order_score'] = 0;
        $data['un_get_bet_score'] = 0;
        $is_sign = Cache::store('redis')->get('is_sign'.$cid.'_'.$uid);
        if(!$is_sign || $is_sign['date'] != date('Y-m-d')){
            $data['sign_score'] = $data['day_sign'];
        }
        $orderModel = model('app\common\model\Order',$cid);
        $total_money = $orderModel->get_sum_money($uid);
        if($total_money > 0){
            $data['un_get_order_score'] = $total_money * $data['order_score'];
        }
        $UserStatModel = model('app\common\model\UserStat',$cid);
        $bet_money = $UserStatModel->get_total_bet_by_user($uid);
        if($bet_money > 0){
            $data['un_get_bet_score'] = $bet_money * $data['bet_score'];
        }
        if($user_info){
            $data['un_get_order_score'] = $data['un_get_order_score'] - $user_info['get_order_score'];
            $data['un_get_bet_score'] = $data['un_get_bet_score'] <= $user_info['get_bet_score'] ? 0 : $data['un_get_bet_score'] - $user_info['get_bet_score'];
            $data['score'] = $user_info['score'];
        }
        return success('obter sucesso',$data);   //获取成功
    }

    /**
     * @Apidoc\Title("领取积分")
     * @Apidoc\Desc("领取积分")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("领取积分")
     * @Apidoc\Param("type", type="int",require=true, desc="领取类型：sign=签到;order=充值;bet=下注")
     * @Apidoc\Returned("score",type="int",desc="积分")
     */
    public function get_score(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $type = input('type','');
        if($type === '' || !in_array($type,['sign','order','bet'])){
            return error('parâmetros incorretos');  //参数错误
        }
        $redis = Cache::store('redis')->handler();
        $lockKey = "user_score_lock_{$cid}_{$uid}";
        if ($redis->exists($lockKey)) {
            return error('O pedido está sendo atualmente processado, por favor tente de novo mais tarde');
        }
        $redis->set($lockKey, true, 5); // 设置锁，60秒后过期

        $config = get_config();
        $data['day_sign'] = $config['day_sign'] ?? 0;
        $data['order_score'] = $config['order_score'] ?? 0;
        $data['bet_score'] = $config['bet_score'] ?? 0;
        if($data['day_sign'] == 0 && $data['order_score'] == 0 && $data['bet_score'] == 0) {
            return error('Não há nada disponível para afirmar');
        }
        $ScoreBillModel = app('app\common\model\ScoreBill');

        $user_info = Db::name('user_info')->where("cid","=",$cid)->where("uid","=",$uid)->find();
        $score = 0;
        if($type === 'sign'){
            $is_sign = Cache::store('redis')->get('is_sign'.$cid.'_'.$uid);
            if(!$is_sign || $is_sign['date'] != date('Y-m-d')){
                $score = $data['day_sign'];
            }
            $types = $ScoreBillModel::SCORE_SIGN;
            $stat_update = ['sign_score'=>$score,'sign_num'=>1];
        }else if($type === 'order'){
            $orderModel = model('app\common\model\Order',$cid);
            $total_money = $orderModel->get_sum_money($uid);
            $score = $total_money * $data['order_score'];
            if($user_info) {
                $score = intval($total_money * $data['order_score']) - $user_info['get_order_score'];
            }
            $types = $ScoreBillModel::SCORE_PAY;
            $stat_update = ['order_score'=>$score];
        }else if($type === 'bet'){
            $UserStatModel = model('app\common\model\UserStat',$cid);
            $bet_money = $UserStatModel->get_total_bet_by_user($uid);
            $score = $bet_money * $data['bet_score'];
            if($user_info) {
                $score = intval($bet_money * $data['bet_score']) - $user_info['get_bet_score'];
            }
            $types = $ScoreBillModel::SCORE_BET;
            $stat_update = ['bet_score'=>$score];
        }

        try{
            Db::startTrans();
            try {
                if($score > 0){
                    $row = $ScoreBillModel->addIntvie($user_info, $cid, $uid, $types, $score);
                    app('app\common\model\ScoreStat')->add($stat_update);
                    Db::commit();
                    if($type === 'sign'){
                        Cache::store('redis')->set('is_sign'.$cid.'_'.$uid, ['date'=>date('Y-m-d')]);
                    }
                }else{
                    return error('Não há nada disponível para afirmar');
                }
            } catch (\Exception $e) {
                Db::rollback();
                return error($e->getMessage(), 500);
            }
        }finally {
            $redis->del($lockKey); // 处理完成后删除锁
        }
        return success("Recebido com sucesso");//领取成功
    }
    /**
     * @Apidoc\Title("商品列表")
     * @Apidoc\Desc("商品列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("商品")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("keyword", type="string",require=false, desc="搜索时候传：商品名称")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="商品列表",table="cp_goods")
     */
    public function get_goods_lists(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'sort desc');
        $goodsModel = app('app\common\model\Goods');
        $list = $goodsModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("兑换商品")
     * @Apidoc\Desc("兑换商品")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("兑换商品")
     * @Apidoc\Param("gid", type="int",require=true, desc="商品ID")
     * @Apidoc\Param("name", type="string",require=true, desc="收货人姓名")
     * @Apidoc\Param("phone", type="string",require=true, desc="收货人电话")
     * @Apidoc\Param("address", type="string",require=true, desc="收货人地址")
     */
    public function exchange_goods(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $gid = input("gid",0);
        $name = input("name","");
        $phone = input("phone","");
        $address = input("address","");
        if(empty($name) || empty($phone) || empty($address)) return error("Por favor, preenche a informação de recepção");    //请填写收货信息

        $goods = app('app\common\model\Goods')->find($gid);
        if(!$goods) return error("O produto não existe");  //商品不存在
        $UserModel = model('app\common\model\User',$cid);
        $user = $UserModel->getInfo($uid);
        $user_info = Db::name('user_info')->where("cid","=",$cid)->where("uid","=",$uid)->find();
        if(empty($user_info)) return error("Pontos insuficientes");  //积分不足
        if($goods['score'] > $user_info['score']) return error("Pontos insuficientes");  //积分不足

        $data = [
            'cid' => $cid,
            'uid' => $uid,
            'mobile' => $user['mobile'],
            'inv_code' => $user['inv_code'],
            'gid' => $gid,
            'num' => 1,
            'score' => $goods['score'],
            'title' =>  $goods['title'],
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'add_time' => time(),
        ];

        Db::startTrans();
        try {
            $count = app('app\common\model\GoodsOrder')
                ->where("cid","=",$cid)
                ->where("uid","=",$uid)
                ->where('add_time',">=",time())
                ->count();
            $row = app('app\common\model\GoodsOrder')->insert($data);
            if($row){
                $ScoreBillModel = app('app\common\model\ScoreBill');
                $ScoreBillModel->addIntvie($user_info, $cid, $uid, $ScoreBillModel::EXCHANGE_GOODS, -$goods['score']);
                if($count == 0){
                    app('app\common\model\ScoreStat')->add(['exchange_num'=>1]);
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage(), '500');
        }
        return success("Troca bem sucedida");
    }
    /**
     * @Apidoc\Title("积分明细列表")
     * @Apidoc\Desc("积分明细列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("积分明细列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="积分明细",table="cp_score_bill",children={
     *      @Apidoc\Returned("mobile",type="string",desc="用户手机号"),
     *      @Apidoc\Returned("inv_code",type="int",desc="用户邀请码")
     *  })
     */
    public function score_bill(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $where[] = ['b.cid', '=', $cid];
        $where[] = ['b.uid', '=', $uid];
        $limit = input("limit",10);
        $orderBy = input("orderBy", 'id desc');
        $goodsModel = app('app\common\model\ScoreBill');
        $list = $goodsModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
}
