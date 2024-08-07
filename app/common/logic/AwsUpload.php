<?php
namespace app\common\logic;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AwsUpload
{
    public function uploadToS3($file)
    {
        // 文件路径
        $filePath = $file->getRealPath();
        $fileName = $file->getOriginalName();
        $mimeType = mime_content_type($filePath);

        // AWS S3配置
        $s3Config = [
            'region'  => 'sa-east-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => 'AKIAQ3EGWVCZHLOPMUXI',
                'secret' => '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',
            ],
        ];

        // 创建S3客户端
        $s3 = new S3Client($s3Config);

        // 上传文件到S3
        try {
            $result = $s3->putObject([
                'Bucket' => 'rs3games',
                'Key'    => 'uploads/' . $fileName,
                'SourceFile' => $filePath,
                'ContentType' => $mimeType,
                //'ACL'    => 'public-read', // 公开访问权限
            ]);

            return ['code'=>0,'msg' => '上传成功', 'url' => $result['ObjectURL']];
        } catch (AwsException $e) {
            return ['code'=>500,'msg' => $e->getMessage()];
        }
    }
    public function uploadToS32($filePath)
    {
        // 获取文件名
        $fileName = basename($filePath);
        $mimeType = mime_content_type($filePath);

        // AWS S3配置
        $s3Config = [
            'region'  => 'sa-east-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => 'AKIAQ3EGWVCZHLOPMUXI',
                'secret' => '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',
            ],
        ];
        // 创建S3客户端
        $s3 = new S3Client($s3Config);

        // 上传文件到S3
        try {
            $result = $s3->putObject([
                'Bucket' => 'rs3games',
                'Key'    => 'uploads/' . $fileName,
                'SourceFile' => $filePath,
                'ContentType' => $mimeType,
                //'ACL'    => 'public-read', // 公开访问权限
            ]);
            return ['code' => 0, 'msg' => '上传成功', 'url' => $result['ObjectURL']];
        } catch (AwsException $e) {
            return ['code' => 500, 'msg' => $e->getMessage()];
        }
    }
}