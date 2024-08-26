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
     * @Apidoc\Title("等级页面")
     * @Apidoc\Desc("等级页面")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("等级页面")
     * @Apidoc\Returned("user",type="array",desc="用户信息",table="cp_user",children={
     *     @Apidoc\Returned("level_img",type="string",desc="等级图片"),
     *     @Apidoc\Returned("level_exp",type="int",desc="当前等级的经验"),
     *     @Apidoc\Returned("nex_level",type="int",desc="下级所需经验")
     * })
     * @Apidoc\Returned("level",type="array",desc="等级列表",table="cp_level")
     * @Apidoc\Returned("is_get_day",type="int",desc="每日奖励：0=不能领取；1=可以领取")
     * @Apidoc\Returned("is_get_week",type="int",desc="每周奖励：0=不能领取；1=可以领取")
     */
    public function get_level(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $list = app('app\common\model\Level')->getList();
        $UserModel = model('app\common\model\User',$cid);
        $user = $UserModel->getInfo($uid);
        $level = $user['level'];
        foreach($list as $k => $v){
            if($level == $v['level']){
                $user['level_img'] = $v['img'];
                $user['exp'] = (int)$user['exp'];
                $user['level_exp'] = $v['exp'];
                $user['nex_level'] = $list[$k+1]['exp'] - (int)$user['exp'];
                $level_info = $v;
                break;
            }
        }
        $is_get_day = $is_get_week = 1;

        $day_count = app('app\common\model\LevelLog')
            ->where('cid', $cid)
            ->where('uid', $uid)
            ->where('type', '=', 1)
            ->where('add_time', '>=', date('Y-m-d H:i:s', time()))
            ->count();
        $UserStatModel = model('app\common\model\UserStat', $cid);
        if($day_count >= 1 || $level_info['beet_back_day'] <= 0){
            $is_get_day = 0;
        }else{
            $startTime = date("Y-m-d 00:00:00", strtotime("-1 day"));
            $endTime = date("Y-m-d 23:59:59", strtotime("-1 day"));
            $userStat = $UserStatModel->get_total_bet_amount($uid, $startTime, $endTime);
            $betMoney = $userStat['bet_money'];
            if($betMoney <= 0){
                $is_get_day = 0;
            }
        }

        $start_time = strtotime('monday this week');
        $week_count = app('app\common\model\LevelLog')
            ->where('cid', $cid)
            ->where('uid', $uid)
            ->where('type', '=', 2)
            ->where('add_time', '>=', date('Y-m-d H:i:s', $start_time))
            ->count();
        if($week_count >= 1 || $level_info['week_back'] <= 0){
            $is_get_week = 0;
        }else{
            $startTime = date("Y-m-d 00:00:00", strtotime("last week Monday"));
            $endTime = date("Y-m-d 23:59:59", strtotime("last week Sunday"));
            $userStat = $UserStatModel->get_total_bet_amount($uid, $startTime, $endTime);
            $betMoney = $userStat['bet_money'];
            if($betMoney <= 0){
                $is_get_day = 0;
            }
        }
        $data = [
            'level' => $list,
            'is_get_day' => $is_get_day,
            'is_get_week' => $is_get_week,
            'user' => $user
        ];
        return success("obter sucesso",$data);//获取成功
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
        $UserModel = model('app\common\model\User', $cid);
        $user = $UserModel->getInfo($uid);
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
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("type", type="int",require=true,default=1,desc="下级类型：1=一级；2=二级；3=三级")
     * @Apidoc\Param("date", type="int",require=true,default=5,desc="时间：1=今天；2=本周；3=本月；4=本年；5=全部")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="团队投注，充值列表",ref="app\common\model\UserStat@team")
     */
    public function team(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $type = $this->request->post('type');
        $date = $this->request->post('date',5);
        $limit = $this->request->post('limit',10);
        $where = [];
        if($type == 1){
            $where[] = ['pid',"=",$uid];
        }else if($type == 2){
            $where[] = ['ppid',"=",$uid];
        }else if($type == 3){
            $where[] = ['pppid',"=",$uid];
        }
        $date_where = $this->get_time($date);
        if($date_where){
            $where[] = $date_where;
        }
        $UserStat = model('app\common\model\User',$cid);
        $list = $UserStat->team($where,$limit);    //数据
        return success("obter sucesso",$list);//获取成功
    }
    protected function get_time($date){
        $where = [];
        switch($date){
            case 1:
                $start_time = strtotime(date("Y-m-d"));
                //获取明天的日期
                $where = ['reg_time',">=",$start_time];
                break;
            case 2:
                //创建一个本周开始时间和结束时间的条件
                $start_time = strtotime('monday this week');
                $end_time = time();
                $where = ['reg_time', 'between', [$start_time, $end_time]];
                break;
                //创建一个本月开始时间和结束时间的条件
            case 3:
                $start_time = strtotime(date('Y-m-01'));
                $end_time = time();
                $where = ['reg_time', 'between', [$start_time, $end_time]];
                break;
                //创建一个今年开始时间和当前为结束时间的条件
            case 4:
                $start_time = strtotime(date('Y-01-01'));
                $end_time = time();
                $where = ['reg_time', 'between', [$start_time, $end_time]];
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
        if ($channel && $channel['cz_money'] > 0 && $channel['bet_money'] > 0) {
            $where[] = ['u.pid', "=", $uid];
            $box_num = $UserStat->get_box_num($where, $channel['cz_money'], $channel['bet_money']);//宝箱人数
        }
        return array($UserStat, $box_num);
    }
    /**
     * @Apidoc\Title("站内信")
     * @Apidoc\Desc("站内信")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("站内信")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("orderBy", type="string",require=false, desc="字段排序")
     * @Apidoc\Param("limit", type="int",require=true, desc="每页的条数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="站内信",table="cp_mail")
     */
    public function mail(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $limit = input("limit",10);
        $orderBy = input("orderBy", 'id desc');
        $where[] = ['uid',"=",$uid];
        $where[] = ['cid',"=",$cid];
        $list = app('app\common\model\Mail')->getList($where,$limit,$orderBy);
        return success("obter sucesso",$list);//获取成功
    }
    /**
     * @Apidoc\Title("删除站内信")
     * @Apidoc\Desc("删除站内信")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("删除站内信")
     * @Apidoc\Param("id", type="int",require=true, desc="需要删除的数据")
     */
    public function del_mail(){
        $id = input("id");
        if(empty($id)) return error("Por favor seleccione os dados a suprimir");
        app('app\common\model\Mail')->where('id','=',$id)->delete();
        return success("Eliminar com sucesso"); //删除成功
    }
    /**
     * @Apidoc\Title("领取每日返点")
     * @Apidoc\Desc("领取每日返点")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("领取每日返点")
     */
    public function get_day_level() {
        return $this->get_level_reward('day');
    }
    /**
     * @Apidoc\Title("领取每周返点")
     * @Apidoc\Desc("领取每周返点")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("领取每周返点")
     */
    public function get_week_level() {
        return $this->get_level_reward('week');
    }

    private function get_level_reward($type) {
        $cid = $this->request->cid;
        $uid = $this->request->uid;

        // 根据类型获取时间范围
        if ($type === 'day') {
            $startTime = date("Y-m-d 00:00:00", strtotime("-1 day"));
            $endTime = date("Y-m-d 23:59:59", strtotime("-1 day"));
            $logType = 1;
            $rewardField = 'beet_back_day';
            $start_time = time();
        } else if ($type === 'week') {
            $startTime = date("Y-m-d 00:00:00", strtotime("last week Monday"));
            $endTime = date("Y-m-d 23:59:59", strtotime("last week Sunday"));
            $logType = 2;
            $rewardField = 'week_back';
            $start_time = strtotime('monday this week');
        } else {
            return error("无效的类型");
        }

        $user = model('app\common\model\User', $cid)->getInfo($uid);
        $count = app('app\common\model\LevelLog')
            ->where('cid', $cid)
            ->where('uid', $uid)
            ->where('type', '=', $logType)
            ->where('add_time', '>=', date('Y-m-d H:i:s', $start_time))
            ->count();

        if ($count > 0) {
            return error('Já recebido, por favor não receba novamente'); //已领取，请勿重复领取
        }

        $level = app('app\common\model\Level')->where('level', $user['level'])->find();
        if (empty($level)) {
            return error("O nível não existe"); //等级不存在
        }

        if ($level[$rewardField] > 0) {
            $UserStatModel = model('app\common\model\UserStat', $cid);
            $userStat = $UserStatModel->get_total_bet_amount($uid, $startTime, $endTime);
            $betMoney = $userStat['bet_money'];
            $money = 0;
            if ($betMoney > 0) {
                $money = round($betMoney * ($level[$rewardField] / 100), 2);
            }
            if ($money > 0) {
                Db::startTrans();
                try {
                    $BillModel = model('app\common\model\Bill', $cid);
                    $BillModel->addIntvie($user, $logType === 1 ? $BillModel::DAY_LEVEL_MONEY : $BillModel::WEEK_LEVEL_MONEY, $money, 0, $level['multiple']);
                    $row = app('app\common\model\LevelLog')->add($cid, $uid, $user['level'], $money, $logType);
                    if (!$row) {
                        Db::rollback();
                        return error("Não foi possível obter", 500);    //获取失败
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return error($e->getMessage());
                }
            } else {
                return error("Não há recompensas para reivindicar");   //没有可领取的奖励
            }
        }else{
            return error("Não há recompensas para reivindicar");   //没有可领取的奖励
        }
        return success("obter sucesso"); //获取成功
    }


}