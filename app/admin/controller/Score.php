<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;

/**
 * 积分配置管理相关接口
 * @Apidoc\Title("积分配置管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(9)
 */
class Score extends Base{
    /**
     * @Apidoc\Title("获取积分配置")
     * @Apidoc\Desc("获取积分配置")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("获取积分配置")
     * @Apidoc\Param("day_sign", type="int",require=true, desc="每日签到获取的积分")
     * @Apidoc\Param("day_sign_desc", type="string",require=true, desc="每日签到积分说明")
     * @Apidoc\Param("order_score", type="int",require=true, desc="充值兑换积分倍数")
     * @Apidoc\Param("order_score_desc", type="string",require=true, desc="充值兑换积分倍数说明")
     * @Apidoc\Param("bet_score", type="int",require=true, desc="投注兑换积分倍数")
     * @Apidoc\Param("bet_score_desc", type="string",require=true, desc="投注兑换积分倍数说明")
     */
    public function get_config(){
        $info = Db::name('config')->select();
        $config = [];
        foreach($info as $k=>$v){
            $config[$v['code']] = $v['value'];
        }
        $data['day_sign'] = $config['day_sign'] ?? '';
        $data['day_sign_desc'] = $config['day_sign_desc'] ?? '';
        $data['order_score'] = $config['order_score'] ?? '';
        $data['order_score_desc'] = $config['order_score_desc'] ?? '';
        $data['bet_score'] = $config['bet_score'] ?? '';
        $data['bet_score_desc'] = $config['bet_score_desc'] ?? '';
        return success("保存成功",$data);
    }

    /**
     * @Apidoc\Title("积分配置")
     * @Apidoc\Desc("积分配置")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("积分配置")
     * @Apidoc\Param("day_sign", type="int",require=true, desc="每日签到获取的积分")
     * @Apidoc\Param("day_sign_desc", type="string",require=true, desc="每日签到积分说明")
     * @Apidoc\Param("order_score", type="int",require=true, desc="充值兑换积分倍数")
     * @Apidoc\Param("order_score_desc", type="string",require=true, desc="充值兑换积分倍数说明")
     * @Apidoc\Param("bet_score", type="int",require=true, desc="投注兑换积分倍数")
     * @Apidoc\Param("bet_score_desc", type="string",require=true, desc="投注兑换积分倍数说明")
     */
    public function set_config(){
        $data = input("post.");
        foreach($data as $k=>$v){
            $res = Db::name("config")->where("code","=",$k)->find();
            if($res){
                Db::name("config")->where("id","=",$res['id'])->update(['value'=>$v]);
            }else{
                Db::name("config")->insert(['code'=>$k,'value'=>$v]);
            }
        }
        cache('config',null);
        return success("保存成功");
    }
}