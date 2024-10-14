<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 充值相关接口
 * @Apidoc\Title("充值相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(6)
 */
class Pay extends Base
{
    /**
     * @Apidoc\Title("充值价格表")
     * @Apidoc\Desc("充值价格表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("充值价格表")
     * @Apidoc\Returned(type="array",desc="游戏平台列表",table="cp_recharge")
     */
    public function get_recharge_list()
    {
        $cid = $this->request->cid;
        $where[] = ['cid','=',$cid];
        $list = model('app\common\model\Recharge')->lists($where);
        return success("obter sucesso",$list);   //获取成功
    }
    /**
     * @Apidoc\Title("支付列表")
     * @Apidoc\Desc("支付列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("支付列表")
     * @Apidoc\Returned("name",type="string",desc="支付名称")
     * @Apidoc\Returned("code",type="string",desc="支付CODE")
     */
    public function pay_list(){
        $config = get_config();
        if($config['pay_config'] === 'CapivaraPay'){
            $list = [['name' => 'PIX B','code' => 'CapivaraPay'],['name' => 'PIX A','code' => 'KirinPay']];
        }else{
            $list = [['name' => 'PIX A','code' => 'KirinPay'],['name' => 'PIX B','code' => 'CapivaraPay']];
        }
        return success("obter sucesso",$list);   //获取成功
    }
    /**
     * @Apidoc\Title("充值接口")
     * @Apidoc\Desc("充值接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("充值接口")
     * @Apidoc\Param("rid", type="int",require=true, desc="充值价格表ID")
     * @Apidoc\Param("money", type="float",require=true, desc="充值金额")
     * @Apidoc\Param("pay_code", type="string",require=true, desc="支付平台code")
     * @Apidoc\Returned("url",type="string",desc="支付链接")
     * @Apidoc\Returned("qrcode",type="string",desc="支付二维码")
     */
    public function pay()
    {
        $uid = $this->request->uid;
        $cid = $this->request->cid;
        $rid = $this->request->post('rid',0);
        $money = $this->request->post('money',0);
        $pay_code = $this->request->post('pay_code','');
        if($pay_code == '') return error('Por favor, selecione o método de pagamento');  //请选择支付方式
        $gifts = 0;
        $multiple = 0;
        $BankModel = model('app\common\model\Bank');
        $row = $BankModel->getInfo($cid,$uid);
        if(!$row) return error('Por favor, vincule seu cartão bancário primeiro',102);  //请先绑定银行卡
        if($rid > 0){
            $recharge = model('app\common\model\Recharge')->getInfo($rid,$cid);
            if($recharge){
                $gifts = $recharge['gifts'];
                $multiple = $recharge['multiple'];
            }
        }
        $channel = model('app\common\model\Channel')->info($cid);
        if(!$channel){
            return error('O canal não existe');        //渠道不存在
        }
        if($money < $channel['min_recharge']){
            return error('O valor da recarga não pode ser inferior a '.$channel['min_recharge']);  //充值金额不能小于
        }
        $user = model('app\common\model\User',$cid)->getInfo($uid);
        if(!$user){
            return error('Usuário não existe');      //用户不存在
        }
        $merOrderNo = $cid.'_'.getSn("CZ");

        $id = model('app\common\model\Order',$cid)->add($cid,$uid,$merOrderNo,$money,$row['pix'],$gifts,$multiple);
        if(!$id) return error('Falha na geração do pedido');    //订单生成失败
        //$config = get_config();
        $payClass = app('app\service\pay\KirinPay');
        if($pay_code){
            $payClass = app('app\service\pay\\'.$pay_code);
        }
        /*if(isset($config['pay_config'])){
            $payClass = app('app\service\pay\\'.$config['pay_config']);
        }*/
        $res = $payClass->pay($merOrderNo,$money,$row['pix']);
        //$res = json_decode($res,true);
        if($res['code'] == 0){
            $data = [
                'url' => $res['data']['paymentLinkUrl'],
            ];
            return success("Pedido criado com sucesso",$data);    //创建订单成功
        }else{
            return error($res['msg']);
        }
    }
}
