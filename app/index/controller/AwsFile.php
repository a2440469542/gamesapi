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
    public function get_channel(){
        $ad = Db::name('channel')->field('cid,icon,logo')->select();
        foreach ($ad as $k => $v) {
            $update = [];
            if($v['icon'] != ''){
                $AwsUpload = new AwsUpload();
                $savename = $AwsUpload->uploadToS32(APP_PATH.'public'.$v['icon']);
                if(!$savename['code'] > 0){
                    $update['icon'] = $savename['url'];
                }
            }
            if($v['logo'] != ''){
                $AwsUpload = new AwsUpload();
                $savename = $AwsUpload->uploadToS32(APP_PATH.'public'.$v['logo']);
                if(!$savename['code'] > 0){
                    $update['logo'] = $savename['url'];
                }
            }
            if($update){
                Db::name('channel')->where('cid','=',$v['cid'])->update($update);
                unlink(APP_PATH.'public'.$v['icon']);
                unlink(APP_PATH.'public'.$v['logo']);
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
