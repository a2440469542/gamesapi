<?php
declare(strict_types = 1);

namespace app\middleware;

use think\facade\Cache;
use think\facade\Db;

class Auth
{
    const NO_LOGIN_ACTIONS = ['index.index'];

    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $action = $this->getAction();
        if (!$this->isFreeAction($action)) {
            if($request->isPost()){
                $user = $this->authorize($request);
            }else{
                $user = session('admin');
            }
            $request->aid = $user['id'];
            $request->admin_name = $user['user_name'];
            $request->rid = $user['rid'];
        }
        return $next($request);
    }

    protected function getAction()
    {
        return strtolower(request()->controller() . '.' . request()->action());
    }
    protected function isFreeAction($action)
    {
        return in_array($action, self::NO_LOGIN_ACTIONS);
    }
    protected function authorize($request)
    {
        $token = $request->header('authorization');
        if (!$token) {
            abort(401, 'Token not provided');
        }
        try {
            $user = Cache::get($token);
            if (!$user) {
                abort(401, '未登录...');
            }else{
                if($user['rid'] > 1){
                    $controller = strtolower(request()->controller());
                    $action = strtolower(request()->action());
                    $menu = Db::name('menu')->where(['controllers'=>$controller,'methods'=>$action])->find();
                    if($menu){
                        $roles = Db::name('roles')->where(['rid'=>$user['rid']])->find();
                        print_r($roles['rule']);exit;
                        $rule = json_decode($roles['rule'],true);
                        if(!in_array($menu['id'],$roles['rule'])) {
                            return error('无权限访问',403);
                        }
                    }
                }
            }
            return $user;
        } catch (\Throwable $e) {
            abort(401, $e->getMessage());
        }
    }
}