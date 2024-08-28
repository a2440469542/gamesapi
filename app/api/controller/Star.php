<?php

namespace app\api\controller;
use app\common\model\LuckyStar;
use app\common\model\StarPhrase;
use hg\apidoc\annotation as Apidoc;
use think\facade\Cache;
use think\facade\Db;

/**
 * 幸运星接口
 * @Apidoc\Title("幸运星接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(15)
 */
class Star extends Base
{
    /**
     * @Apidoc\Title("首页相关信息获取")
     * @Apidoc\Desc("首页活动相关信息获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("活动相关信息")
     * @Apidoc\Param("id", type="string",require=true, desc="用户ID")
     * @Apidoc\Returned("mobile",type="array",desc="幸运星列表",table="cp_star_mobile")
     * @Apidoc\Returned("num",type="int",desc="成功邀请")
     * @Apidoc\Returned("total_num",type="int",desc="已祝福")
     * @Apidoc\Returned("money",type="int",desc="累计获得")
     * @Apidoc\Returned("n_reg_money",type="int",desc="奖励在路上")
     */
    public function index(){
        $uid = $this->request->uid;
        $cid = $this->request->cid;
        $phrase = StarPhrase::select()->toArray();
        $time = strtotime(date("Y-m-d",time()));
        $mobile = Db::name("star_mobile")->where("uid","=",$uid)
            ->where("cid",'=',$cid)
            ->where("uid","=",$uid)
            ->where("share_time",">=",$time)
            ->select();
        if(empty($mobile)){
            $mobile = Db::name("star_mobile")
                ->where("uid",'=',0)
                ->where("status",'=',0)
                ->limit(5)
                ->select();
            foreach($mobile as $k=>$v) {
                $randomKey = array_rand($phrase);
                $content = $phrase[$randomKey];
                $update =[
                    'cid' => $cid,
                    'uid' => $uid,
                    'pid' => $content['id'],
                    'phrase' => $content['content'],
                    'share_time' =>time(),
                    'status'=>1
                ];
                Db::name("star_mobile")->where("id",'=',$v['id'])->update($update);
            }
        }
        $data['mobile'] = $mobile;
        return success("获取成功",$data);
    }
    /**
     * @Apidoc\Title("邀请记录")
     * @Apidoc\Desc("用户充值订单列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户充值订单列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("limit", type="int",require=true, desc="每页的条数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="发送相关记录",table="cp_star_mobile")
     */
    public function get_mobile(){
        $uid = $this->request->uid;
        $cid = $this->request->cid;
        $limit = input("limit",10);
        $list = Db::name("star_mobile")->where("uid","=",$uid)
            ->where("cid",'=',$cid)
            ->paginate($limit)->toArray();
        return success("获取成功",$list);
    }
    /**
     * @Apidoc\Title("复制 + 点击聊天后请求")
     * @Apidoc\Desc("复制 + 点击聊天后请求的接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("复制 + 点击聊天后入库信息")
     * @Apidoc\Param("pid", type="int",require=true, desc="短语ID")
     * @Apidoc\Param("mid", type="int",require=true, desc="手机id")
     * @Apidoc\Param("uid", type="int",require=true, desc="用户id")
     */
    public function getCode(){
        $sid = input("sid");
        $pid = input("pid");
        $mid = input("mid");
        $username = input("uid");
        if(empty($sid)){
            return error("缺少必要参数sid");
        }
        if(empty($pid)){
            return error("缺少必要参数pid");
        }
        if(empty($mid)){
            return error("缺少必要参数mid");
        }
        $luckyStar = LuckyStar::where("id","=",$sid)->where("is_del","=",0)->find();   //获得活动信息
        if(empty($luckyStar)){
            return error("活动信息不存在");
        }
        $where = [];
        if($luckyStar->time_type == 1){
            if($luckyStar->start_time >= time()){
                return error("活动尚未开始");
            }
            if($luckyStar->end_time <= time()){
                return error("活动已经结束");
            }
            $where[] = ['addtime',"between",[$luckyStar->start_time,$luckyStar->end_time]];
        }
        $phrase = StarPhrase::where("id","=",$pid)->find();     //获得推广短语信息
        if(empty($phrase)){
            return error("推广短语不存在");
        }
        $mobile = Db::name("star_mobile")
            ->where("sid","=",$sid)
            ->where("id","=",$mid)
            ->find();
        if(empty($mobile)){
            return error("此手机号不存在");
        }
        if($mobile["status"] != 0){
            return error("此手机号已经使用,请刷新页面重新获取");
        }
        $users = Users::info($username);
        if(empty($users)){
            return error("无此用户");
        }
        $uid = $users->uid;
        $condition = $luckyStar["condition"];
        if($condition != 1){
            //存款
            $topup_sum = UsersData::where("uid","=",$uid)->where($where)->sum("topup");
            //投注
            $turnover_sum = UsersData::where("uid","=",$uid)->where($where)->sum("turnover");
            switch ($condition){
                case 2:
                    if($topup_sum == 0){
                        return error("没有存款，不能祝福",504);
                    }
                    break;
                case 3:
                    if($turnover_sum == 0){
                        return error("没有投注，不能祝福",503);
                    }
                    break;
                case 4:
                    if($topup_sum == 0){
                        return error("没有存款，不能祝福",504);
                    }
                    if($turnover_sum == 0){
                        return error("没有投注，不能祝福",503);
                    }
                    break;
            }
        }
        //判断是否达到参与次数
        if($luckyStar->num == 1){
            $map = $where;
            $map[] = ["uid","=",$uid];
            $count = InviteLog::get_count($map);
            if($count >= 1){
                return error("每人限制参与一次;您已经参与了一次了",501);
            }
        }else{
            $start = strtotime(date("Y-m-d"));
            $map = [
                ["addtime",">=",$start],
                ["uid","=",$uid]
            ];
            $count = InviteLog::get_count($map);
            if($luckyStar->num == 2 && $count >= 1){
                return error("每人每天限制参与一次；您已经参与了一次了",501);
            }
            if($luckyStar->num == 3 && $count >= 3){
                return error("每人每天限制参与三次；您已经参与了三次了",501);
            }
        }
        $data = [
            'sid' => $sid,
            "uid" => $uid,
            'username' => $username,
            'pid' => $phrase->id,
            'phrase' => $phrase->content,
            'mid' => $mobile["id"],
            'mobile' => $mobile["mobile"],
            'money' => 4,
            'status' => 0,
            'addtime' => time()
        ];
        $row = InviteLog::insert($data);
        if($row){
            Db::name("star_mobile")
                ->where("id","=",$mid)
                ->update(["status"=>1]);
            return success("成功");
        }else{
            return error("失败");
        }
    }
}