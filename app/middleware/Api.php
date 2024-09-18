<?php
declare(strict_types = 1);

namespace app\middleware;

use think\facade\Cache;

class Api
{
    const NO_LOGIN_ACTIONS = ['index.index','index.ad','index.jack_pot','index.channel','index.config','game.plate','game.get_game_list','login.register','login.logout','login.user_login','login.get_code','login.get_email_code'];

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
            $user = $this->authorize($request);
            $request->user = $user;
            $request->uid = $user['uid'];
            $request->cid = $user['cid'];
        }else{
            $cid = $request->header("cid");
            $url = $request->post("url");
            $channel = model('app\common\model\Channel')->info($cid,$url);
            if (!$channel) {
                return error("O canal não existe",10001);//渠道不存在
            }
            if (!$channel) {
                return error("O ID do canal não pode ficar vazio",10001);//渠道ID不能为空
            }else{
                $request->cid = $cid;
            }
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
            if (empty($user) || !$user) {
                abort(401, 'Não logado...');//未登录
            }
            return $user;
        } catch (\Throwable $e) {
            abort(401, $e->getMessage());
        }
    }
}