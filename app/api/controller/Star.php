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
     * @Apidoc\Returned("notice",type="array",desc="滚动条列表",table="cp_star_mobile")
     * @Apidoc\Returned("get_money",type="float",desc="累计获得金额")
     * @Apidoc\Returned("inv_num",type="int",desc="邀请人数")
     * @Apidoc\Returned("un_get_money",type="float",desc="可领取奖励")
     */
    public function index(){
        $uid = $this->request->uid;
        $cid = $this->request->cid;
        $user = $this->request->user;
        $phrase = StarPhrase::select()->toArray();
        $time = strtotime(date("Y-m-d",time()));
        $mobile = Db::name("star_mobile")->where("uid","=",$uid)
            ->where("cid",'=',$cid)
            ->where("uid","=",$uid)
            ->where("share_time",">=",$time)
            ->select()->toArray();
        if(empty($mobile)){
            $mobile = Db::name("star_mobile")
                ->where("cid",'=',0)
                ->where("uid",'=',0)
                ->where("status",'=',0)
                ->limit(5)
                ->select()->toArray();
            if(empty($mobile)){
                $mobile = Db::name("star_mobile")
                    ->where("status",'<',2)
                    ->limit(5)
                    ->select()->toArray();
            }
            foreach($mobile as $k=>$v) {
                $randomKey = array_rand($phrase);
                $content = $phrase[$randomKey];
                $update =[
                    'cid' => $cid,
                    'uid' => $uid,
                    'pid' => $content['id'],
                    'username' => $user['mobile'],
                    'phrase' => $content['content'],
                    'share_time' =>time(),
                    'status'=>1
                ];
                Db::name("star_mobile")->where("id",'=',$v['id'])->update($update);
            }
        }
        $total_money = Db::name("star_mobile")
            ->field("sum(money) as money,count(id) as num,status")
            ->where("cid","=",$cid)->where("uid","=",$uid)->where("status",">=",3)->group("status")->select();
        $data['get_money'] = 0;
        $data['inv_num'] = 0;
        $data['un_get_money'] = 0;
        foreach($total_money as $k=>$v){
            if($v['status'] == 3){
                $data['un_get_money'] = $v['money'];
            }
            if($v['status'] == 4){
                $data['get_money'] = $v['money'];
            }
            $data['inv_num'] += $v['num'];
        }
        $data['mobile'] = $mobile;


        //
        $notice = Db::name("star_mobile")
            ->field("count(*) as num,sum(money) as money,uid,username")
            ->where("status",">=",3)
            ->group("uid")
            ->limit(10)
            ->select()->toArray();
        foreach($notice as $k=>&$v){
            $v['username'] = replaceStr($v['uid']);
        }
        $data['notice'] = $notice;
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
     * @Apidoc\Title("点击聊天后请求")
     * @Apidoc\Desc("点击聊天后请求的接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("点击聊天后入库信息")
     * @Apidoc\Param("mid", type="int",require=true, desc="手机id")
     */
    public function getCode(){
        $uid = $this->request->uid;
        $cid = $this->request->cid;
        $mid = input("mid");
        $star_mobile = Db::name("star_mobile")->where("id","=",$mid)->find();
        if(empty($star_mobile)){
            return error("O telefone não existe");  //手机号不存在
        }
        if($star_mobile['status'] >= 2){
            return success("O código foi enviado com sucesso");  //发送成功
        }
        Db::name("star_mobile")->where("id","=",$mid)->update(['status'=>2,'sendtime'=>time()]);
        return success("O código foi enviado com sucesso");  //发送成功
    }
    /**
     * @Apidoc\Title("领取幸运星奖励")
     * @Apidoc\Desc("领取幸运星奖励")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("领取幸运星奖励")
     */
    public function get_money(){
        $uid = $this->request->uid;
        $cid = $this->request->cid;

        $redis = Cache::store('redis')->handler();
        $lockKey = "user_star_lock_{$uid}";
        if ($redis->exists($lockKey)) {
            return error('O pedido está sendo atualmente processado, por favor tente de novo mais tarde');
        }
        $redis->set($lockKey, true, 60);
        Db::startTrans();
        try {
            $star_mobile = Db::name("star_mobile")
                ->where("uid","=",$uid)
                ->where("cid","=",$cid)
                ->where("status","=",3)
                ->select();
            $money = 0;
            $multiple = 0;
            foreach($star_mobile as $k=>$v){
                $money += $v['money'];
                $multiple = $v['multiple'];
            }
            $user = model('app\common\model\User', $cid)->getInfo($uid);
            $BillModel = model('app\common\model\Bill', $cid);
            $result = $BillModel->addIntvie($user, $BillModel::STAR_MONEY, $money,0,$multiple);
            if (!$result) {
                Db::rollback();
                return error("A coleção falhou", 500);  //不在榜单
            }
            Db::name("star_mobile")->where("uid","=",$uid)
                ->where("cid","=",$cid)
                ->where("status","=",3)
                ->update(['status'=>4]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            write_log($e->getMessage() . '代码行数:' . $e->getLine() . '文件:' . $e->getFile(), 'rank');
            return error("A coleção falhou");   //领取失败
        } finally {
            $redis->del($lockKey);
        }
        return success("Obtido com sucesso",$money); //获取成功
    }
}