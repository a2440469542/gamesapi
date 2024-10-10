<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 游戏管理相关接口
 * @Apidoc\Title("游戏管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(8)
 */
class Game extends Base{
    /**
     * @Apidoc\Title("游戏列表")
     * @Apidoc\Desc("游戏列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("pid", type="int",require=true, desc="游戏平台ID")
     * @Apidoc\Param("keyword", type="string",require=false, desc="游戏名：搜索时候传")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="游戏平台列表",table="cp_game")
     */
    public function index(){
        if($this->request->isPost()) {
            $where = [];
            $limit = input("limit");
            $orderBy = input("orderBy", 'sort desc,gid desc');
            $keyword = input("keyword");
            $pid = input("pid");
            if ($keyword) {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            if($pid){
                $where[] = ["pid","=",$pid];
            }
            $GameModel = app("app\common\model\Game");
            $list = $GameModel->lists($where, $limit, $orderBy);
            return success("获取成功", $list);
        }
        return view();
    }
    /**
     * @Apidoc\Title("添加编辑游戏")
     * @Apidoc\Desc("添加编辑游戏")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏平台")
     * @Apidoc\Param("",type="array",table="cp_game")
     */
    public function edit(){
        $data = input("post.");
        if(!isset($data['name']) || !$data['name']){
            return error("请输入游戏名");
        }
        if(!isset($data['code']) || !$data['code']){
            return error("请输入游戏code");
        }
        $GameModel = app("app\common\model\Game");
        return $GameModel->add($data);
    }

    /**
     * @Apidoc\Title("删除游戏")
     * @Apidoc\Desc("删除游戏")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏平台")
     * @Apidoc\Param("gid", type="int",require=true, desc="删除数据的游戏ID")
     */
    public function del(){
        $gid = input("gid");
        if(!$gid){
            return error("请选择要删除的数据");
        }
        $GameModel = app("app\common\model\Game");
        $res = $GameModel->where('gid',"=", $gid)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}