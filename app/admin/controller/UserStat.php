<?php
namespace app\admin\controller;
use app\common\model\Channel as ChannelModel;
use hg\apidoc\annotation as Apidoc;
use app\common\model\User as UserModel;
use app\admin\model\Menu;
/**
 * 用户统计管理相关接口
 * @Apidoc\Title("用户统计管理相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(9)
 */
class UserStat extends Base{
    /**
     * @Apidoc\Title("用户统计管理相关接口")
     * @Apidoc\Desc("用户统计管理相关接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户统计管理相关接口")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("mobile", type="string",require=false, desc="手机：搜索时候传")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Param("date", type="string",require=false, desc="日期")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="用户统计管理相关接口",table="cp_user_stat")
     * @Apidoc\Returned("avg_cz_money",type="float",desc="平均充值金额")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $cid = input("cid", 0);
        $mobile = input("mobile", '');
        $orderBy = input("orderBy", 'id desc');
        $date = input("date", '');
        if ($mobile) {
            $where[] = ['mobile', "=", $mobile];
        }
        if($date){
            $where[] = ['date', '=', $date];
        }
        if($cid === 0){
            return error("请选择渠道");
        }
        $userModel = app('app\common\model\UserStat');
        $userModel->setPartition($cid);
        $list = $userModel->get_list($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    public function export(){
        $where = [];
        $limit = input("limit");
        $cid = input("cid", 0);
        $mobile = input("mobile", '');
        $orderBy = input("orderBy", 'id desc');
        if ($mobile) {
            $where[] = ['mobile', "=", $mobile];
        }
        if($cid === 0){
            return error("请选择渠道");
        }
        $userModel = app('app\common\model\UserStat');
        $userModel->setPartition($cid);
        $list = $userModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
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
    /**
     * @Apidoc\Title("渠道数据统计")
     * @Apidoc\Desc("渠道数据统计")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道数据统计")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("date", type="string",require=false, desc="日期：搜索时候传，默认为空")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="统计列表",table="cp_channel",children={
     *      @Apidoc\Returned("reg_num",type="int",desc="注册人数"),
     *      @Apidoc\Returned("cz_num",type="int",desc="充值人数"),
     *      @Apidoc\Returned("cz_money",type="float",desc="总充值金额"),
     *      @Apidoc\Returned("cash_money",type="float",desc="总提现金额"),
     *      @Apidoc\Returned("box_num",type="int",desc="宝箱领取人数")
     * })
     */
    public function channel_stat(){
        $where = [];
        $limit = input("limit",10);
        $orderBy = input("orderBy", 'cid desc');
        $date = input("date",'');
        $list = ChannelModel::lists($where,$limit);
        $UserModel = app('app\common\model\User');
        foreach ($list['data'] as &$item){
            $UserStatModel = model('app\common\model\UserStat',$item['cid']);
            $WagesModel = model('app\common\model\Wages',$item['cid']);
            $reg_num = $UserModel->reg_num($item['cid'],$date);     //注册人数
            $cz_num = $UserStatModel->get_cz_num($date);             //充值人数
            $user_stat = $UserStatModel->get_total_money($date); //统计信息
            $box_num = $UserStatModel->box_num($date);           //宝箱领取人数
            $item['cz_num'] = $cz_num;
            $item['reg_num'] = $reg_num;
            $item['cz_money'] = round($user_stat['cz_money']   ?? '0.00',2);   //总充值金额
            $item['cash_money'] = round($user_stat['cash_money'] ?? '0.00',2);   //总提现金额
            $item['bet_money'] = round($user_stat['bet_money'] ?? '0.00',2);   //总投注金额
            $item['box_num'] = $box_num;
        }
        return success("获取成功",$list);
    }
    /**
     * @Apidoc\Title("渠道每日统计")
     * @Apidoc\Desc("渠道每日统计")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道每日统计")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="统计列表",table="cp_user_stat",children={
     *      @Apidoc\Returned("reg_num",type="int",desc="注册人数"),
     *      @Apidoc\Returned("cz_num",type="int",desc="充值人数"),
     *      @Apidoc\Returned("box_num",type="int",desc="宝箱领取人数")
     * })
     */
    public function channel_day_stat(){
        $where = [];
        $cid = input("cid", 0);
        $limit = input("limit",10);
        $orderBy = input("orderBy", 'date desc');
        if($cid > 0){
            $where[] = ['us.cid','=',$cid];
        }
        $list = app('app\common\model\UserStat')->get_date_list($where,$limit, $orderBy);
        $UserModel = app('app\common\model\User');
        foreach ($list['data'] as &$item){
            $UserStatModel = model('app\common\model\UserStat',$item['cid']);
            $item['reg_num'] = $UserModel->reg_num($item['cid'],$item['date']);     //注册人数
            $item['cz_num']  = $UserStatModel->get_cz_num($item['date']);           //充值人数
            $item['box_num'] = $UserStatModel->box_num($item['date']);              //宝箱领取人数
        }
        return success("获取成功",$list);
    }
}