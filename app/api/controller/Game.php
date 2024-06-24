<?php

namespace app\api\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 游戏相关接口
 * @Apidoc\Title("游戏相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(3)
 */
class Game extends Base
{
    /**
     * @Apidoc\Title("游戏列表")
     * @Apidoc\Desc("游戏列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("keyword", type="string",require=false, desc="游戏名：搜索时候传")
     * @Apidoc\Param("pid", type="int",require=false, desc="平台ID：默认0")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="游戏平台列表",table="cp_game")
     */
    public function get_game_list(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'gid desc');
        $keyword = input("keyword");
        if($keyword){
            $where[] = ['name', 'like', '%'.$keyword.'%'];
        }
        $list = model('app\common\model\Game')->lists($where, $limit, $orderBy);
        foreach ($list['data'] as &$v){
            $v['img'] = SITE_URL.$v['img'];
        }
        return success("obter sucesso", $list);  //获取成功
    }
    /**
     * @Apidoc\Title("用户下注记录")
     * @Apidoc\Desc("用户下注记录")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户下注记录")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("orderBy", type="string",require=false, desc="字段排序")
     * @Apidoc\Param("limit", type="int",require=true, desc="每页的条数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="用户下注记录",table="cp_game_log",children={
     *      @Apidoc\Returned("name",type="string",desc="游戏名称")
     * })
     */
    public function get_game_log(){
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $limit = input("limit",10);
        $orderBy = input("orderBy", 'add_time desc');
        $where[] = ['uid',"=",$uid];
        $list = model('app\common\model\GameLog',$cid)->getList($where,$limit,$orderBy);
        return success("obter sucesso",$list);//获取成功
    }
}
