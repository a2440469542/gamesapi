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
        /*$_ip = [
            '112.209.17.154',
            '125.85.80.46',
            '127.0.0.1',
            '119.8.143.173',
            '122.53.38.142',
            '113.248.1.84',
            '113.248.27.150',
            '125.85.83.22',
            '125.85.124.39',
            '125.85.121.75'
        ];
        $ip = get_real_ip__();
        if(!in_array($ip,$_ip)){
            abort(404, '禁止访问');
        }*/
        /*$admin =  session('admin');
        if(empty($this->admin_name) && empty($admin)){
            return redirect(url("/admin/login/index"))->send();
        }*/
    }
}