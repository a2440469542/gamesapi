<?php
namespace app\common\controller;

use hg\apidoc\annotation\Param;
use hg\apidoc\annotation\Returned;
use hg\apidoc\annotation\Query;
use hg\apidoc\annotation\Before;
use hg\apidoc\annotation\After;
use hg\apidoc\annotation\Header;

/**
 * NotParse
 */
class Definitions
{
    /**
     * 获取分页数据列表的参数
     * @Param("page",type="int",require=true,default=1,desc="查询页码")
     * @Param("limit",type="int",require=true,default=20,desc="查询每页条数")
     */
    public function pagingParam(){}
    /**
     * @Returned("total", type="int", desc="总条数")
     * @Returned("per_page", type="int", desc="每页数量")
     * @Returned("current_page", type="int", desc="当前页码")
     * @Returned("last_page", type="int", desc="下一页")
     */
    public function pageReturn(){}
    /**
     * 获取一条数据明细
     * @Query("id",type="int",require=true,desc="唯一id")
     */
    public function getDetail(){}
    /**
     * 删除一条数据明细
     * @Param("id",type="int",require=true,desc="唯一id")
     * @Returned("data",type="boolean",replaceGlobal=true,desc="删除状态")
     */
    public function delDetail(){}

    /**
     * 返回字典数据
     * @returned("id",type="int",desc="唯一id")
     * @returned("name",type="string",desc="字典名")
     * @returned("value",type="string",desc="字典值")
     */
    public function dictionary(){}
    /**
     * @returned("cid",type="int",desc="渠道ID")
     * @returned("name",type="int",desc="渠道名")
     * @returned("add_time",type="string",desc="添加时间")
     * @returned("reg_num",type="int",desc="注册人数")
     * @returned("cz_num",type="int",desc="充值人数")
     * @returned("cz_money",type="float",desc="总充值金额")
     * @returned("bet_money",type="float",desc="总投注金额")
     * @returned("cash_money",type="float",desc="总提现金额")
     * @returned("box_num",type="int",desc="宝箱领取人数")
     * @returned("daili_wages_num",type="int",desc="代理工资领取人数")
     * @returned("daili_wages_money",type="float",desc="代理工资领取总额")
     * @returned("bozhu_wages_num",type="int",desc="博主工资领取人数")
     * @returned("bozhu_wages_money",type="float",desc="博主工资领取总额")
     */
    public function channelStat(){}
    /**
     * 表单验证公用事件
     * @Before(event="ajax",url="/demo/test/getFormToken",method="POST",contentType="appicateion-json",
     *    @Before(event="setParam",key="abc",value="params.phone"),
     *    @Before(event="setParam",key="cc",value="123456"),
     *    @After(event="setHeader",key="X-CSRF-TOKEN",value="res.data.data")
     * )
     */
    public function formTokenEvent(){}
}