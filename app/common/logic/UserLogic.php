<?php
namespace app\common\logic;
use think\facade\Cache;
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
    public function register($inv_code,$data,$cid){
        $UserModel = model('app\common\model\User',$cid);
        $user = null;
        if($inv_code){
            if(isset($data['mobile'])){
                $user = $this->bind_user($UserModel,$inv_code,$data['mobile'],$cid);
            }else{
                $user = $UserModel->get_inv_info($inv_code);    //获取上级的信息
            }
            if($user){
                $data['pid']   = $user['uid'];
                $data['ppid']  = $user['pid'];
                $data['pppid'] = $user['ppid'];
            }
        }
        $row = $UserModel->add($data);
        if($row['code'] > 0){
            return $row;
        }
        $uid = $row['uid'];
        //数据统计
        if($user != null){
            $UserStatModel = model('app\common\model\UserStat',$this->cid);
            $stat = ['invite_user' => 1];
            $UserStatModel->add($user,$stat);
        }
        do {
            $token = "api_".bin2hex(random_bytes(16));
        } while (Cache::has($token));
        return ['code'=>0,'token'=>$token,'uid'=>$uid];
    }
}