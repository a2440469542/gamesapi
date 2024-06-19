<?php
namespace app\admin\controller;
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
     * @Apidoc\Param("mobile", type="string",require=false, desc="手机：搜索时候传")
     * @Apidoc\Param("cid", type="int",require=true, desc="渠道ID")
     * @Apidoc\Returned(type="array",desc="用户统计管理相关接口",table="cp_user_stat")
     */
    public function index(){
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
    /*public function export(){

    }*/
}