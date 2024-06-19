<?php
namespace app\api\controller;

use hg\apidoc\annotation as Apidoc;
use think\App;
use think\facade\Request;
use app\service\game\GamePlatformFactory;

/**
 * 调用三方游戏登录相关接口
 * @Apidoc\Title("调用三方游戏登录相关接口")
 * @Apidoc\Group("base")
 * @Apidoc\Sort(4)
 */
class GameLogin extends Base
{
    protected $platformService;
    protected $user;
    protected $game;
    protected function set_config()
    {
        $cid = $this->request->cid;
        $uid = $this->request->uid;
        $gid = Request::post('gid'); // 从前端获取游戏类型
        if (empty($gid)) {
            return error("Erro de parâmetro", 500);  //参数错误
        }
        $this->user = model('app\common\model\User',$cid)->getInfo($uid);
        $game = model('app\common\model\Game')->find($gid);
        if($this->user['is_rebot'] == 1){
            $plate = model('app\common\model\Plate')->where('is_rebot','=',1)->find();
        }else{
            $channel = model('app\common\model\Channel')->info($cid);
            $plate = model('app\common\model\Plate')->find($channel['pg_id']);
        }
        $this->game = $game->toArray();
        $platform = $plate['code'];

        $this->platformService = GamePlatformFactory::getPlatformService($platform, $plate, $this->user);
        return true;
    }

    protected function registerUser()
    {
        $channel = model('app\common\model\Channel')->where("cid", $this->user['cid'])->find();
        $user = $this->user;
        $user['cname'] = $channel['name'];  // 渠道名称

        return $this->platformService->registerUser($user);
    }

    /**
     * @Apidoc\Title("获取游戏启动链接")
     * @Apidoc\Desc("获取游戏启动链接")
     * @Apidoc\Method("POST")
     * @Apidoc\Author("")
     * @Apidoc\Tag("获取游戏启动链接")
     * @Apidoc\Param("gid", type="int", require=true, desc="游戏ID")
     * @Apidoc\Returned("url", type="object", desc="游戏启动链接")
     */
    public function get_game_url()
    {
        $row = $this->set_config();
        if($row !== true) {
            return $row;
        }
        $token = $this->registerUser();

        if ($token['code'] != 0) {
            return error($token['msg'], 501);    // 游戏登录失败
        }
        $this->user['user_token'] = $token['token'];
        $response = $this->platformService->getGameUrl($this->user,$this->game);

        if ($response['code'] != 0) {
            return error($response['msg'], 501);    // 游戏登录失败
        }

        return success("obter sucesso", $response); //获取成功
    }
}