<?php
namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use PhpOffice\PhpSpreadsheet\IOFactory;
/**
 * VIP等级管理相关接口
 * @Apidoc\Title("VIP等级管理相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(9)
 */
class Level extends Base{
    /**
     * @Apidoc\Title("VIP等级列表")
     * @Apidoc\Desc("VIP等级列表获取")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("VIP等级")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="VIP等级列表",table="cp_level")
     */
    public function index(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'level desc');
        $cid = input("id");
        if($cid) $where[] = ['id',"=",$cid];
        $levelModel = app('app\common\model\Level');
        $list = $levelModel->lists($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
    /**
     * @Apidoc\Title("添加编辑VIP等级")
     * @Apidoc\Desc("添加编辑VIP等级")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("VIP等级")
     * @Apidoc\Param("",type="array",table="cp_level")
     */
    public function edit(){
        $data = input("post.");
        $levelModel = app('app\common\model\Level');
        return $levelModel->add($data);
    }
    /**
     * @Apidoc\Title("导入等级表格")
     * @Apidoc\Desc("导入等级表格接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("导入等级表格")
     * @Apidoc\Param("file", type="file", desc="文件")
     */
    public function import(){
        // 获取上传的文件
        $file = request()->file('file');

        // 检查文件是否上传成功
        if (!$file) {
            return error("请选择上传的文件");
        }

        // 读取文件内容
        $filePath = $file->getPathname();

        // 使用 PhpSpreadsheet 读取 Excel 文件
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // 准备要插入的数据
        $products = [];
        foreach ($data as $row) {
            // 假设第一行是表头，跳过
            if ($row === $data[0]) {
                continue;
            }
            $products[] = [
                'level' => $row[0],           // 等级
                'exp' => $row[1],             // 经验
                'bonus' => $row[2],           // 奖金
                'cash_num' => $row[3],        // 每日提款次数
                'cash_money' => $row[4],      // 每日提款限制
                'week_back' => $row[5],       // 每周返现比例
                'beet_back_day' => $row[6],   // 每日投注返现比例
                'multiple' => $row[7],        // 倍数
                // 更多字段...
            ];
        }
        $levelModel = app('app\common\model\Level');
        $row = $levelModel->insertAll($products);
        if($row){
            return success("导入成功");
        }else{
            return error("导入失败");
        }
    }
    /**
     * @Apidoc\Title("删除VIP等级")
     * @Apidoc\Desc("删除VIP等级")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("VIP等级")
     * @Apidoc\Param("id", type="int",require=true, desc="删除数据的VIP等级ID")
     */
    public function del(){
        $id = input("id");
        if(!$id){
            return error("请选择要删除的数据");
        }
        $levelModel = app('app\common\model\Level');
        $res = $levelModel->where('id', $id)->delete();
        if($res){
            return success("删除成功");
        }else{
            return error("删除失败");
        }
    }
    /**
     * @Apidoc\Title("VIP等级统计列表")
     * @Apidoc\Desc("VIP等级统计列表")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("VIP等级统计列表")
     * @Apidoc\Param(ref="pagingParam",desc="分页参数")
     * @Apidoc\Param("cid", type="string",require=false, desc="字段排序")
     * @Apidoc\Returned(ref="pageReturn")
     * @Apidoc\Returned("data",type="array",desc="VIP等级列表")
     */
    public function get_stat(){
        $where = [];
        $limit = input("limit");
        $orderBy = input("orderBy", 'level desc');
        $cid = input("cid");
        if($cid) $where[] = ['cid',"=",$cid];
        $levelModel = app('app\common\model\Level');
        $list = $levelModel->stat($where, $limit, $orderBy);
        return success("获取成功", $list);
    }
}