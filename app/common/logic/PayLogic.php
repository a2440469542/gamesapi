<?php
namespace app\common\logic;

use think\facade\Db;

class PayLogic {
    public function pay($post) {
        $pay_class = app('app\service\pay\BetcatPay');
        $sign = $pay_class->check_pay_sign($post);
        if ($sign !== true) return false;

        $order_sn = explode('_', $post['merOrderNo']);
        $cid = $order_sn[0];
        $OrderModel = model('app\common\model\Order', $cid);
        $order = $OrderModel->getInfo($post['merOrderNo']);

        if (empty($order)) {
            $this->logError("订单不存在", 'pay');
            return false;
        }

        if ($order['status'] != 1) {
            $this->logError("订单状态错误", 'pay');
            return false;
        }

        Db::startTrans();
        try {
            if ($post['orderStatus'] == 2) {
                return $this->handleSuccessfulPayment($OrderModel,$post, $order, $cid);
            } elseif ($post['orderStatus'] < 0) {
                return $this->handleFailedPayment($OrderModel,$post, $order);
            } else {
                return true;
            }
        } catch (\Exception $e) {
            Db::rollback();
            $this->logError($e->getMessage(), 'cash');
            return false;
        }
    }

    private function handleSuccessfulPayment($OrderModel,$post, $order, $cid) {
        $update = [
            'id' => $order['id'],
            'status' => 2,
            'orderno' => $post['orderNo']
        ];

        $UserModel = model('app\common\model\User', $cid);
        $user = $UserModel->getInfo($order['uid']);
        if (empty($user)) {
            $this->logError("用户不存在", 'pay');
            return false;
        }

        $BillModel = model('app\common\model\Bill', $cid);
        $BillModel->addIntvie($user, $BillModel::PAY_MONEY, $order['money'] + $order['gifts']);

        $UserStatModel = model('app\common\model\UserStat', $cid);
        $user_stat = [
            'uid' => $user['uid'],
            'cid' => $user['cid'],
            'mobile' => $user['mobile'],
            'cz_money' => $order['money'] + $order['gifts'],
            'cz_num' => 1,
        ];
        $UserStatModel->add($user_stat);

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
            'orderno' => $post['orderNo']
        ];

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
    public function cash_out($post) {
        $pay_class = app('app\service\pay\BetcatPay');
        $sign = $pay_class->check_cash_sign($post);
        if ($sign !== true) return false;
        $order_sn = explode('_', $post['merOrderNo']);
        $cid = $order_sn[0];
        $OrderModel = model('app\common\model\Cash', $cid);
        $order = $OrderModel->getInfo($post['merOrderNo']);

        if (empty($order)) {
            $this->logError("订单不存在", 'cash');
            return false;
        }

        if ($order['status'] != 1) {
            $this->logError("订单状态错误", 'cash');
            return false;
        }

        Db::startTrans();
        try {
            if ($post['orderStatus'] == 2) {
                return $this->handleSuccessfulPayment($OrderModel,$post, $order, $cid);
            } elseif ($post['orderStatus'] < 0) {
                return $this->handleFailedPayment($OrderModel,$post, $order);
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
            'orderno' => $post['orderNo']
        ];

        $UserModel = model('app\common\model\User', $cid);
        $user = $UserModel->getInfo($order['uid']);
        if (empty($user)) {
            $this->logError("用户不存在", 'pay');
            return false;
        }

        $BillModel = model('app\common\model\Bill', $cid);
        $BillModel->addIntvie($user, $BillModel::CASH_MONEY, -$order['money']);

        $UserStatModel = model('app\common\model\UserStat', $cid);
        $user_stat = [
            'uid' => $user['uid'],
            'cid' => $user['cid'],
            'mobile' => $user['mobile'],
            'cash_money' => $order['money'],
            'cash_num' => 1,
        ];
        $UserStatModel->add($user_stat);

        if ($CashModel->update_order($update)) {
            Db::commit();
            return true;
        } else {
            Db::rollback();
            return false;
        }
    }

    private function handleFailedCash($CashModel,$post, $order) {
        $update = [
            'id' => $order['id'],
            'status' => -2,
            'orderno' => $post['orderNo']
        ];

        if ($CashModel->update_order($update)) {
            Db::commit();
            return true;
        } else {
            Db::rollback();
            return false;
        }
    }
}