<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
/**
 * 商品管理相关接口
 * @Apidoc\Title("商品管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(9)
 */
class Goods extends Base{
    /**
     * @Apidoc\Title("商品列表")
     * @Apidoc\Desc("商品列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("商品")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("keyword", type="string",require=false, desc="搜索时候传：商品名称")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="商品列表",table="cp_goods")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'sort desc');
        $keyword = input("keyword");
        if($keyword) $where[] = ['title',"LIKE","%{$keyword}%"];
        $goodsModel = app('app\common\model\Goods');
        $list = $goodsModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑轮播图")
     * @Apidoc\Desc("添加编辑轮播图")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("轮播图")
     * @Apidoc\Param("",type="array",table="cp_goods")
     */
    public function edit(){
        $data = input("post.");
        $goodsModel = app('app\common\model\Goods');
        return $goodsModel->add($data);
    }

    /**
     * @Apidoc\Title("删除轮播图")
     * @Apidoc\Desc("删除轮播图")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("轮播图")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的轮播图ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $goodsModel = app('app\common\model\Goods');
        $res = $goodsModel->where('id', $id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
    /**
     * @Apidoc\Title("用户兑换记录")
     * @Apidoc\Desc("用户兑换记录")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("用户兑换记录")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("mobile", type="string",require=false, desc="搜索时候传：用户账号手机号")
     * @Apidoc\Param("keyword", type="string",require=false, desc="搜索时候传：商品名称")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="商品列表",table="cp_goods_order")
     */
    public function goods_order(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'id desc');
        $keyword = input("keyword",'');
        $mobile = input("mobile",'');
        if($keyword) $where[] = ['title',"LIKE","%{$keyword}%"];
        if($mobile) $where[] = ['mobile',"=",$mobile];
        $GoodsOrderModel = app('app\common\model\GoodsOrder');
        $list = $GoodsOrderModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("修改发货状态")
     * @Apidoc\Desc("修改发货状态")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("修改发货状态")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("id", type="int",require=true, desc="订单ID")
     * @Apidoc\Param("status", type="int",require=true, desc="状态：0=待发货；1=已发货")
     * @Apidoc\Param("desc", type="string",require=false, desc="搜索时候传：商品名称")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="商品列表",table="cp_goods_order")
     */
    public function send_goods(){
        $id = input("id");
        $desc = input("desc");
        $status = input("status",'');
        if(!$id){
            return error("请选择要发货的数据");
        }
        if($status == ''){
            return error("请选择发货状态");
        }
        $GoodsOrderModel = app('app\common\model\GoodsOrder');
        $res = $GoodsOrderModel->where('id', '=',$id)->update(['status'=>$status,'desc'=>$desc,'send_time'=>time()]);
        if($res){
            return success("保存成功");
        }else{
            return error("保存失败");
        }
    }
    /**
     * @Apidoc\Title("积分数据统计")
     * @Apidoc\Desc("积分数据统计")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("积分数据统计")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("date", type="string",require=false, desc="搜索时候传：日期")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="积分数据统计",table="cp_score_stat",children={
     *      @Apidoc\Returned("order_num",type="int",desc="充值人数"),
     *      @Apidoc\Returned("bet_num",type="int",desc="下注人数"),
     *      @Apidoc\Returned("total_score",type="int",desc="总领取积分")
     *  })
     */
    public function static(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'date desc');
        $date = input("date",'');
        if($date) $where[] = ['date',"=",$date];
        $ScoreStatModel = app('app\common\model\ScoreStat');
        $list = $ScoreStatModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
}