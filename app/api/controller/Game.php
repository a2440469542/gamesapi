<?php

namespace app\api\controller;
use app\service\game\GamePlatformFactory;
use hg\apidoc\annotation as Apidoc;
use think\facade\Request;
use think\facade\Db;

/**
 * 游戏相关接口
 * @Apidoc\Title("游戏相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(3)
 */
class Game extends Base
{
    /**
     * @Apidoc\Title("游戏平台列表")
     * @Apidoc\Desc("游戏平台列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("游戏平台")
     * @Apidoc\Returned(type="array",desc="游戏平台列表",table="cp_plate")
     */
    public function plate(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'is_live desc');
        $PlateModel = app('app\common\model\Plate');
        $list = $PlateModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
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
        $orderBy = input("orderBy", 'sort desc');
        $keyword = input("keyword");
        $pid = input("pid");
        if($keyword){
            $where[] = ['name', 'like', '%'.$keyword.'%'];
        }
        if($pid){
            $where[] = ['pid', '=', $pid];
        }
        $where[] = ['is_open','=',1];
        $list = model('app\common\model\Game')->lists($where, $limit, $orderBy);
        /*foreach ($list['data'] as &$v){
            $v['img'] = addDomainIfMissing($v['img'],SITE_URL);
        }*/
        return success("obter sucesso", $list);  //获取成功
    }
    /**
     * @Apidoc\Title("直播游戏jackpot以及下面的分类")
     * @Apidoc\Desc("直播游戏jackpot以及下面的分类")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("直播游戏jackpot以及下面的分类")
     * @Apidoc\Returned("",type="array",desc="列表",table="cp_game_slot",children={
     *     @Apidoc\Returned("gameName",type="string",desc="游戏名称"),
     *     @Apidoc\Returned("jackpot",type="string",desc="jackpot"),
     *     @Apidoc\Returned("long_img",type="string",desc="长图标"),
     *     @Apidoc\Returned("img",type="string",desc="图标"),
     *     @Apidoc\Returned("num",type="int",desc="机台数量")
     *  })
     */
    public function live_game_jackpot(){
        $list = Db::name('game_slot')
            ->alias('gs')
            ->join('game g','gs.gameName = g.name')
            ->group('gs.gameName')
            ->field('gs.gameName,gs.jackpot,count(slotId) as num,g.long_img,g.img')
            ->select();
        return success("obter sucesso", $list);  //获取成功
    }
    /**
     * @Apidoc\Title("直播游戏列表")
     * @Apidoc\Desc("直播游戏列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("直播游戏")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("gameName", type="string",require=false, desc="游戏名称")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="游戏列表",table="cp_game_slot")
     */
    public function live_game(){
        $limit = Request::post('limit',20);
        $gameName = input('gameName','');
        $where = [];
        if($gameName){
            $where[] = ['gs.gameName',"=",$gameName];
        }
        $list = Db::name('game_slot')
            ->alias('gs')
            ->field('gs.*,g.long_img,g.img')
            ->join('game g','gs.gameName = g.name')
            ->where('machineStatus','>=',0)
            ->where('state',"=",1)
            ->where('gs.status','=',1)
            ->where($where)
            ->order('machineStatus desc')
            ->paginate($limit);
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
        $where[] = ['gl.uid',"=",$uid];
        $list = model('app\common\model\GameLog',$cid)->getList($where,$limit,$orderBy);
        return success("obter sucesso",$list);//获取成功
    }
}
