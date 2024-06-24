<?php

namespace app\api\controller;
use app\BaseController;
use think\facade\Db;
use app\common\model\Cash;
use think\facade\Request;
class Notify extends BaseController
{
    public function cash_out(){
        $row = input('post.');
        write_log("====付款通知接收参数=====\n",'cash');
        write_log($row,'cash');
        if(empty($row)){
            write_log("接收参数下面进入",'cash');
            echo "error";exit;
        }
        $PayLogic = new \app\common\logic\PayLogic();
        $res = $PayLogic->cash_out($row);
        write_log("====付款通知处理结果=====\n",'cash');
        write_log($res,'cash');
        if($res){
            echo "success";exit;
        }else{
            echo "error";exit;
        }
    }
    public function pay(){
        $row = input('post.');
        write_log("====支付通知接收参数=====\n",'pay');
        write_log($row,'pay');
        if(empty($row)){
            write_log("接收参数下面进入",'pay');
            echo "error";exit;
        }
        $PayLogic = new \app\common\logic\PayLogic();
        $res = $PayLogic->pay($row);
        write_log("====支付通知处理结果=====\n",'pay');
        write_log($res,'pay');
        if($res){
            echo "success";exit;
        }else{
            echo "error";exit;
        }
    }
}