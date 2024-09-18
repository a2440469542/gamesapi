<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/1
 * Time: 20:46
 */
namespace app\common\model;
use app\admin\model\Base;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\AddField;
use think\facade\Cache;
use think\facade\Db;

class UserInfo extends Base
{
    protected $pk = 'uid';
    public function getRegTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function getLastLoginTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function add($data){

    }

}