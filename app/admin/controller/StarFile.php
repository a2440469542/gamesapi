<?php

namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\model\StarFile as StarFileModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

/**
 * 活动数据文件管理
 * @Apidoc\Title("活动数据文件相关")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(3)
 */
class StarFile extends Base
{
    /**
     * @Apidoc\Title("添加数据文件")
     * @Apidoc\Desc("添加数据文件活动")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("jiu")
     * @Apidoc\Tag("数据文件")
     * @Apidoc\Param("sid", type="int",require=true, desc="活动ID")
     * @Apidoc\Param("file", type="file",require=true, desc="数据文件")
     */
    public function add(){
        set_time_limit(0);
        $file = request()->file('file');
        $sid = input("sid");
        if (!$file) return error('请选择您要上传的文件');
        if(empty($sid)) return error("缺少必要参数sid");
        $name = $file->getOriginalName();
        $ext_name = $file->extension();
        if (!in_array($ext_name, ['xls', 'xlsx', "csv"])) return error('请上传xls,xlsx,csv类型的文件');
        $savename = \think\facade\Filesystem::Disk('public')->putFile('uploads', $file);

        // 读取文件内容
        $filePath = $file->getPathname();
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $datas = $sheet->toArray();
        $products = [];
        foreach ($datas as $key =>$row) {
            // 假设第一行是表头，跳过
            if ($key === 0) {
                continue;
            }
            $products[] = [
                'mobile' =>  trim(strval($row[0]))
                // 更多字段...
            ];
        }
        Db::name("star_mobile")->insertAll($products);

        $data = [
            'filename' => $name,
            'file' => $savename,
            'update_time' => time(),
            'admin_name' => $this->request->admin_name,
            'status'  => 0,
        ];
        $id = StarFileModel::insertGetId($data);
        $data['id'] = $id;
        $data["update_time"] = date("Y-m-d H:i:s",$data["update_time"]);

        return success("添加成功", $data);
    }
}