<?php
namespace app\admin\controller;
use app\common\model\Channel as ChannelModel;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;

/**
 * 代理相关接口
 * @Apidoc\Title("代理相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class Agent extends Base{
    /**
     * @Apidoc\Title("代理列表")
     * @Apidoc\Desc("代理列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("代理列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("mobile", type="string",require=false, desc="用户手机号：搜索时候传")
     * @Apidoc\Returned("data",type="array",desc="渠道宝箱列表",table="cp_agent")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id asc');
        $mobile = input("mobile",'');
        if($mobile != ''){
            $where[] = ['mobile','=',$mobile];
        }
        $list = app('app\admin\model\Agent')->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("渠道列表")
     * @Apidoc\Desc("渠道列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("name", type="string",require=false, desc="渠道名称：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="渠道列表",table="cp_channel")
     */
    public function channel(){
        $limit = input("limit",10);
        $name = input("name",'');
        if($name != ''){$where[] = ['name','like',"%{$name}%"];}
        $cids = Db::name('agent_channel')->column('cid');
        $where[] = ['cid','NOT IN',$cids];
        $list = ChannelModel::lists($where,$limit);
        return success("获取成功",$list);
    }
    /**
     * @Apidoc\Title("代理绑定的渠道列表")
     * @Apidoc\Desc("代理绑定的渠道列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("代理绑定的渠道列表")
     * @Apidoc\Param("aid", type="int",require=true, desc="代理ID")
     * @Apidoc\Returned("",type="array",desc="代理绑定的渠道列表",table="cp_agent_channel",children={
     *     @Apidoc\Returned("cz_money",type="float",desc="总充值金额"),
     *     @Apidoc\Returned("cash_money",type="float",desc="总提现金额")
     * })
     */
    public function channle_list(){
        $aid = input("aid");
        $list = Db::name('agent_channel')->where("aid","=",$aid)->select();
        $UserStatModel = app('app\common\model\UserStat');
        foreach ($list as &$value) {
            $UserStatModel->setPartition($value['cid']);
            $user_stat = $UserStatModel->get_total_money(); //统计信息
            $value['cz_money'] = round($user_stat['cz_money']   ?? '0.00',2);       //总充值金额
            $value['cash_money'] = round($user_stat['cash_money'] ?? '0.00',2);     //总提现金额
        }
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("渠道分配")
     * @Apidoc\Desc("渠道分配")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道分配")
     * @Apidoc\Param("aid", type="int",require=true, desc="代理ID")
     * @Apidoc\Param("cid", type="int",require=true, desc="平台ID格式")
     */
    public function set_channel(){
        $cid = input("cid");
        $aid = input("aid");
        if(empty($aid)) return error("请选择数据");
        if(empty($cid)) return error("请选择渠道");
        $count = Db::name('agent_channel')->where("cid","=",$cid)->count();
        if($count > 0) return error("该渠道已被绑定");
        $channel = model('app\common\model\Channel')->info($cid);
        $data = [
            'aid' => $aid,
            'cid' => $cid,
            'name' => $channel['name'],
            'add_time' => date('Y-m-d H:i:s',time()),
        ];
        $row = Db::name('agent_channel')->insert($data);
        if($row){
            return success("设置成功");
        }else{
            return error("未作任何更改");
        }
    }
    /**
     * @Apidoc\Title("删除渠道分配")
     * @Apidoc\Desc("删除渠道分配")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("删除渠道分配")
     * @Apidoc\Param("id", type="int",require=true, desc="绑定ID")
     */
    public function del_channel(){
        $id = input("id");
        if(empty($id)) return error("请选择数据");
        $row = Db::name('agent_channel')->where("id","=",$id)->delete();
        if($row){
            return success("删除成功");
        }else {
            return error("未作任何更改");
        }
    }
}