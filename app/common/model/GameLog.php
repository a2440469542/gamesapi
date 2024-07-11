<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/1
 * Time: 20:46
 */
namespace app\common\model;
use app\admin\model\Base;
use hg\apidoc\annotation\Field;
use hg\apidoc\annotation\AddField;
use think\facade\Cache;
use think\facade\Db;

class GameLog extends Base
{
    protected $pk = 'uid';
    public function getAddTimeAttr($value): string
    {
        return date("Y-m-d H:i:s",$value);
    }

    /**
     * @param $cid          int     渠道ID
     * @param $uid          int     用户ID
     * @param $mobile       string  用户手机号
     * @param $bid          int     账变ID
     * @param $gid          int     游戏ID
     * @param $gname        int     游戏名称
     * @param $UpdateCredit float   输赢金额
     * @param $game_id      string  平台游戏ID
     * @param $Term         string  平台流水号
     * @param $Bet          float   下注金额
     * @param $Award        float   本局得分
     * @return mixed
     */
    public function add($cid,$uid,$mobile,$bid,$pid,$gid,$gname,$UpdateCredit,$game_id,$Term,$Bet,$Award): mixed
    {
        $data = [
            'cid' => $cid,
            'uid' => $uid,
            'mobile' => $mobile,
            'bid' => $bid,
            'pid' => $pid,
            'gid' => $gid,
            'name' => $gname,
            'win_lose' => $UpdateCredit,
            'game_id' => $game_id,
            'term' => $Term,
            'bet'  => $Bet,
            'award' => $Award,
            'add_time' => time()
        ];
        $this->setPartition($cid);
        return self::partition($this->partition)->insertGetId($data);
    }
    public function getList($where=[], $limit=10, $order='id desc'){
        $list = self::alias("gl")
            ->field("gl.*,u.inv_code")
            ->leftJoin("cp_user PARTITION({$this->partition}) `u`","gl.uid = u.uid")
            ->where($where)
            ->order($order)
            ->partition($this->partition)
            ->paginate($limit)->toArray();
        return $list;
    }
}