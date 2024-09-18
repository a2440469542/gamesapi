<?php

namespace app\service\sms;

use think\facade\Cache;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


class Sms
{
    private $app_id = 'AjmmMDHU';
    private $app_secret = 'UOHEMDSSLCCWSQCBSXFTOJQFSIFJMVHQ';

    private $app_email_id = 'k7_mail';
    private $app_email_secret = 'jvsfvv1hw13vvz4xwe';

    public function send_sms($mobile,$code){
        $content = 'Your verification code is '.$code;
        Cache::set('code_'.$mobile,$code,300);
        $sign = strtoupper(MD5($this->app_id.$content.$code.$this->app_secret));
        $url = 'http://smsapi.abosend.com:8205/v2/api/sendSMS';
        $data = [
            'orgCode' => $this->app_id,
            'mobiles' => '+'.$mobile,
            'content' => $content,
            'rand' => $code,
            'sign' => $sign,
        ];
        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ];
        $row = httpRequest($url,'POST',json_encode($data),$headers);
        $row = json_decode($row,true);
        return $row;
    }
    public function send_email($email,$code){
        $url = 'https://sms.nbfmg.com/api/nation/method';
        $data = [
            'cmd' =>  'GetBalance',
            'user' => 'k7_mail',
            'signature' => 'jvsfvv1hw13vvz4xwe',
        ];
        $row = httpRequest($url,'GET',$data);
        $row = json_decode($row,true);
        if($row[0]['error_Code'] != 200){
            return ['code'=>$row[0]['error_Code'],'msg'=>$row[0]['error_Message']];
        }
        if($row[0]['result'] > 0){
            $url = 'https://sms.nbfmg.com/api/nation/method';
            $data = [
                'cmd' =>  'SendMail',
                'user' => 'k7_mail',
                'signature' => 'jvsfvv1hw13vvz4xwe',
                'To' => $email,
                'Subject' => 'Verification Code',
                'Body' => 'Your verification code is '.$code,
            ];
            $row = httpRequest($url,'GET',$data);
            $row = json_decode($row,true);
            if($row[0]['error_Code'] != 200){
                return ['code'=>$row[0]['error_Code'],'msg'=>$row[0]['error_Message']];
            }
        }else{
            return ['code'=>400,'msg'=>'Erro de configuração'];
        }
        return ['code'=>0,'msg'=>'Erro de configuração'];
    }
}