<?php
namespace app\common\logic;

use think\facade\Db;

class CapivaraPayLogic {
    public function pay($post) {
        $pay_class = app('app\service\pay\CapivaraPay');
        $post['NoticeParams'] = json_decode($post['NoticeParams'], true);
        $sign = $pay_class->check_pay_sign($post);
        if ($sign !== true) return false;
        $data = $post['NoticeParams'];
        $order_sn = explode('_', $data['outTradeNo']);
        $cid = $order_sn[0];
        $OrderModel = model('app\common\model\Order', $cid);
        $order = $OrderModel->getInfo($data['outTradeNo']);

        if (empty($order)) {
            $this->logError("订单不存在", 'pay');
            return false;
        }

        Db::startTrans();
        try {
            if ($data['payCode'] == '0000') {
                return $this->handleSuccessfulPayment($OrderModel,$data, $order, $cid);
            } else {
                $this->handleFailedPayment($OrderModel,$data, $order);
                $this->logError($data['status'], 'pay');
                return false;
            }
        } catch (\Exception $e) {
            Db::rollback();
            $this->logError($e->getMessage(), 'pay');
            return false;
        }
    }

    private function handleSuccessfulPayment($OrderModel,$post, $order, $cid) {
        $update = [
            'id' => $order['id'],
            'status' => 2,
            'orderno' => $post['outTradeNo']
        ];

        $UserModel = model('app\common\model\User', $cid);
        $user = $UserModel->getInfo($order['uid']);
        if (empty($user)) {
            $this->logError("用户不存在", 'pay');
            return false;
        }

        $channel = model('app\common\model\Channel')->info($user['cid']);

        $money = $order['money'];

        if(isset($channel['deposit_fee'])){
            $fee = round($money *  $channel['deposit_fee'],2);
            $money = $money - $fee;
            $update['real_money'] = $money;
        }

        $BillModel = model('app\common\model\Bill', $cid);
        $BillModel->addIntvie($user, $BillModel::PAY_MONEY, $money , $order['gifts'], $order['multiple']);

        $UserStatModel = model('app\common\model\UserStat', $cid);
        $user_stat = ['cz_money' => $money, 'cz_num' => 1];
        if($order['gifts'] > 0){
            $user_stat['gifts_money'] = $order['gifts'];
        }
        $UserStatModel->add($user,$user_stat);
        app('app\common\model\Mail')->add($cid,$order['uid'],'Recargar bem sucedido',$order['money']);
        if ($OrderModel->update_order($update)) {
            Db::commit();
            return true;
        } else {
            Db::rollback();
            return false;
        }
    }

    private function handleFailedPayment($OrderModel,$post, $order) {
        $update = [
            'id' => $order['id'],
            'status' => 3,
            'orderno' => $post['outTradeNo']
        ];
        app('app\common\model\Mail')->add($order['cid'],$order['uid'],$post['message'],$order['money']);
        if ($OrderModel->update_order($update)) {
            Db::commit();
            return true;
        } else {
            Db::rollback();
            return false;
        }
    }

    private function logError($message, $type) {
        write_log("====错误信息=====\n" . $message . "\n", $type);
    }
    public function cash_out($data) {
        $post['NoticeParams'] = json_decode($data['NoticeParams'], true);
        write_log($post, "cash");
        $pay_class = app('app\service\pay\CapivaraPay');
        $sign = $pay_class->check_pay_sign($post,"cash");
        if ($sign !== true) return false;
        $order_sn = explode('_', $post['outTradeNo']);
        $cid = $order_sn[0];
        $OrderModel = model('app\common\model\Cash', $cid);
        $order = $OrderModel->getInfo($post['outTradeNo']);

        if (empty($order)) {
            $this->logError("订单不存在", 'cash');
            return false;
        }
        Db::startTrans();
        try {
            if ($post['remitResult'] == "00") {
                return $this->handleSuccessfulCash($OrderModel,$post, $order, $cid);
            } elseif($post['remitResult'] == "99" || $post['remitResult'] == "01" || $post['remitResult'] == "06"){
                return false;
            }elseif ($post['remitResult'] == "50" ||
                $post['remitResult'] == "02" ||
                $post['remitResult'] == "04" ||
                $post['remitResult'] == "05" ||
                $post['remitResult'] == "12" ||
                $post['remitResult'] == "13" ||
                $post['remitResult'] == "1000"
            ) {
                return $this->handleFailedCash($OrderModel,$post, $order, $cid);
            } else {
                return true;
            }
        } catch (\Exception $e) {
            Db::rollback();
            $this->logError($e->getMessage(), 'cash');
            return false;
        }
    }
    private function handleSuccessfulCash($CashModel,$post, $order, $cid) {
        $update = [
            'id' => $order['id'],
            'status' => 2,
            'orderno' => $post['outTradeNo']
        ];

        $UserModel = model('app\common\model\User', $cid);
        $user = $UserModel->getInfo($order['uid']);
        if (empty($user)) {
            $this->logError("用户不存在", 'cash');
            return false;
        }

        /*$BillModel = model('app\common\model\Bill', $cid);
        $BillModel->addIntvie($user, $BillModel::CASH_MONEY, -$order['money']);*/

        $UserStatModel = model('app\common\model\UserStat', $cid);
        $user_stat = ['cash_money' => $order['money'], 'cash_num' => 1,];
        $UserStatModel->add($user,$user_stat);
        app('app\common\model\Mail')->add($cid,$order['uid'],'Retirar bem sucedido',$order['money']);

        if ($CashModel->update_order($update)) {
            Db::commit();
            return true;
        } else {
            Db::rollback();
            return false;
        }
    }

    private function handleFailedCash($CashModel,$post, $order, $cid) {
        $update = [
            'id' => $order['id'],
            'status' => -2,
            'orderno' => $post['outTradeNo']
        ];
        $UserModel = model('app\common\model\User', $cid);
        $user = $UserModel->getInfo($order['uid']);
        if (empty($user)) {
            $this->logError("用户不存在", 'cash');
            return false;
        }
        //提现失败返回
        $BillModel = model('app\common\model\Bill', $cid);
        $BillModel->addIntvie($user, $BillModel::CASH_RETURN, $order['money']);
        app('app\common\model\Mail')->add($cid,$order['uid'],$post['message'],$order['money']);
        if ($CashModel->update_order($update)) {
            Db::commit();
            return true;
        } else {
            Db::rollback();
            return false;
        }
    }
}