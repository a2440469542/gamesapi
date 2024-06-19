<?php

namespace app\plate\controller;

use app\BaseController;
use think\App;
use think\facade\Db;
class Base extends BaseController {
    public function __construct(App $app)
    {
        parent::__construct($app);
    }
}