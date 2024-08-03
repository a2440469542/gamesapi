<?php
namespace app\common\logic;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AwsUpload
{
    public function uploadToS3($file)
    {
        // 获取上传的文件
        $file = request()->file('image');
        if (!$file) {
            return json(['error' => '没有上传文件']);
        }

        // 文件路径
        $filePath = $file->getRealPath();
        $fileName = $file->getOriginalName();

        // AWS S3配置
        $s3Config = [
            'region'  => '你的区域',
            'version' => 'latest',
            'credentials' => [
                'key'    => '你的访问密钥ID',
                'secret' => '你的秘密访问密钥',
            ],
        ];

        // 创建S3客户端
        $s3 = new S3Client($s3Config);

        // 上传文件到S3
        try {
            $result = $s3->putObject([
                'Bucket' => '你的存储桶名称',
                'Key'    => 'uploads/' . $fileName,
                'SourceFile' => $filePath,
                'ACL'    => 'public-read', // 公开访问权限
            ]);

            return ['message' => '上传成功', 'url' => $result['ObjectURL']];
        } catch (AwsException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}