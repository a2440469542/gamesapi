<?php
namespace app\common\logic;
use think\facade\Db;

class UserLogic{
    public function bind_user($UserModel,$inv_code,$mobile,$cid){
        $user = $UserModel->get_inv_info($inv_code);    //获取上级的信息
        if($user){
            $star_mobile = Db::name("star_mobile")
                ->where("mobile","=",$mobile)
                ->where("cid","=",$cid)
                ->where("uid",'=',$user['uid'])
                ->find();
            if($star_mobile){
                $count = Db::name("star_mobile")
                    ->where("cid","=",$star_mobile['cid'])
                    ->where("uid",'=',$star_mobile['uid'])
                    ->where("status","=",3)
                    ->count();
                $star_coin = Db::name("star_coin")
                    ->where("min","<=",$count+1)
                    ->where("max",">=",$count+1)
                    ->find();
                $update['status'] = 3;
                if($star_coin){
                    $update['money'] = $star_coin['money'];
                    $update['multiple'] = $star_coin['multiple'];
                }
                $update['addtime'] = time();
                Db::name("star_mobile")->where("id","=",$star_mobile['id'])->update($update);
            }
        }
        return $user;
    }
}