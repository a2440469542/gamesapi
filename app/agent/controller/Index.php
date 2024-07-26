<?php

namespace app\agent\controller;

use hg\apidoc\annotation as Apidoc;
use app\admin\model\Menu;
use app\BaseController;
use think\facade\Db;

/**
 * 数据相关
 * @Apidoc\Title("数据相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(1)
 */
class Index extends Base
{
    /**
     * @Apidoc\Title("渠道列表")
     * @Apidoc\Desc("渠道列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")
     * @Apidoc\Returned("",type="array",desc="渠道列表",table="cp_channel")
     */
    public function get_plate(){
        $id = $this->request->id;
        $info = app('app\agent\model\Agent')->where('id',$id)->find();
        $cids = Db::name('agent_channel')->where("aid","=",$info['id'])->column('cid');
        if(empty($cids)) return error('Por favor, contacte o serviço de clientes primeiro para ligar o canal');
        $channel = app('app\common\model\Channel')->where('cid','IN',$cids)->select();
        return success('获取成功',$channel);
    }
    /**
     * @Apidoc\Title("当前平台每日数据")
     * @Apidoc\Desc("当前平台每日数据")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("当前平台每日数据")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned("invite_user",type="int",desc="邀请人数")
     * @Apidoc\Returned("cz_money",type="float",desc="充值金额")
     * @Apidoc\Returned("bet_money",type="float",desc="下注金额")
     * @Apidoc\Returned("cash_money",type="float",desc="提现金额")
     * @Apidoc\Returned("cz_num",type="int",desc="充值人数")
     * @Apidoc\Returned("cash_num",type="float",desc="提现人数")
     * @Apidoc\Returned("bozhu_num",type="int",desc="N1领取人数")
     * @Apidoc\Returned("bozhu_money",type="float",desc="N1工资")
     * @Apidoc\Returned("daili_num",type="int",desc="N2领取人数")
     * @Apidoc\Returned("daili_money",type="float",desc="N2工资")
     * @Apidoc\Returned("n3_num",type="int",desc="N3领取人数")
     * @Apidoc\Returned("n3_money",type="float",desc="N3工资")
     */
    public function get_total(){
        $cid = input('cid');
        if(empty($cid)) return error('Por favor, contacte o serviço de clientes primeiro para ligar o canal');  //请选择渠道
        $UserStatModel = model('app\agent\model\UserStat',$cid);
        $where = [];
        $list = $UserStatModel->lists($where);
        return success('获取成功',$list);
    }
    /**
     * @Apidoc\Title("渠道统计相关接口")
     * @Apidoc\Desc("渠道统计相关接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道统计相关接口")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned("reg_num",type="int",desc="注册人数")
     * @Apidoc\Returned("cz_num",type="int",desc="充值人数")
     * @Apidoc\Returned("cz_money",type="float",desc="总充值金额")
     * @Apidoc\Returned("bet_money",type="float",desc="总投注金额")
     * @Apidoc\Returned("cash_money",type="float",desc="总提现金额")
     * @Apidoc\Returned("box_num",type="int",desc="宝箱领取人数")
     * @Apidoc\Returned("daili_wages_num",type="int",desc="代理工资领取人数")
     * @Apidoc\Returned("daili_wages_money",type="float",desc="代理工资领取总额")
     * @Apidoc\Returned("bozhu_wages_num",type="int",desc="博主工资领取人数")
     * @Apidoc\Returned("bozhu_wages_money",type="float",desc="博主工资领取总额")
     */
    public function stat(){
        $cid = input("cid", 0);
        if($cid === 0){
            return error("请选择渠道");
        }
        $UserModel = app('app\common\model\User');
        $UserStatModel = model('app\common\model\UserStat',$cid);
        $WagesModel = model('app\common\model\Wages',$cid);
        $reg_num = $UserModel->reg_num($cid);           //注册人数
        $user_money = $UserModel->user_money($cid);     //用户余额
        $cz_num = $UserStatModel->get_cz_num();             //充值人数
        $user_stat = $UserStatModel->get_total_money(); //统计信息
        $box_num = $UserStatModel->box_num();           //宝箱领取人数
        $wages_num = $WagesModel->wages_num();          //工资领取人数
        $wages_money = $WagesModel->wages_money();      //工资领取金额
        $data = [
            'reg_num' => $reg_num,
            'cz_num' => $cz_num,
            'cz_money' => round($user_stat['cz_money']   ?? '0.00',2),   //总充值金额
            'bet_money' => round($user_stat['bet_money'] ?? '0.00',2),   //总投注金额
            'cash_money' => round($user_stat['cash_money'] ?? '0.00',2),   //总提现金额
            'box_num' => $box_num,
            'daili_wages_num' =>    $wages_num['daili'],    //代理工资领取人数
            'daili_wages_money' => round($wages_money['daili'] ?? '0.00',2),   //代理工资领取总额
            'bozhu_wages_num' =>    $wages_num['bozhu'],    //博主工资领取人数
            'bozhu_wages_money' => round($wages_money['bozhu'] ?? '0.00',2),   //博主工资领取总额
            'user_money' => round($user_money,2),   //用户余额
            'box_money'  => round($user_stat['box_money'] ?? '0.00',2),   //宝箱领取总额
        ];
        return success("获取成功", $data);
    }
}
