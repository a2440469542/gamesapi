<?php

namespace app\admin\controller;

use app\BaseController;
use think\App;
use think\facade\Db;
use think\facade\View;

class Base extends BaseController {
    protected $HrefId,$adminRules;
    public $ip;
    protected $middleware = [\app\middleware\Auth::Class];
    public function __construct(App $app)
    {
        parent::__construct($app);
        $_ip = [
            '112.209.17.154',
            '125.85.68.125',
            '127.0.0.1',
            '119.8.143.173',
            '122.53.38.142',
            '113.248.1.84',
            '113.248.27.150',
            '113.248.233.50',
            '171.223.95.222',
            '113.248.243.244',
            '113.248.231.132',
            '118.140.56.84',
            '113.248.2.5',
            '54.251.96.165',
            '182.148.200.164',
            '113.248.5.93',
            '45.227.58.124'
        ];
        $ip = get_real_ip__();
        if(!in_array($ip,$_ip)){
            abort(404, '禁止访问');
        }
        /*$admin =  session('admin');
        if(empty($this->admin_name) && empty($admin)){
            return redirect(url("/admin/login/index"))->send();
        }*/
    }
}