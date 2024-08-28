<?php

namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\StarCoin as StarCoinModel;
use think\facade\Db;

/**
 * 活动彩金档位管理
 * @Apidoc\Title("活动彩金档位管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(4)
 */
class StarCoin extends Base
{
    /**
     * @Apidoc\Title("添加活动彩金档位")
     * @Apidoc\Desc("添加活动彩金档位接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("活动彩金档位")
     * @Apidoc\Param("sid", type="int",require=true, desc="活动ID")
     * @Apidoc\Param("phrase",ref="app\common\model\StarCoin\add")
     */
    public function add(){
        $min = input("min");
        $max = input("max");
        $money = input("money");
        if(empty($sid)) return error("缺少必要参数sid");
        if(empty($min)) return error("请输入邀请人区间最小数");
        if(empty($max)) return error("请输入邀请人区间最大数");
        if(empty($money)) return error("请输入彩金");
        if($max < $min) return error("邀请人区间最大数不能小于邀请人区间最小数");
        $data = [
            'sid' => $sid,
            'min' => $min,
            'max' => $max,
            'money' => $money,
            'admin_name' => $this->request->admin_name,
            'update_time' => time()
        ];
        $id = StarCoinModel::insertGetId($data);
        if($id > 0){
            $data['id'] = $id;
            $data["update_time"] = date("Y-m-d H:i:s",$data["update_time"]);
            return success("添加成功", $data);
        }else{
            return error("添加失败");
        }
    }
    /**
     * @Apidoc\Title("修改活动彩金档位")
     * @Apidoc\Desc("修改活动彩金档位")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("活动彩金档位")
     * @Apidoc\Param("id", type="int",require=true, desc="推广短语ID")
     * @Apidoc\Param(ref="app\common\model\StarCoin\add")
     */
    public function edit(){
        $id = input("id");
        $data = input("post.");
        if(empty($id)) return error("请选择要修改的数据");
        $row = StarCoinModel::where("id","=",$id)->save($data);
        if($row){
            return success("修改成功", $data);
        }else{
            return error("修改失败");
        }
    }
    /**
     * @Apidoc\Title("删除活动彩金档位")
     * @Apidoc\Desc("删除活动彩金档位接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("删除活动彩金档位")
     * @Apidoc\Param("id", type="int",require=true, desc="活动彩金档位ID")
     */
    public function del(){
        $id = input("id");
        if(empty($id)) return error("请选择要删除的数据");
        $row = StarCoinModel::where("id","=",$id)->delete();
        if($row){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}