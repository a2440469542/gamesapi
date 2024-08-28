<?php

namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\StarPhrase as StarPhraseModel;
use think\facade\Db;

/**
 * 活动推广短语管理
 * @Apidoc\Title("活动推广短语相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(3)
 */
class StarPhrase extends Base
{
    /**
     * @Apidoc\Title("添加推广短语")
     * @Apidoc\Desc("添加推广短语接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("数据文件")
     * @Apidoc\Param("sid", type="int",require=true, desc="活动ID")
     * @Apidoc\Param("content", type="string",require=true, desc="推广短语")
     */
    public function add(){
        $sid = input("sid");
        $content = input("content");
        if(empty($sid)) return error("缺少必要参数sid");
        if(empty($content))  return error("请输入推广短语");
        $data = [
            'content' => $content,
            'admin_name' => $this->request->admin_name,
            'update_time' => time()
        ];
        $id = StarPhraseModel::insertGetId($data);
        if($id > 0){
            $data['id'] = $id;
            $data["update_time"] = date("Y-m-d H:i:s",$data["update_time"]);
            return success("添加成功", $data);
        }else{
            return error("添加失败");
        }
    }
    /**
     * @Apidoc\Title("修改推广短语")
     * @Apidoc\Desc("修改推广短语接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("数据文件")
     * @Apidoc\Param("id", type="int",require=true, desc="推广短语ID")
     * @Apidoc\Param("content", type="string",require=true, desc="推广短语")
     */
    public function edit(){
        $id = input("id");
        $content = input("content");
        if(empty($id)) return error("请选择要修改的数据");
        if(empty($content))  return error("请输入推广短语");
        $data = [
            'content' => $content,
            'admin_name' => $this->request->admin_name,
            'update_time' => time()
        ];
        $row = StarPhraseModel::where("id","=",$id)->save($data);
        if($row){
            $data["update_time"] = date("Y-m-d H:i:s",$data["update_time"]);
            return success("修改成功", $data);
        }else{
            return error("修改失败");
        }
    }
    /**
     * @Apidoc\Title("删除推广短语")
     * @Apidoc\Desc("删除推广短语接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("删除推广短语")
     * @Apidoc\Param("id", type="int",require=true, desc="推广短语ID")
     */
    public function del(){
        $id = input("id");
        if(empty($id)) return error("请选择要删除的数据");
        $row = StarPhraseModel::where("id","=",$id)->delete();
        if($row){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
}