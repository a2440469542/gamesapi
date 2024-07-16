<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\WagesConfig as WagesConfigModel;
use think\facade\Cache;

/**
 * 渠道工资配置相关接口
 * @Apidoc\Title("渠道工资配置相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(11)
 */
class WagesConfig extends Base{
    /**
     * @Apidoc\Title("配置信息")
     * @Apidoc\Desc("配置信息获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")
     * @Apidoc\Param("cid",type="int",desc="渠道ID")
     * @Apidoc\Returned("",type="object",desc="相关配置",table="cp_wages_config")
     */
    public function index(){
        if($this->request->isPost()){
            $cid = input("cid");
            if(empty($cid)) return error("渠道ID不能为空");
            $WagesConfigModel = app('app\common\model\WagesConfig');
            $info = $WagesConfigModel->getInfo($cid);
            if(empty($info)){
                $info['type'] = 1;
            }
            return success("获取成功",$info);
        }
        return view();
    }
    /**
     * @Apidoc\Title("添加编辑渠道")
     * @Apidoc\Desc("添加编辑渠道")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("渠道")
     * @Apidoc\Param("",type="array",table="cp_wages_config")
     */
    public function edit(){
        $data = input("post.");
        if(!isset($data['cid']) || empty($data['cid'])){
            return error("渠道ID不能为空");
        }
        if(!isset($data['type']) || !in_array($data['type'],[1,2])){
            return error("类型错误");
        }
        $WagesConfigModel = app('app\common\model\WagesConfig');
        return $WagesConfigModel->add($data);
    }
}