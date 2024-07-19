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
        if(empty($info['pid'])) return error('Por favor, contacte o serviço de clientes primeiro para ligar o canal');
        $channel = app('app\common\model\Channel')->where('cid','IN',$info['pid'])->select();
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
}
