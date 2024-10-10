<?php

namespace app\index\controller;
use app\BaseController;
use app\service\game\GamePlatformFactory;
use think\facade\Db;

class Game extends BaseController
{
    public function get_game()
    {
        $time = time();
        $headers = [
            'Content-Type: application/json;charset=UTF-8',
            'X-Atgame-Mchid:10287',
            'X-Atgame-Timestamp:'.$time,
            'X-Atgame-Sign:'.strtoupper(md5($time . '2A1E04420A772882EC846C95E9652FD8'))
        ];
        $row = $this->request('https://openapi-br.atgameapp.com/pub/api/game/loadlist', [], $headers);
        if($row['code'] == 0){
            $games = $row['data']['glist'];
            $insert = [];
            foreach ($games as $key => $value){
                $count = Db::name('game')->where('pid','=',10)->where('code', '=',$value['gameid'])->count();
                if($count > 0) continue;
                $insert[] = [
                    'pid' => 10,
                    'code' => $value['gameid'],
                    'name' => $value['name'],
                    'img' => $value['icon'],
                    'is_open' => 1,
                ];
            }
            if(!empty($insert)){
                Db::name('game')->insertAll($insert);
            }
        }
        echo '完成';
    }
    protected function request($uri, $params, $headers)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return ['status' => '500', 'msg' => curl_error($ch)];
        }

        curl_close($ch);
        return json_decode($response, true);
    }
    public function get_slot(){
        $plate = app('app\common\model\Plate')->where("code","=",'IslotGame')->find();
        if(empty($plate))
        {
            echo '没平台';
            exit;
        }
        $line = app('app\common\model\Line')
            ->where('pid',"=",$plate['id'])
            ->order('lid desc')
            ->find();   //线路
        if(empty($line))
        {
            echo '没线路';
            exit;
        }
        $platform = $plate['code'];
        $data = $new_game = $games = [];
        $game = app('app\common\model\Game')->where("pid","=",$plate['id'])->select()->toArray();
        $gameList = [];
        foreach($game as $val){
            $gameList[$val['name']] = $val;
        }
        $jackpot = $this->get_jackpot($platform,$line);
        $jackpotData = [];
        foreach($jackpot as $value){
            $mcIds = trim(explode(',',$value['mcIds']));
            foreach($mcIds as $v){
                $jackpotData[$v] = $value['jackpot'];
            }
        }
        print_r($jackpotData);
        $list = $this->get_slot_list($platform,$line);
        foreach($list as $value){
            unset($value['machineType']);
            $value['jackpot'] = $jackpotData[$value['gameName']] ?? 0;
            $data[] = $value;
            if(!isset($gameList[$value['gameName']]) && !isset($games[$value['gameName']])){
                $new_game[] = [
                    'pid' => $plate['id'],
                    'code' => $value['gameName'],
                    'name' => $value['gameName'],
                    'is_open' => 0,
                ];
                $games[$value['gameName']] = $value['gameName'];
            }
        }
        if($data){
            Db::execute('TRUNCATE TABLE cp_game_slot');
            Db::name('game_slot')->insertAll($data);
        }else{
            echo '没数据';
        }
        if($new_game){
            Db::name('game')->insertAll($new_game);
        }
        echo '完成';
    }
    public function get_slot_list($platform,$line,$page=1,$pageSize=10){
        $platformService = GamePlatformFactory::getPlatformService($platform, $line, []);
        $list = $platformService->get_game_list($page, $pageSize);
        $games = [];
        if(isset($list['data']['resultsList']) && $list['data']['resultsList']){
            $row = $this->get_slot_list($platform,$line,$page+1,$pageSize=10);
            $games = array_merge($list['data']['resultsList'],$row);
        }else{
            print_r($list);
        }
        return $games;
    }
    public function get_jackpot($platform,$line){
        $platformService = GamePlatformFactory::getPlatformService($platform, $line, []);
        $list = $platformService->get_jackpot_list();
        if(isset($list['data']['jackpotList']) && $list['data']['jackpotList']){
            return $list['data']['jackpotList'];
        }else{
            return [];
        }
    }
}
