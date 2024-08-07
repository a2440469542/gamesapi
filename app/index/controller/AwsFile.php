<?php

namespace app\index\controller;
use app\BaseController;
use think\facade\Db;
use app\common\logic\AwsUpload;

class AwsFile extends BaseController
{
    public function get_game()
    {
        $game = Db::name('game')->select();
        foreach ($game as $k => $v) {
            if($v['img'] != '' && $this->is_http($v['img'])){
                $AwsUpload = new AwsUpload();
                $savename = $AwsUpload->uploadToS32(APP_PATH.'public'.$v['img']);
                if(!$savename['code'] > 0){
                    Db::name('game')->where('gid','=',$v['gid'])->update(['img'=>$savename['url']]);
                    unlink(APP_PATH.'public'.$v['img']);
                }
            }
        }
        echo "完成";
    }
    public function get_ad(){
        $ad = Db::name('ad')->select();
        foreach ($ad as $k => $v) {
            if($v['img'] != '' && $this->is_http($v['img'])){
                $AwsUpload = new AwsUpload();
                $savename = $AwsUpload->uploadToS32(APP_PATH.'public'.$v['img']);
                if(!$savename['code'] > 0){
                    Db::name('ad')->where('id','=',$v['id'])->update(['img'=>$savename['url']]);
                    unlink(APP_PATH.'public'.$v['img']);
                }
            }
        }
        echo "完成";
    }
    public function is_http($url){
        $parsedUrl = parse_url($url);
        // 如果URL中不包含scheme（如http或https），则认为没有域名
        if (empty($parsedUrl['scheme'])) {
            return true;
        }
        return false;
    }
}
