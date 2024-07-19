<?php
return [
    // （选配）文档标题，显示在左上角与首页
    'title'              => '接口文档',
    // （选配）文档描述，显示在首页
    'desc'               => '',
    // （选配）是否启用Apidoc，默认true
    'enable'               => true,
    // （必须）设置文档的应用/版本
    'apps'           => [
        [
            // （必须）标题
            'title'=>'后台API',
            // （必须）控制器目录地址，也可以是数组来指定多个控制器目录，如：['app\demo\controller','app\test\controller']
            'path'=>'app\admin\controller',
            // （必须）唯一的key
            'key'=>'admin',
            // （选配）该应用的访问授权密码
            'password' => '',
            // （选配）当前应用全局参数
            'params'=>[
                // （选配）当前应用全局的请求Header
                'header'=>[],
                // （选配）当前应用全局的请求Query
                'query'=>[],
                // （选配）当前应用全局的请求Body
                'body'=>[],
            ],
            // （选配）当前应用全局响应体
            'responses'=>[
                // （选配）当前应用成功响应体
                'success'=>[
                    // 同全的请求成功响应体
                ],
                // （选配）当前应用异常响应体
                'error'=>[
                    // 同全的请求成功响应体
                ]
            ],
            // （选配）该应用是否不允许调试
            'notDebug'=>false,
            // （选配）该应用的接口调试时，使用指定host发起请求，通常用于多应用多域名时配置
            'host'=>'',
        ],
        [
            // （必须）标题
            'title'=>'前端API',
            // （必须）控制器目录地址，也可以是数组来指定多个控制器目录，如：['app\demo\controller','app\test\controller']
            'path'=>'app\api\controller',
            // （必须）唯一的key
            'key'=>'api',
            // （选配）该应用的访问授权密码
            'password' => '',
            'params'=>[
                // （选配）当前应用全局的请求Header
                'header'=>[
                    ['name'=>'Authorization','type'=>'string','require'=>true,'desc'=>'身份令牌Token'],
                    ['name'=>'cid','type'=>'int','require'=>true,'desc'=>'渠道ID']
                ],
                // （选配）当前应用全局的请求Query
                'query'=>[],
                // （选配）当前应用全局的请求Body
                'body'=>[],
            ],
        ],
        [
            // （必须）标题
            'title'=>'代理端API',
            // （必须）控制器目录地址，也可以是数组来指定多个控制器目录，如：['app\demo\controller','app\test\controller']
            'path'=>'app\agent\controller',
            // （必须）唯一的key
            'key'=>'api',
            // （选配）该应用的访问授权密码
            'password' => '',
            'params'=>[
                // （选配）当前应用全局的请求Header
                'header'=>[
                    ['name'=>'Authorization','type'=>'string','require'=>true,'desc'=>'身份令牌Token'],
                ],
                // （选配）当前应用全局的请求Query
                'query'=>[],
                // （选配）当前应用全局的请求Body
                'body'=>[],
            ],
        ]
    ],
    // （必须）指定通用注释定义的文件地址
    'definitions'        => "app\common\controller\Definitions",
    // （必须）自动生成url规则，当接口不添加@Apidoc\Url ("xxx")注解时，使用以下规则自动生成
    /*'auto_url' => [
        // 字母规则，lcfirst=首字母小写；ucfirst=首字母大写；
        'letter_rule' => "lcfirst",
        // url前缀
        'prefix'=>"",
    ],*/
    //  (选配) 是否自动注册路由
    'auto_register_routes'=>false,
    // （必须）缓存配置
    'cache'              => [
    // 是否开启缓存
    'enable' => false,
],
    // （必须）权限认证配置
    'auth'               => [
    // 是否启用密码验证
    'enable'     => false,
    // 全局访问密码
    'password'   => "",
    // 密码加密盐
    'secret_key' => "apidoc#hg_code",
    // 授权访问后的有效期
    'expire' => 24*60*60*360,
],
    // 全局参数
    'params'=>[
    // （选配）全局的请求Header
    'header'=>[
        // name=字段名，type=字段类型，require=是否必须，default=默认值，desc=字段描述
        ['name'=>'Authorization','type'=>'string','require'=>true,'desc'=>'身份令牌Token'],
    ],
    // （选配）全局的请求Query
    'query'=>[
        // 同上 header
    ],
    // （选配）全局的请求Body
    'body'=>[
        // 同上 header
    ],
],
    // 全局响应体
    'responses'=>[
        // 成功响应体
        'success'=>[
            ['name'=>'code','desc'=>'业务代码','type'=>'int','require'=>1],
            ['name'=>'message','desc'=>'业务信息','type'=>'string','require'=>1],
            //参数同上 headers；main=true来指定接口Returned参数挂载节点
            ['name'=>'data','desc'=>'业务数据','type'=>"array",'main'=>true,'require'=>1],
        ],
        // 异常响应体
        'error'=>[
            ['name'=>'code','desc'=>'业务代码','type'=>'int','require'=>1,'md'=>'/docs/HttpError.md'],
            ['name'=>'message','desc'=>'业务信息','type'=>'string','require'=>1],
        ]
    ],
    //（选配）全局事件
    'debug_events'=>[
    // 前置事件
    'before'=>[
        // event=事件方法名；name=事件名称；
    ],
    // 后置事件
    'after'=>[
        // 同上
    ]
],
    //（选配）默认作者
    'default_author'=>'',
    //（选配）默认请求类型
    'default_method'=>'GET',
    // （选配）允许跨域访问
    'allowCrossDomain'=>true,
     /**
      * （选配）解析时忽略带@注解的关键词，当注解中存在带@字符并且非Apidoc注解，如 @key test，此时Apidoc页面报类似以下错误时:
      * [Semantical Error] The annotation "@key" in method app\demo\controller\Base::index() was never imported. Did you maybe forget to add a "use" statement for this annotation?
      */
    'ignored_annitation'=>['key'],


     // （选配）数据库配置
    /*'database'=>[
        // 数据库表前缀
        'prefix'          => '',
        // 数据库编码，默认为utf8
        'charset'         =>  'utf8',
        // 数据库引擎，默认为 InnoDB
        'engine'          => 'InnoDB',
    ],*/
    // （选配）Markdown文档
    'docs'              => [
    // title=文档标题，path=.md文件地址，appKey=指定应用/版本，多级分组使用children嵌套
    ['title'=>'后端HTTP响应编码','path'=>'docs/admin','appKey'=>'admin',
        'children'=>[
            ['title'=>'code错误码说明','path'=>'docs/HttpError'],
        ]
    ],
    [
        'title'=>'接口HTTP响应编码',
        'path'=>'docs/HttpError.md','appKey'=>'api',
        'children'=>[
            ['title'=>'code错误码说明','path'=>'docs/HttpError'],
        ],
    ]
],
    // （选配）代码生成器配置 注意：是一个二维数组
    // （选配）代码模板
    // （选配）接口分享功能
    'share'=>[
    // 是否开启接口分享功能
    'enable'=>true,
    // 自定义接口分享操作，二维数组，每个配置为一个按钮操作
    'actions'=>[
        [
            // 操作名称
            'name'=>'下载json',
            // 点击时触发的方法
            'click'=>function($shareData,$apiData){
                // 自定义业务代码...

                // retrun 返回js执行脚本。可以用downloadFile("下载地址","名称")来执行文件下载。
                return 'downloadFile("/test.json","name");';
            }
        ]
    ]
],
    //自定义处理注解
    'parsesAnnotation'=>function($data){
    //...
        return $data;
    }
];
