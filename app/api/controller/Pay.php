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
     * @Apidoc\Title("充值接口")
     * @Apidoc\Desc("充值接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("充值接口")
     * @Apidoc\Param("rid", type="int",require=true, desc="充值价格表ID")
     * @Apidoc\Param("money", type="float",require=true, desc="充值金额")
     * @Apidoc\Returned("url",type="string",desc="支付链接")
     * @Apidoc\Returned("qrcode",type="string",desc="支付二维码")
     */
    public function pay()
    {
        $uid = $this->request->uid;
        $cid = $this->request->cid;
        $rid = $this->request->post('rid',0);
        $money = $this->request->post('money',0);
        $gifts = 0;
        if($rid > 0){
            $recharge = model('app\common\model\Recharge')->getInfo($rid,$cid);
            if($recharge){
                $gifts = $recharge['gifts'];
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
        $id = model('app\common\model\Order',$cid)->add($cid,$uid,$merOrderNo,$money,$gifts);
        if(!$id) return error('Falha na geração do pedido');    //订单生成失败
        $BetcatPay = app('app\service\pay\KirinPay');
        $res = $BetcatPay->pay($merOrderNo,$money);
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
