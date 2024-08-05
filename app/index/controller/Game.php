<?php

namespace app\index\controller;
use app\BaseController;
use think\facade\Db;

class Game extends BaseController
{
    public function get_game()
    {
        $time = time();
        $headers = [
            'Content-Type: application/json;charset=UTF-8',
            'X-Atgame-Mchid:10060',
            'X-Atgame-Timestamp:'.$time,
            'X-Atgame-Sign:'.strtoupper(md5($time . '8CC6C6C2B08A12D5B86A91D0A7A94DA5'))
        ];
        $row = $this->request('https://openapi.windygame.net/demo/api/game/loadlist', [], $headers);
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
}
