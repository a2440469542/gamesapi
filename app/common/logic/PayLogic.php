<?php
namespace app\common\logic;

use think\facade\Db;

class PayLogic {
    public function pay($post) {
        $pay_class = app('app\service\pay\KirinPay');
        $sign = $pay_class->check_pay_sign($post);
        if ($sign !== true) return false;
        $data = $post['data'];
        $order_sn = explode('_', $data['merchantOrderNo']);
        $cid = $order_sn[0];
        $OrderModel = model('app\common\model\Order', $cid);
        $order = $OrderModel->getInfo($data['merchantOrderNo']);

        if (empty($order)) {
            $this->logError("订单不存在", 'pay');
            return false;
        }

        Db::startTrans();
        try {
            if ($data['status'] == 'SUCCESS') {
                return $this->handleSuccessfulPayment($OrderModel,$data, $order, $cid);
            } else {
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
    public function cash_out($data) {
        $pay_class = app('app\service\pay\KirinPay');
        $sign = $pay_class->check_pay_sign($data,"cash");
        if ($sign !== true) return false;
        $post = $data['data'];
        $order_sn = explode('_', $post['merchantOrderNo']);
        $cid = $order_sn[0];
        $OrderModel = model('app\common\model\Cash', $cid);
        $order = $OrderModel->getInfo($post['merchantOrderNo']);

        if (empty($order)) {
            $this->logError("订单不存在", 'cash');
            return false;
        }
        Db::startTrans();
        try {
            if ($data['status'] == "SUCCESS") {
                return $this->handleSuccessfulCash($OrderModel,$post, $order, $cid);
            } elseif ($data['status'] == "FAILURE" ||  $data['status'] == "FAIL") {
                return $this->handleFailedCash($OrderModel,$post, $order);
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
            $this->logError("用户不存在", 'cash');
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