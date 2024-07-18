<?php
declare(strict_types = 1);

namespace app\middleware;

use think\facade\Cache;
use think\facade\Db;

class Agent
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
                $agent = $this->authorize($request);
            }else{
                $agent = session('admin');
            }
            $request->id = $agent['id'];
            $request->mobile = $agent['mobile'];
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
            $agent = Cache::get($token);
            if (!$agent) {
                abort(401, '未登录...');
            }
            return $agent;
        } catch (\Throwable $e) {
            abort(401, $e->getMessage());
        }
    }
}