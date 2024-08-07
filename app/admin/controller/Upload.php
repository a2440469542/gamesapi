<?php

namespace app\admin\controller;
use hg\apidoc\annotation as Apidoc;
use app\common\logic\AwsUpload;
/**
 * @Apidoc\Title("文件上传相关")
 * @Apidoc\Group("Upload")
 * @Apidoc\Sort(10)
 */
class Upload
{
    /**
     * @Apidoc\Title("图片上传")
     * @Apidoc\Desc("用于上传图片接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("文件上传")
     * @Apidoc\Param("file", type="file", desc="文件")
     * @Apidoc\Returned("path",type="string", desc="文件路径;保存入库")
     * @Apidoc\Returned("domain",type="string", desc="图片地址域名，保存入库时不可加域名")
     */
    public function uploader()
    {
        $file = request()->file('file');
        try {
            $data = [];
            $data['name'] = $file->getOriginalName();
            $data['size'] = $file->getSize();
            $data['ext'] = $file->extension();
            $data['add_time'] = time();
            // 设定文件上传的大小
            $fileSize = 1024*1024*5;
            validate(['image'=>'fileSize:'.$fileSize.'|fileExt:jpg,png,jpeg,gif'])
                ->check($data);
            //$savename = \think\facade\Filesystem::disk('public')->putFile('uploads', $file);
            $AwsUpload = new AwsUpload();
            $savename = $AwsUpload->uploadToS3($file);
            if($savename['code'] > 0){
                return error($savename['msg']);
            }
            $data['path'] = $savename['url'];
            //$data['path'] = str_replace('\\', '/', '/' . $savename);
            //$data['domain'] = SITE_URL;
            //$data['img'] = $data['domain'].$data['path'];
            return success('上传成功', $data);
        } catch (\think\exception\ValidateException $e) {
            return error($e->getMessage());
        }
    }
    /**
     * @Apidoc\Title("文件上传")
     * @Apidoc\Desc("用于上传文件接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("文件上传")
     * @Apidoc\Param("file", type="file", desc="文件")
     * @Apidoc\Returned("path",type="string", desc="文件路径;保存入库")
     * @Apidoc\Returned("filename",type="string", desc="文件名称")
     */
    public function upload_file(){
        set_time_limit(0);
        $file = request()->file('file');
        if (!$file) return error('请选择您要上传的文件');
        $name = $file->getOriginalName();
        $ext_name = $file->extension();
        if (!in_array($ext_name, ['xls', 'xlsx', "csv"])) return error('请上传xls,xlsx,csv类型的文件');
        $savename = \think\facade\Filesystem::Disk('public')->putFile('uploads', $file);
        $path = str_replace('\\', '/', '/' . $savename);
        $data = ['filename' => $name, 'path' => $path];
        return success("导入成功,可以在文件列表查看导入的文件", $data);
    }
}