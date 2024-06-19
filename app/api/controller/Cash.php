<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;

/**
 * 用户提现相关接口
 * @Apidoc\Title("用户提现相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(9)
 */
class Cash extends Base
{
    /**
     * @Apidoc\Title("获取用户银行卡")
     * @Apidoc\Desc("获取用户银行卡")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("获取用户银行卡")
     * @Apidoc\Returned(type="array",desc="游戏平台列表",table="cp_bank")
     */
    public function get_bank(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $BankModel = model('app\common\model\Bank');
        $row = $BankModel->getInfo($cid,$uid);
        return success("obter sucesso",$row);//获取成功
    }
    /**
     * @Apidoc\Title("用户绑定银行卡")
     * @Apidoc\Desc("用户绑定银行卡")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户绑定银行卡")
     * @Apidoc\Param("",type="array",table="cp_bank")
     */
    public function bind_bank()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $type = input('type','CPF');
        $pix =  input('pix','');
        $mobile =  input('mobile','');
        $name =  input('name','');
        $id  =  input('id',0);
        if(!in_array($type,['CPF','PHONE'])){
            return error('Erro de parâmetro');   //参数错误
        }
        if($pix == '' || $name == '') return error('Erro de parâmetro');//参数错误
        if($type == 'PHONE' && $mobile == '') return error('Erro de parâmetro');//参数错误
        $BankModel = model('app\common\model\Bank');
        $row = $BankModel->getInfo($cid,$uid);
        if($row) $id = $row['id'];
        $res = $BankModel->add($cid,$uid,$type,$mobile,$pix,$name,$id);
        if($res){
            return success('Vinculação bem-sucedida'); //绑定成功
        }else{
            return error('Falha na vinculação');//绑定失败
        }
    }
    /**
     * @Apidoc\Title("用户提现接口")
     * @Apidoc\Desc("用户提现接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户提现接口")
     * @Apidoc\Param("money",type="float",desc="提现金额")
     */
    public function cash(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $redis = Cache::store('redis')->handler();
        $lockKey = "user_request_lock_{$uid}";
        if ($redis->exists($lockKey)) {
            return error('O pedido está sendo atualmente processado, por favor tente de novo mais tarde');
        }
        $redis->set($lockKey, true, 5); // 设置锁，60秒后过期
        // 处理请求
        try {
            $money = input('money',0);
            $channel = model('app\common\model\Channel')->info($cid);
            if($money < $channel['min_draw']) return error('O saque mínimo não pode ser inferior a :'.$channel['min_draw']);  //最低提现不能低于
            $CashModel = model('app\common\model\Cash',$cid);
            if($CashModel->hasCashRecord($uid)) return error('Há uma retirada em andamento, aguarde até que este registro seja retirado com sucesso.');    //有一笔在提现中，请等待此笔记录提现成功
            $user = model('app\common\model\User',$cid)->getInfo($uid);
            $BankModel = model('app\common\model\Bank');
            $row = $BankModel->getInfo($cid,$uid);
            if(!$row) return error('Por favor, vincule seu cartão bancário primeiro',102);  //请先绑定银行卡
            if($user['water'] > 0) return error('O faturamento não foi atingido e o saque não pode ser feito.');//流水未达到，无法提现
            if($user['money'] < $money) return error('Saldo insuficiente');   //余额不足
            if($user['is_rebot'] === 1) return error('O robô não pode fazer retiradas');  //测试账号不能提现
            $order_sn = $cid.'_'.getSn("TX");
            $account = $row['type'] == 'CPF' ? $row['pix'] : $row['mobile'];
            $res = $CashModel->add($cid,$uid,$order_sn,$row['type'],$account,$row['pix'],$row['name'],$money);
            if($res){
                $BetcatPay = app('app\service\pay\BetcatPay');
                $res = $BetcatPay->cash_out($order_sn ,"BRL",$money,$row['type'],$account,$row['pix'],$row['pix'],$user);
                $res = json_decode($res,true);
                if($res['code'] != 0) return error($res['error']);
                return success('Retirada com sucesso'); //提现成功
            }else{
                return error('Falha na retirada');   //提现失败
            }
        }finally {
            $redis->del($lockKey); // 处理完成后删除锁
        }

    }
    /**
     * @Apidoc\Title("用户提现记录")
     * @Apidoc\Desc("用户提现记录")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户提现记录")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("orderBy", type="string",require=false, desc="字段排序")
     * @Apidoc\Param("limit", type="int",require=true, desc="每页的条数")
     * @Apidoc\Param("status", type="int",require=true, desc="状态：不传就是全部；0=待审核；1=审核通过；-1=拒绝提现；2=提现成功；-2=提现失败")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="用户列表",table="cp_cash")
     */
    public function log()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $status = input('status',"");
        $limit = input("limit",10);
        $orderBy = input("orderBy", 'add_time desc');
        $where[] = ['uid',"=",$uid];
        if($status != "") $where[] = ['status',"=",$status];
        $list = model('app\common\model\Cash',$cid)->getList($where,$limit,$orderBy);
        return success("obter sucesso",$list);  //获取成功
    }
}
