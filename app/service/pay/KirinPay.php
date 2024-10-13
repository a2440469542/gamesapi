<?php
namespace app\service\pay;
use think\facade\Db;
use think\facade\Log;
class KirinPay{
    private $merchantKey = 'FB2+z0UNvR6PG3L5';
    private $aesKey = 'yrdQbqnmzdPgwqw0';
    private $aesIv = '+vVB75LzPAsVA8SY';
    private $api_url = 'https://pay.kirinpays.com';
    /**
     * @param $merOrderNo   string 订单号
     * @param $currency     string 金额币种
     * @param $amount       string 订单金额
     * @return bool|string
     */
    public function pay(string $merOrderNo , string $amount,string $customerCpf, string $currency='BRL',){
        $data = [
            'merchantOrderNo' => $merOrderNo,
            'amount' => number_format(trim($amount), 2, '.', ''),
            'notifyUrl' => SITE_URL.'/api/notify/pay',
            'customerCpf' => $customerCpf
        ];
        $url = $this->api_url.'/gateway/payment/init';
        $str = json_encode($data);
        $merchant_key = $this->merchantKey;//'merchant_key';
        $headers = array();
        $headers[] = 'Content-Type: '. 'application/json;charset=UTF-8';
        $headers[] = 'merchant_key: '.$merchant_key;
        $aes_key = $this->aesKey;//'aes_key';
        $aes_iv = $this->aesIv;//'aes_iv';
        $data= $this->encryptNew($str, $aes_key, $aes_iv);
        $info['data']=$data;
        write_log($info,'pay');
        $data = json_encode($info);
        $ret = $this->httpsPost($url,$data,$headers);
        //var_dump($ret);
        return json_decode($ret,true);
        /*$code = $retinfo['code'];

        if($code=='0'){
            $payinfo = $retinfo['data']['paymentLinkUrl'];
            echo $payinfo;
        }
        else{
            $ret1['ret']='-1';
            $ret1['error']='error';
            echo json_encode($ret);
        }*/
    }
    public function cash_out(string $merOrderNo , string $amount,string $type,string $accountNo,string $document,$user=[]){
        $data = [
            'merchantOrderNo' => $merOrderNo,
            'amount' => number_format(trim($amount), 2, '.', ''),
            'notifyUrl' => SITE_URL.'/api/notify/cash_out',
            'transferType' => $type,
            'beneficiaryAccount' => $accountNo,
            'purpose' => $document
        ];
        write_log($data,'cash_out');
        $url = $this->api_url.'/gateway/payout/init';
        $str = json_encode($data);
        $merchant_key = $this->merchantKey;//'merchant_key';
        $headers = array();
        $headers[] = 'Content-Type: '. 'application/json;charset=UTF-8';
        $headers[] = 'merchant_key: '.$merchant_key;
        $aes_key = $this->aesKey;//'aes_key';
        $aes_iv = $this->aesIv;//'aes_iv';
        $data= $this->encryptNew($str, $aes_key, $aes_iv);
        $info['data']=$data;
        write_log($info,'cash_out');
        $data = json_encode($info);
        $ret = $this->httpsPost($url,$data,$headers);
        write_log($ret,'cash_out');
        //var_dump($ret);
        return json_decode($ret,true);
    }
    public function check_pay_sign($data,$file='pay'){
        try{
            $returnArray = $data['data'];
            $sign = $data['signature'];
            //echo $sign;
            $merchant_key = $this->merchantKey;//'merchant_key';
            $aes_key = $this->aesKey;//'aes_key';
            $lsign = md5($merchant_key.json_encode($returnArray).$aes_key);
            write_log("====签名=====\n".$lsign."\n",$file);
            if($lsign != $sign){
                write_log("====签名错误=====\n",$file);
                return false;
            }
        }catch(Exception $e){
            write_log($e->getMessage(),$file);
            return false;
        }
        return true;
    }

    private function httpsPost($url, $paramStr,$headers){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $paramStr,
            CURLOPT_HTTPHEADER => $headers,
        ));
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);
        if (($err) || ($httpcode !== 200)) {
            write_log($response,'receive') ;
            write_log("err=>{$err}===code=>{$httpcode}",'receive');
        }
        return $response;
    }
    /**
     * AES/CBC/PKCS5Padding Encrypter
     * @param $str
     * @param $key
     * @return string
     **/
    private function encryptNew($str, $key, $iv)
    {
        return base64_encode(openssl_encrypt($str, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv));
    }
}