<?php
namespace app\api\controller;

use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

/**
 * 工资相关接口
 * @Apidoc\Title("工资相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(4)
 */
class Wages extends Base
{
    /**
     * @Apidoc\Title("工资信息")
     * @Apidoc\Desc("工资信息获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户信息")
     * @Apidoc\Returned("money", type="float", desc="已获得的工资")
     * @Apidoc\Returned("un_money", type="int", desc="未获得的工资")
     */
    public function get_wages()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;

        $user = $this->getUserInfo($cid, $uid);
        write_log('用户手机号:'.$user['mobile'],'wages');
        if (!$user) {
            return error("Usuário não existe");//用户不存在
        }
        $config = $this->getWagesConfig($cid);
        write_log($config,'wages');
        if (!$config) {
            return error("A configuração salarial não existe");
        }
        $wages = $this->getWagesInfo($cid, $uid);
        $czInfo = $this->getCzInfo($cid, $uid, $config);
        $un_money = ($czInfo['bozhu_money'] + $czInfo['daili_money'] + $czInfo['n3_money']) - $wages['bozhu'] - $wages['daili'] - $wages['n3'];
        $data = [
            'money' => round($wages['bozhu'] + $wages['daili'] + $wages['n3'],2),
            'un_money' => round($un_money,2)
        ];

        return success("obter sucesso",$data);//获取成功
    }

    /**
     * @Apidoc\Title("领取工资")
     * @Apidoc\Desc("领取工资")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("领取工资")
     */
    public function receive_wages()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $redis = Cache::store('redis')->handler();
        $lockKey = "user_receive_box_lock_{$uid}";
        if ($redis->exists($lockKey)) {
            return error('O pedido está sendo atualmente processado, por favor tente de novo mais tarde');
        }
        $redis->set($lockKey, true, 5); // 设置锁，60秒后过期
        try{
            $user = $this->getUserInfo($cid, $uid);
            if (!$user) {
                return error("o usuário não existe");
            }

            $wages = $this->getWagesInfo($cid, $uid);
            $config = $this->getWagesConfig($cid);
            if (!$config) {
                return error("A configuração salarial não existe");
            }

            $czInfo = $this->getCzInfo($cid, $uid, $config);

            Db::startTrans();
            try {
                $user = $this->processWages($user, $wages, $czInfo, $config);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return error($e->getMessage(), 500);
            }
            $data = [
                'money' => round($czInfo['bozhu_money'] + $czInfo['daili_money'] + $czInfo['n3_money'],2),
                'un_money' =>  0,
                'user' => $user
            ];
            return success("obter sucesso",$data);//获取成功
        }  finally {
            $redis->del($lockKey); // 处理完成后删除锁
        }
    }

    private function getUserInfo($cid, $uid)
    {
        $UserModel = model('app\common\model\User', $cid);
        return $UserModel->getInfo($uid);
    }

    private function getWagesInfo($cid, $uid)
    {
        $WagesModel = model('app\common\model\Wages', $cid);
        return $WagesModel->get_money($uid);
    }

    private function getWagesConfig($cid)
    {
        $WagesConfig = model('app\common\model\WagesConfig');
        return $WagesConfig->getInfo($cid);
    }

    private function getCzInfo($cid, $uid, $config)
    {
        $UserStat = model('app\common\model\UserStat', $cid);

        $czNumBozhu = $UserStat->get_deposit_num([['u.pid', '=', $uid]]);
        write_log('博主充值人数:'.$czNumBozhu,'wages');
        $czMoneyBozhu = $UserStat->get_deposit_and_bet([['u.pid', '=', $uid]])['cz_money'] ?? 0.00;
        write_log('博主充值金额:'.$czMoneyBozhu,'wages');
        $czNumDaili = $UserStat->get_deposit_num([['u.ppid', '=', $uid]]);
        write_log('代理充值人数:'.$czNumDaili,'wages');
        $czMoneyDaili = $UserStat->get_deposit_and_bet([['u.ppid', '=', $uid]])['cz_money'] ?? 0.00;
        write_log('代理充值金额:'.$czMoneyDaili,'wages');
        $czNumN3 = $UserStat->get_deposit_num([['u.pppid', '=', $uid]]);
        write_log('N3充值人数:'.$czNumN3,'wages');
        $czMoneyN3 = $UserStat->get_deposit_and_bet([['u.pppid', '=', $uid]])['cz_money'] ?? 0.00;
        write_log('代理充值金额:'.$czMoneyN3,'wages');

        $bozhuMoney = $dailiMoney = $n3Money =  0;
        if ($config['type'] == 1) {
            $bozhuMoney = calculateSalary($czNumBozhu, $czMoneyBozhu, $config) * $config['bozhu'];
            $dailiMoney = calculateSalary($czNumDaili, $czMoneyDaili, $config) * $config['daili'];
            $n3Money = calculateSalary($czNumN3, $czMoneyN3, $config) * $config['n3'];
        } else {
            if($czNumBozhu >= $config['cz_num']){
                $bozhuMoney = $czMoneyBozhu * ($config['bozhu'] / 100);
            }
            if($czNumDaili >= $config['cz_num']){
                $dailiMoney = $czMoneyDaili * ($config['daili'] / 100);
            }
            if($czNumN3 >= $config['cz_num']){
                $n3Money = $czMoneyN3 * ($config['n3'] / 100);
            }
        }

        return ['bozhu_money' => $bozhuMoney, 'daili_money' => $dailiMoney,'n3_money' => $n3Money];
    }

    private function processWages($user, $wages, $czInfo, $config)
    {
        $BillModel = model('app\common\model\Bill', $user['cid']);
        $WagesModel = model('app\common\model\Wages', $user['cid']);

        $bozhuUnMoney = $czInfo['bozhu_money'] - $wages['bozhu'];
        $dailiUnMoney = $czInfo['daili_money'] - $wages['daili'];
        $N3UnMoney = $czInfo['n3_money'] - $wages['n3'];

        if ($bozhuUnMoney > 0) {
            $row = $BillModel->addIntvie($user, $BillModel::WAGES_BOZHU, $bozhuUnMoney);
            $user = $row['user'];
            $WagesModel->add($user, $bozhuUnMoney, 1, $config['type']);
        }

        if ($dailiUnMoney > 0) {
            $row = $BillModel->addIntvie($user, $BillModel::WAGES_DAILI, $dailiUnMoney);
            $user = $row['user'];
            $WagesModel->add($user, $dailiUnMoney, 2, $config['type']);
        }
        if ($N3UnMoney > 0) {
            $row = $BillModel->addIntvie($user, $BillModel::WAGES_N3, $N3UnMoney);
            $user = $row['user'];
            $WagesModel->add($user, $N3UnMoney, 3, $config['type']);
        }
        return $user;
    }
}