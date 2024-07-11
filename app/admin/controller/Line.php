<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\Plate as PlateModel;
use app\admin\model\Menu;
/**
 * 平台线路管理相关接口
 * @Apidoc\Title("平台线路管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(7)
 */
class Line extends Base{
    /**
     * @Apidoc\Title("平台线路列表")
     * @Apidoc\Desc("平台线路列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("平台线路")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("lid", type="int",require=false, desc="线路ID")
     * @Apidoc\Param("pid", type="int",require=false, desc="平台ID")
     * @Apidoc\Returned(type="array",desc="平台线路列表",table="cp_plate")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $lid = input("lid",0);
        $pid = input("pid",0);
        if($lid > 0){
            $where[] = ['lid','=',$lid];
        }
        if($pid > 0){
            $where[] = ['pid','=',$pid];
        }
        $orderBy = input("orderBy", 'lid desc');
        $PlateModel = app('app\common\model\Line');
        $list = $PlateModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑平台线路")
     * @Apidoc\Desc("添加编辑平台线路")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("平台线路")
     * @Apidoc\Param("",type="array",table="cp_plate")
     */
    public function edit(){
        $data = input("post.");
        if(!isset($data['title']) || !$data['title']){
            return error("请输入平台线路名");
        }
        if(!isset($data['app_id']) || !$data['app_id']){
            return error("请输入平台线路应用ID");
        }
        if(!isset($data['app_secret']) || !$data['app_secret']){
            return error("请输入平台线路应用密钥");
        }
        if(!isset($data['url']) || !$data['url']){
            return error("请输入平台线路接口地址");
        }
        if(!isset($data['pid']) || !$data['pid']){
            return error("请选择平台");
        }
        $PlateModel = app("app\common\model\Line");
        return $PlateModel->add($data);
    }

    /**
     * @Apidoc\Title("删除平台线路")
     * @Apidoc\Desc("删除平台线路")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("平台线路")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的平台线路ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $PlateModel = app("app\common\model\Plate");
        $res = $PlateModel->where('lid', $id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}