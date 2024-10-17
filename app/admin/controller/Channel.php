<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\Channel as ChannelModel;
use think\facade\Cache;
use think\facade\Db;

/**
 * 渠道管理相关接口
 * @Apidoc\Title("渠道管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(6)
 */
class Channel extends Base{
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
    public function index(){
        if($this->request->isPost()){
            $where = [];
            $limit = input("limit",10);
            $orderBy = input("orderBy", 'cid desc');
            $name = input("name",'');
            if($name != ''){$where[] = ['name','like',"%{$name}%"];}
            $list = ChannelModel::lists($where,$limit);
            return success("获取成功",$list);
        }
        return view();
    }
    /**
     * @Apidoc\Title("平台列表")
     * @Apidoc\Desc("平台列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("平台列表")
     * @Apidoc\Returned("",type="array",desc="PG线路列表",table="cp_plate",children={
     *      @Apidoc\Returned("line",type="array",desc="平台线路列表",table="cp_line")
     * })
     */
    public function pg_list(){
        $PlateModel = app("app\common\model\Plate");
        $where = [];
        $list = $PlateModel->lists($where, 10, "id asc");
        $LineModel = app('app\common\model\Line');
        foreach($list as $k=>$v){
            $line = $LineModel->lists([['pid','=',$v['id']]], 10, "lid asc");
            $list[$k]['line'] = $line;
        }
        return success("获取成功",$list);
    }
    /**
     * @Apidoc\Title("添加编辑渠道")
     * @Apidoc\Desc("添加编辑渠道")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")
     * @Apidoc\Param("",type="array",table="cp_channel")
     */
    public function add(){
        $data = input("post.");
        $res = ChannelModel::add($data);
        if($res){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }
    /**
     * @Apidoc\Title("添加编辑渠道")
     * @Apidoc\Desc("添加编辑渠道")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")
     * @Apidoc\Param("service_path",type="string",desc="客服链接")
     * @Apidoc\Param("tg_path",type="string",desc="tg链接")
     */
    public function set_url(){
        $cid = input('cid',0);
        if($cid == 0) return error("请选择要设置的渠道");
        $data = input('post.');
        $res = ChannelModel::add($data);
        if($res){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }
    /**
     * @Apidoc\Title("设置渠道活动配置")
     * @Apidoc\Desc("设置渠道活动配置")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("设置渠道活动配置")
     * @Apidoc\Param("cid",type="int",default=0,desc="渠道ID")
     * @Apidoc\Param("rank",type="int",default=0,desc="排行榜活动:不开启为0；活动配置ID")
     */
    public function set_activity(){
        $rank = input('rank',0);
        $cid = input('cid',0);
        if($cid == 0) return error("请选择要设置的渠道");
        $data = ['cid' => $cid, 'activity' => ['rank'=>$rank]];
        $res = ChannelModel::add($data);
        if($res){
            return success("保存成功");
        }else{
            return error("数据未做任何更改");
        }
    }
    /**
     * @Apidoc\Title("删除渠道")
     * @Apidoc\Desc("删除渠道")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")0
     * @Apidoc\Param("cid", type="int",require=true, desc="删除数据的ID")
     */
    public function del(){
        $id = input("cid");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $res = ChannelModel::where("cid","=",$id)->update(['is_del'=>1]);
        Db::name("bank")->where("cid","=",$id)->delete();
        Db::name("bill")->where("cid","=",$id)->delete();
        Db::name("box")->where("cid","=",$id)->delete();
        Db::name("box_log")->where("cid","=",$id)->delete();
        Db::name("cash")->where("cid","=",$id)->delete();
        Db::name("game_log")->where("cid","=",$id)->delete();
        Db::name("game_user")->where("cid","=",$id)->delete();
        Db::name("order")->where("cid","=",$id)->delete();
        Db::name("user")->where("cid","=",$id)->delete();
        Db::name("user_stat")->where("cid","=",$id)->delete();
        Db::name("sign")->where("cid","=",$id)->delete();
        Db::name("score_bill")->where("cid","=",$id)->delete();
        Db::name("wages")->where("cid","=",$id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
    /**
     * @Apidoc\Title("渠道统计导出数据")
     * @Apidoc\Desc("渠道统计导出数据")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道统计导出数据")
     * @Apidoc\Returned(type="array",ref="channelStat")
     */
    public function export(){
        $list = ChannelModel::get_all();
        $UserModel = app('app\common\model\User');
        $UserStatModel = app('app\common\model\UserStat');
        $WagesModel = app('app\common\model\Wages');
        $PlateModel = app('app\common\model\Plate');
        $LineModel = app('app\common\model\Line');
        $reg_num = $UserModel::field('count(*) as num,cid')->where('is_rebot','=',0)->group('cid')->select();            //注册人数
        $user_money = $UserModel::field('SUM(money) as money,cid')->where('is_rebot','=',0)->group('cid')->select();     //用户余额
        $cz_num = $UserStatModel::field('count(*) as num,cid')->where("cz_money",">",0)->group('cid')->select();         //充值人数
        $user_stat = $UserStatModel->get_total_money_by_cid(); //统计信息
        $box_num = $UserStatModel::field('count(*) as num,cid')->where("box_money",">",0)->group('cid')->select();       //宝箱领取人数
        $reg_num_arr = $user_money_arr = $cz_num_arr = $user_stat_arr = $box_num_arr = [];
        foreach($reg_num as $v){
            $reg_num_arr[$v['cid']] = $v;
        }
        foreach($user_money as $v){
            $user_money_arr[$v['cid']] = $v;
        }
        foreach($cz_num as $v){
            $cz_num_arr[$v['cid']] = $v;
        }
        foreach($user_stat as $v){
            $user_stat_arr[$v['cid']] = $v;
        }
        foreach($box_num as $v){
            $box_num_arr[$v['cid']] = $v;
        }
        $data = [];
        foreach($list as $k=>$v){
            $arr = [];
            $arr['cid'] = $v['cid'];
            $arr['name'] = $v['name'];
            $arr['add_time'] = $v['add_time'];
            if($v['plate_line']){
                foreach($v['plate_line'] as $k1=>$v1){
                    $plate_name = $PlateModel::where("id","=",$k1)->value('name');
                    $line_name = $LineModel::where("lid","=",$v1)->value('title');
                    $arr[$plate_name] = $line_name;
                }
            }else{
                $arr['PG'] = '';
                $arr['JILI'] = '';
                $arr['PP'] = '';
            }
            //注册人数
            if(isset($reg_num_arr[$v['cid']])){
                $arr['reg_num'] = $reg_num_arr[$v['cid']]['num'];
            }else{
                $arr['reg_num'] = 0;
            }
            //用户余额
            if(isset($user_money_arr[$v['cid']])){
                $arr['user_money'] = round($user_money_arr[$v['cid']]['money'],2);
            }else{
                $arr['user_money'] = 0;
            }
            //充值人数
            if(isset($cz_num_arr[$v['cid']])) {
                $arr['cz_num'] = $cz_num_arr[$v['cid']]['num'];
            }else{
                $arr['cz_num'] = 0;
            }
            //统计信息
            if(isset($user_stat_arr[$v['cid']])){
                $arr['cz_money'] = round($user_stat_arr[$v['cid']]['cz_money'],2);      //总充值金额
                $arr['bet_money'] = round($user_stat_arr[$v['cid']]['bet_money'],2);    //总投注金额
                $arr['cash_money'] = round($user_stat_arr[$v['cid']]['cash_money'],2);  //总提现金额
                $arr['box_money'] = round($user_stat_arr[$v['cid']]['box_money'],2);    //宝箱领取总额
            }else{
                $arr['cz_money'] = 0;
                $arr['bet_money'] = 0;
                $arr['cash_money'] = 0;
                $arr['box_money'] = 0;
            }
            if(isset($box_num_arr[$v['cid']])) {
                $arr['box_num'] = $box_num_arr[$v['cid']]['num'];
            }else{
                $arr['box_num'] = 0;
            }
            $WagesModel->setPartition($v['cid']);
            $wages_num = $WagesModel->wages_num();          //工资领取人数
            $wages_money = $WagesModel->wages_money();      //工资领取金额
            $arr['daili_wages_num'] =    $wages_num['daili'];    //代理工资领取人数
            $arr['daili_wages_money'] = round($wages_money['daili'] ?? '0.00',2);   //代理工资领取总额
            $arr['bozhu_wages_num'] =    $wages_num['bozhu'];    //博主工资领取人数
            $arr['bozhu_wages_money'] = round($wages_money['bozhu'] ?? '0.00',2);   //博主工资领取总额
            $data[] = $arr;
        }
        return success("获取成功",$data);
    }
}