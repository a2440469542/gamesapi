<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\Channel as ChannelModel;
use think\facade\Cache;

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
     * @Apidoc\Returned("",type="array",desc="渠道列表",table="cp_channel")
     */
    public function index(){
        if($this->request->isPost()){
            $where = [];
            $limit = input("limit");
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
     * @Apidoc\Title("删除渠道")
     * @Apidoc\Desc("删除渠道")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")
     * @Apidoc\Param("cid", type="int",require=true, desc="删除数据的ID")
     */
    public function del(){
        $id = input("cid");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $res = ChannelModel::where("cid","=",$id)->update(['is_del'=>1]);
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}