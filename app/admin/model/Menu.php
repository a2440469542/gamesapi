<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/1
 * Time: 20:46
 */
namespace app\admin\model;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\WithoutField;
use hg\apidoc\annotation\AddField;
use hg\apidoc\annotation\Param;
class Menu extends Base
{
    protected $pk = 'id';
    /**
     * @Field("title,icon,breadcrumbHidden,badge,dot,hidden,levelHidden,isCustomSvg,noClosable,noKeepAlive,tabHidden")
     */
    public static function add($data){
        if(isset($data['id']) && $data['id'] > 0){
            $res = self::where("id","=",$data['id'])->update($data);
        }else{
            $res = self::insert($data);
        }
        return $res;
    }
    protected static function auth($menu , $pid=0,$rules=[]){
        $arr=array();
        $rulesArr = array();
        if($rules) {
            $rulesArr = explode(',', $rules);
        }
        foreach ($menu as $v){
            if(isset($v['pid']) && $v['pid']==$pid){
                $v['spread'] = false;
                $v['children'] = self::auth($menu,$v['id'],$rules);
                if(!empty($rulesArr) && in_array($v['id'],$rulesArr) && empty($v['children'])){
                    $v['checked'] = true;
                }
                $arr[] = $v;
                $arr= array_merge($arr,self::auth($menu, $v['id'],$rules));
            }
        }
        return $arr;
    }
    /**
     * @Field("id,pid,title,icon,breadcrumbHidden,badge,dot,name,path,component,is_show,status,sort")
     * @AddField("children",type="array",desc="下级菜单")
     */
    public static function lists(){
        $list = self::select()->order("sort asc")->toArray();
        $list = self::auths($list,0);
        $data = [['value' => 0, 'label' => "顶级分类"]];
        $data = array_values(array_merge($data,$list));
        return $data;
    }
    public static function auths($menu,$pid){
        $arr = [];
        foreach ($menu as $val){
            if(isset($val['pid']) && $val['pid']==$pid){
                $data = [
                    'value' => $val['id'],
                    'label' => $val['name'],
                ];
                $children = self::auths($menu,$val['id']);
                $data['children'] = array_values($children);
                $arr[] = $data;
            }
        }
        return $arr;
    }
    public static function lists_data(){
        $list = self::order("sort asc")->select()->toArray();
        $list = self::auth_menu($list,0);
        return  $list;
    }
    public static function auth_menu($menu,$pid){
        $data = [];
        $arr = [];
        foreach ($menu as $val){
            if(isset($val['pid']) && $val['pid']==$pid){
                $children = self::auth_menu($menu,$val['id']);
                $val['child'] = false;
                if($children){
                    $val['children'] = $children;
                    $val['child'] = true;
                }
                $arr[] = $val;
            }
        }
        return $arr;
    }
}