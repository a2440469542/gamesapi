<?php

namespace app\api\controller;

use app\BaseController;
use think\App;
use think\facade\Db;
class Base extends BaseController {
    protected $middleware = [\app\middleware\Api::Class];
    protected $cid = 0;
    public function __construct(App $app)
    {
        parent::__construct($app);
        /*$action = strtolower(request()->controller() . '.' . request()->action());
        write_log("======接口地址=======\n",'post');
        write_log($action,'post');
        write_log("======接口参数=======\n",'post');
        $post = $this->request->post();
        write_log($post,'post');*/
        $cid = $this->request->header('cid');
        $this->cid = $cid;
    }
}