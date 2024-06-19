<?php

namespace app\admin\model;

use think\Model;

class Base extends Model
{
    protected $partition = '';
    public function setPartition($value)
    {
        if(is_array($value)){
            foreach ($value as &$v) {
                $v = 'p'.$v;
            }
        }else{
            $value = 'p'.$value;
        }
        $this->partition = $value;
    }
}