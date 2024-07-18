<?php

namespace app\agent\controller;

use app\BaseController;
use think\App;
use think\facade\Db;
use think\facade\View;

class Base extends BaseController {
    protected $HrefId,$adminRules;
    public $ip;
    protected $middleware = [\app\middleware\Agent::Class];
    public function __construct(App $app)
    {
        parent::__construct($app);
    }
}