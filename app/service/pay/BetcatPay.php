<?php

namespace app\service\pay;
use think\facade\Db;
use think\facade\Log;
class BetcatPay
{
    private $get_app_id = '60c853830a4dbd5ee29be855716eaeb4';
    private $get_app_secret = '1c4ea41a5f7d5d0571217d36997a9c66';
    private $pay_app_id = '63f758408970163f45859815c72a054e';
    private $pay_app_secret = '63186d94908984910fe384e17f6f4246';
    private $api_url = 'https://v1.a.betcatpay.com';
    //代收订单创建

    /**
     * @param $merOrderNo   string 订单号
     * @param $currency     string 金额币种
     * @param $amount       string 订单金额
     * @return bool|string
     */
    public function pay(string $merOrderNo , string $amount,string $currency='BRL'){
        $data = [
            'appId' => $this->get_app_id,
            'merOrderNo' => $merOrderNo,
            'currency' => $currency,
            'amount' => $amount,
            'notifyUrl' => SITE_URL.'/api/notify/pay',
        ];
        $data["sign"] = $this->sign($data,$this->get_app_secret);
        $url = $this->api_url.'/api/v1/payment/order/create';
        return $this->curl($url,$data);
    }
    public function check_pay_sign($data){
        $sign = $this->verify_sign($data);
        write_log("====签名=====\n".$sign."\n",'pay');
        if($sign != $data['sign']){
            write_log("====签名错误=====\n",'pay');
            return false;
        }
        return true;
    }
    public function check_cash_sign($data){
        $sign = $this->verify_sign($data);
        write_log("====签名=====\n".$sign."\n",'cash');
        if($sign != $data['sign']){
            write_log("====签名错误=====\n",'cash');
            return false;
        }
        return true;
    }
    public function notify($data,$header){
        $sign = $this->verify_sign($data);
        write_log("====签名=====\n".$sign."\n",'cash');
        if($sign != $data['sign']){
            write_log("====签名错误=====\n",'cash');
            return false;
        }
        $order =  Cash::where("order_sn","=",$data['merOrderNo'])->find();
        if(empty($order)){
            write_log("====订单不存在=====\n".$sign."\n",'cash');
            return false;
        }
        if($order['status'] != 1){
            write_log("====订单状态错误=====\n".$sign."\n",'cash');
            return false;
        }
        if($data['orderStatus'] == 2){
            $order->status = 2;
            $order->orderno = $data['orderNo'];
            $order->save();
            $time = strtotime(date("Y-m-d",time()));
            $count = Cash::where('uid',"=",$order['uid'])->where('status',2)->where("add_time",">=",$time)->count();
            $statis_data['cash_money'] = $order->money;
            if($count <= 1) {
                $statis_data['cash_num'] = 1;
            }
            Statis::add($statis_data);
            write_log("====成功=====\n".$sign."\n",'cash');
            return true;
        }else if($data['orderStatus'] < 0){
            Db::startTrans();
            try{
                $res = Bill::addIntvie($order['uid'],Bill::CASH_FAIL,$order->money);
                if($res['code'] > 0){
                    Db::rollback();
                    return false;
                }
                $order->status = 3;
                $order->orderno = $data['orderNo'];
                $order->save();
                $user = Db::name('user')->where('id',$order->uid)->find();
                notifierCash($user['chat_id']);
                Db::commit();
                return true;
            }catch(\Exception $e){
                Db::rollback();
                write_log("====错误信息=====\n".$e->getMessage()."\n",'cash');
                return false;
            }
        }else{
            return true;
        }
    }
    public function cash_out(string $merOrderNo ,string $currency, string $amount,string $bankCode,string $accountNo,string $accountName,string $document,$user=[]){
        $data = [
            'appId' => $this->pay_app_id,
            'merOrderNo' => $merOrderNo,
            'currency' => $currency,
            'amount' => number_format($amount, 2, '.', ''),
            'notifyUrl' => SITE_URL.'/api/notify/cash_out',
            'extra' => [
                'bankCode' => $bankCode,
                'accountNo' => $accountNo,
                'accountName' => $accountName,
                'document' => $document,
            ],
        ];
        $data['sign'] = $this->sign($data,$this->pay_app_secret);
        write_log("签名:".$data['sign'],'cash_out');
        $url = $this->api_url.'/api/v1/payout/order/create';
        return $this->curl($url,$data);
    }
    public function curl($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        $tmpdatastr = is_array($data) ? http_build_query($data) : $data;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $tmpdatastr);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2); // 只使用TLS 1.2
        // curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1); // 只使用TLS 1.1
        $res = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch);
        curl_close($ch);

        if (($err) || ($httpcode !== 200)) {
            write_log($res,'receive') ;
            write_log("err=>{$err}===code=>{$httpcode}",'receive') ;
        }
        return $res;
    }
    private function sign($data,$app_secret)
    {
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            if($k == 'extra'){
                ksort($v);
                $str .= $k."=";
                foreach ($v as $key => $item) {
                    $str .= $key . '=' . $item . '&';
                }
            }else{
                $str .= $k . '=' . $v . '&';
            }
        }
        $str .= 'key=' . $app_secret;
        write_log("签名字符串:".$str,'cash_out');
        return hash('sha256', $str);
    }
    private function verify_sign($data)
    {
        unset($data['sign']);
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str .= 'key=' . $this->pay_app_secret;

        return hash('sha256', $str);
    }
}