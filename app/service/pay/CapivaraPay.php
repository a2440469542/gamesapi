<?php
namespace app\service\pay;
use think\facade\Db;
use think\facade\Log;
use app\common\controller\Des3;
class CapivaraPay{
    private $merchantKey = 'OW241007132014939';
    private $aesKey = 'A561517C1B601A95C148FE61DD54DA13';
    private $aesIv = '367532727286235205556015';
    private $api_url = 'https://api.helloowen.com';
    /**
     * @param $merOrderNo   string 订单号
     * @param $currency     string 金额币种
     * @param $amount       string 订单金额
     * @return array
     */
    public function pay(string $merOrderNo , string $amount,string $customerCpf, string $currency='BRL',){
        $data = [
            'appID' => $this->merchantKey,
            'currencyCode' => $currency,
            'tradeCode' => 'BRL002',
            'randomNo' => (string) rand(1000,9999),
            'outTradeNo' => $merOrderNo,
            'totalAmount' => (string) ($amount * 100),
            'productTitle' => 'k7pay',
            'notifyUrl' => SITE_URL.'/api/notify/pay',
            'tradeIP' => get_real_ip__(),
            'payName' => 'xiaotiantian',
            'payEmail' => 'xiaotiantian@gmail.com',
            'payPhone' => $customerCpf,
            'payBankCard' => $customerCpf,
            'payBankCode' => 'PIX'
        ];
        $data['sign'] = $this->generateSignature($data);
        $url = $this->api_url.'/pay/apply.shtml';
        $applyParamsJson = self::json_encode($data);
        write_log($data,'pay');
        write_log($applyParamsJson,'pay');
        $ret = $this->httpsPost($url,['ApplyParams' => $applyParamsJson],[]);
        $ret = json_decode($ret,true);
        write_log('=====支付回调数据======','pay');
        write_log($ret,'pay');
        if($ret && $ret['resultCode'] == '0000'){
            $row['code'] = 0;
            $row['data']['paymentLinkUrl'] = $ret['payURL'];
        }else{
            $row['code'] = 500;
            $row['msg'] = $ret['stateInfo'] ?? '404 error';
        }
        return $row;
    }
    public function cash_out(string $merOrderNo , string $amount,string $type,string $accountNo,string $document,$user=[]){
        $Des3 = new Des3();
        write_log($accountNo,'cash_out');
        $data = [
            'appID' => $this->merchantKey,
            'currencyCode' => 'BRL',
            'randomNo' => (string) rand(1000,9999),
            'outTradeNo' => $merOrderNo,
            'bankCode' => 'PIX',
            'bankAcctName' => $Des3->encryptText('xiaotiantian',$this->aesIv),
            'bankFirstName' => $Des3->encryptText('xiao',$this->aesIv),
            'bankLastName' => $Des3->encryptText('tiantian',$this->aesIv),
            'bankAcctNo' => $Des3->encryptText($accountNo,$this->aesIv),
            'totalAmount' => $Des3->encryptText($amount * 100,$this->aesIv),
            'accPhone' => $Des3->encryptText('+'.$accountNo,$this->aesIv),
            'notifyUrl' => SITE_URL.'/api/notify/cash_out',
            'identityNo' => $document,
            'identityType' => $type
        ];
        $data['sign'] = $this->generateSignature($data);
        write_log($data,'cash_out');
        $url = $this->api_url.'/cashOutVp/apply.shtml';
        write_log($url,'cash_out');
        $applyParamsJson = self::json_encode($data);
        write_log($data,'cash_out');
        write_log($applyParamsJson,'cash_out');
        $ret = $this->httpsPost($url,['CashOutParams' => $applyParamsJson],[]);
        $ret = json_decode($ret,true);
        write_log('=====提现回调数据======','cash_out');
        write_log($ret,'cash_out');
        if($ret && $ret['resultCode'] == '0000'){
            $row['code'] = 0;
        }else{
            $row['code'] = 500;
            $row['msg'] = $ret['resultMsg'] ?? '404 error';
        }
        return $row;
    }
    public function check_pay_sign($data,$file='pay'){
        $returnArray = $data['NoticeParams'];
        write_log($returnArray,$file);
        $sign = $returnArray['sign'];
        write_log("====返回签名=====\n".$sign."\n",$file);
        unset($returnArray['sign']);
        //echo $sign;
        $lsign = $this->generateSignature($returnArray);
        write_log("====签名=====\n".$lsign."\n",$file);
        if($lsign != $sign){
            write_log("====签名错误=====\n",$file);
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

    static function json_encode($inPut){
        if(is_string($inPut)){
            $text = $inPut;
            $text = str_rePlace('\\', '\\\\', $text);
            $text = str_rePlace(
                array("\r", "\n", "\t", "\""),
                array('\r', '\n', '\t', '\\"'),
                $text);
            $text = str_rePlace("\\/", "/", $text);
            return '"' . $text . '"';
        }else if(is_array($inPut) || is_object($inPut)){
            $arr = array();
            $is_obj = is_object($inPut) || (array_keys($inPut) !== range(0, count($inPut) - 1));
            foreach($inPut as $k=>$v){
                if($is_obj){
                    $arr[] = self::json_encode($k) . ':' . self::json_encode($v);
                }else{
                    $arr[] = self::json_encode($v);
                }
            }
            if($is_obj){
                $arr = str_rePlace("\\/", "/", $arr);
                return '{' . join(',', $arr) . '}';
            }else{
                $arr = str_rePlace("\\/", "/", $arr);
                return '[' . join(',', $arr) . ']';
            }
        }else{
            $inPut = str_rePlace("\\/", "/", $inPut);
            return $inPut . '';
        }
    }

    static function json_decode($json){
        $comment = false;
        $out = '$x=';
        for ($i=0; $i<strlen($json); $i++){
            if (!$comment){
                if (($json[$i] == '{') || ($json[$i] == '[')) $out .= ' array(';
                else if (($json[$i] == '}') || ($json[$i] == ']')) $out .= ')';
                else if ($json[$i] == ':') $out .= '=>';
                else $out .= $json[$i];
            }
            else $out .= $json[$i];
            if ($json[$i] == '"' && $json[($i-1)]!="\\") $comment = !$comment;
        }
        eval($out . ';');
        return $x;
    }

    /**
     * AES/CBC/PKCS5Padding Encrypter
     * @param $str
     * @param $key
     * @return string
     **/
    // 获取带签名的请求参数
    private function generateSignature($filteredParams)
    {
        // 按照参数名的 ASCII 码升序排序
        ksort($filteredParams);

        // 将参数转换为 JSON 字符串
        //$jsonString = json_encode($filteredParams, JSON_UNESCAPED_UNICODE);
        $jsonString = self::json_encode($filteredParams);

        // 拼接商户密钥
        $stringToSign = $jsonString . $this->aesKey;

        // 使用 MD5 进行加密，并转换为大写
        return strtoupper(md5($stringToSign));
    }
}