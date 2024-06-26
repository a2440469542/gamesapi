<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

/**
 * 用户相关接口
 * @Apidoc\Title("用户相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(4)
 */
class User extends Base
{
    /**
     * @Apidoc\Title("用户信息")
     * @Apidoc\Desc("用户信息获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户信息")
     * @Apidoc\Returned(type="array",desc="用户信息",table="cp_user")
     */
    public function user_info(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $UserModel = app('app\common\model\User');
        $UserModel->setPartition($cid);
        $user = $UserModel->getInfo($uid);
        if(!$user){
            return error("Usuário não existe");  //用户不存在
        }
        return success("obter sucesso",$user);//获取成功
    }
    /**
     * @Apidoc\Title("获取当前渠道用户宝箱列表")
     * @Apidoc\Desc("获取当前渠道用户宝箱列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("宝箱")
     * @Apidoc\Returned("user_num",type="int",desc="需要达到的有效人数")
     * @Apidoc\Returned("invite_num",type="int",desc="当前用户邀请的有效人数")
     * @Apidoc\Returned("is_get",type="int",desc="是否达到要求领取：0=未达到要求；1=达到要求")
     * @Apidoc\Returned("status",type="int",desc="是否领取：0=未领取；1=已领取")
     * @Apidoc\Returned("money",type="float",desc="宝箱金额")
     */
    public function get_box(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $BoxModel = app('app\common\model\Box');
        $where[] = ['cid',"=",$cid];
        list($UserStat, $box_num) = $this->extracted($cid, $uid);
        $BoxLogModel = app('app\common\model\BoxLog');
        $log = $BoxLogModel->getLastTask($cid,$uid);
        if($log){
            $task = $log['task'];
            foreach ($task as $k => &$v) {
                if($box_num >= $v['user_num']){
                    $v['is_get'] = 1;
                }
                if($v['status'] == 0){
                    $v['invite_num'] = $box_num;
                }
            }
        }else{
            $list = $BoxModel->lists($where);
            $task = [];
            foreach ($list as $k => $v) {
                foreach ($v['user_num'] as $kk => $vv) {
                    $is_get = 0;
                    if($box_num >= $vv){
                        $is_get = 1;
                    }
                    $task[] = [
                        'user_num' => $vv,          //有效邀请人数
                        'invite_num' => $box_num,   //当前用户邀请的有效人数
                        'is_get' => $is_get,        //能否获得
                        'status' => 0,              //是否领取
                        'money' => $v['money']      //获得金额
                    ];
                }
            }
            $BoxLogModel->add($cid,$uid,$task);
        }
        return success("obter sucesso",$task);//获取成功
    }
    /**
     * @Apidoc\Title("领取宝箱奖励")
     * @Apidoc\Desc("领取宝箱奖励")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("领取宝箱奖励")
     * @Apidoc\Returned("user_num",type="int",desc="需要达到的有效人数")
     * @Apidoc\Returned("invite_num",type="int",desc="当前用户邀请的有效人数")
     * @Apidoc\Returned("is_get",type="int",desc="是否达到要求领取：0=未达到要求；1=达到要求")
     * @Apidoc\Returned("status",type="int",desc="是否领取：0=未领取；1=已领取")
     * @Apidoc\Returned("money",type="float",desc="宝箱金额")
     */
    public function receive_box(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $user = $this->request->user;
        if($user['is_rebot'] === 1) return error('A conta de teste não pode ser reivindicada');  //测试账号不能领取
        $redis = Cache::store('redis')->handler();
        $lockKey = "user_receive_box_lock_{$uid}";
        if ($redis->exists($lockKey)) {
            return error('O pedido está sendo atualmente processado, por favor tente de novo mais tarde');
        }
        $redis->set($lockKey, true, 5); // 设置锁，60秒后过期
        try {
            list($UserStat, $box_num) = $this->extracted($cid, $uid);
            $BoxLogModel = model('app\common\model\BoxLog');
            $log = $BoxLogModel->getLastTask($cid, $uid);
            if (empty($log)) return error("Baú do tesouro não existe");        //宝箱不存在
            $money = 0;
            $task = $log['task'];
            foreach ($task as &$v) {
                if ($box_num >= $v['user_num'] && $v['status'] == 0) {
                    $money = $v['money'];
                    $v['is_get'] = 1;
                    $v['status'] = 1;
                    break;
                }
            }
            if ($money == 0) return error("Nenhum peito de tesouro para afirmar", 500);        //宝箱不存在
            Db::startTrans();
            try {
                $UserModel = model('app\common\model\User', $cid);
                $user = $UserModel->getInfo($uid);
                $BillModel = model('app\common\model\Bill', $cid);
                $BillModel->addIntvie($user, $BillModel::BOX_MONEY, $money);
                $row = $BoxLogModel->add($cid, $uid, $task, $money, $log['num'] + 1);
                if (!$row) {
                    Db::rollback();
                    return error("obter falha", 0);//获取失败
                }
                $user_stat = ['box_money' => $money];
                $UserStat->add($user,$user_stat);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return error($e->getMessage(), 'cash');
            }
            return success("obter sucesso", $task);//获取成功
        }finally {
            $redis->del($lockKey); // 处理完成后删除锁
        }
    }
    /**
     * @Apidoc\Title("获取当前用户的团队统计数据")
     * @Apidoc\Desc("获取当前用户的团队统计数据")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("获取当前用户的团队统计数据")
     * @Apidoc\Param("type", type="int",require=true,default=1,desc="下级类型：1=一级；2=二级；3=三级")
     * @Apidoc\Returned("invite",type="int",desc="邀请人数")
     * @Apidoc\Returned("box_num",type="int",desc="满足宝箱人数")
     * @Apidoc\Returned("recharge",type="int",desc="有效存款人")
     * @Apidoc\Returned("cz_money",type="float",desc="总存款")
     * @Apidoc\Returned("bet_money",type="float",desc="总投注额")
     */
    public function get_team_total(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $type = $this->request->post('type');
        $where = [];
        if($type == 1){
            $where[] = ['u.pid',"=",$uid];
        }else if($type == 2){
            $where[] = ['u.ppid',"=",$uid];
        }else if($type == 3){
            $where[] = ['u.pppid',"=",$uid];
        }
        $UserStat = model('app\common\model\UserStat',$cid);
        //从数据库获取自己下级的邀请人数和满足宝箱人数，存款人数，总存款数，总投注额的代码
        $UserModel = model('app\common\model\User',$cid);
        $invite = $UserModel->get_child_num($where);//邀请人数
        $channel = model('app\common\model\Channel')->info($cid);
        $box_num = 0;
        if($channel){
            $box_num = $UserStat->get_box_num($where,$channel['cz_money'],$channel['bet_money']);//宝箱人数
        }
        $recharge = $UserStat->get_deposit_num($where);//存款人数
        $cz_bet = $UserStat->get_deposit_and_bet($where);//总存款数和总投注
        $data = [
            'invite' => $invite,
            'box_num' => $box_num,
            'recharge' => $recharge,
            'cz_money' => $cz_bet['cz_money'] ? round($cz_bet['cz_money'],2) : '0.00',
            'bet_money' => $cz_bet['bet_money'] ? round($cz_bet['bet_money'],2) : '0.00',
        ];
        return success("obter sucesso",$data);//获取成功
    }
    /**
     * @Apidoc\Title("获取当前用户的团队投注，充值列表")
     * @Apidoc\Desc("获取当前用户的团队投注，充值列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("宝箱")
     * @Apidoc\Param("type", type="int",require=true,default=1,desc="下级类型：1=一级；2=二级；3=三级")
     * @Apidoc\Param("date", type="int",require=true,default=5,desc="时间：1=今天；2=本周；3=本月；4=本年；5=全部")
     * @Apidoc\Returned(type="array",desc="团队投注，充值列表",ref="app\common\model\UserStat@team")
     */
    public function team(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $type = $this->request->post('type');
        $date = $this->request->post('date',5);
        $where = [];
        if($type == 1){
            $where[] = ['u.pid',"=",$uid];
        }else if($type == 2){
            $where[] = ['u.ppid',"=",$uid];
        }else if($type == 3){
            $where[] = ['u.pppid',"=",$uid];
        }
        $date_where = $this->get_time($date);
        if($date_where){
            $where[] = $date_where;
        }
        $UserStat = model('app\common\model\UserStat',$cid);
        $list = $UserStat->team($where);    //数据
        return success("obter sucesso",$list);//获取成功
    }
    protected function get_time($date){
        $where = [];
        switch($date){
            case 1:
                $start_time = date("Y-m-d");
                //获取明天的日期
                $where = ['date',"=",$start_time];
                break;
            case 2:
                //创建一个本周开始时间和结束时间的条件
                $start_time = date("Y-m-d",strtotime('monday this week'));
                $end_time = date("Y-m-d");
                $where = ['date', 'between', [$start_time, $end_time]];
                break;
                //创建一个本月开始时间和结束时间的条件
            case 3:
                $start_time = date('Y-m-01');
                $end_time = date('Y-m-d');
                $where = ['date', 'between', [$start_time, $end_time]];
                break;
                //创建一个今年开始时间和当前为结束时间的条件
            case 4:
                $start_time = date('Y-01-01');
                $end_time = date('Y-m-d');
                $where = ['date', 'between', [$start_time, $end_time]];
                break;
        }
        return $where;
    }

    /**
     * @param mixed $cid
     * @param mixed $uid
     * @return array
     */
    protected function extracted(mixed $cid, mixed $uid): array
    {
        $UserStat = model('app\common\model\UserStat', $cid);
        //从数据库获取自己下级的邀请人数和满足宝箱人数，存款人数，总存款数，总投注额的代码
        $channel = model('app\common\model\Channel')->info($cid);
        $box_num = 0;
        if ($channel) {
            $where[] = ['u.pid', "=", $uid];
            $box_num = $UserStat->get_box_num($where, $channel['cz_money'], $channel['bet_money']);//宝箱人数
        }
        return array($UserStat, $box_num);
    }
}
