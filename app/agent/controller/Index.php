<?php

namespace app\agent\controller;

use app\admin\model\Menu;
use app\BaseController;
use think\facade\Db;

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
        $channel = app('app\common\model\Channel')->where('pid','IN',$info['pid'])->select();
        return success('获取成功',$channel);
    }
    /**
     * @Apidoc\Title("绑定列表")
     * @Apidoc\Desc("绑定列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("绑定列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("mobile", type="string",require=false, desc="pix手机号：搜索时候传")
     * @Apidoc\Param("inv_code", type="string",require=false, desc="用户邀请码：搜索时候传")
     * @Apidoc\Param("phone", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Param("pix", type="string",require=false, desc="银行账号：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="充值记录相关",table="cp_bank",children={
     *          @Apidoc\Returned("phone",type="string",desc="用户账号"),
     *          @Apidoc\Returned("inv_code",type="string",desc="用户邀请码")
     *     })
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
