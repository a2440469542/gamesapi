<?php
// 应用公共文件
use app\common\model\Config;
use think\facade\Log;
use think\facade\Db;

if (!function_exists('error')) {
    function error($msg, $code = 500, $data = [])
    {
        $res['code'] = $code;
        $res['msg'] = $msg;
        if ($data) $res['data'] = $data;

        return json($res);
    }
}
if (!function_exists('success')) {
    function success($msg, $data = [], $code = 0, $is_merge = false)
    {
        $res['code'] = $code;
        $res['msg'] = $msg;
        $res['data'] = [];
        if ($data) {
            if ($is_merge === true) $res = array_merge($res, $data);
            else $res['data'] = $data;
        }
        return json($res);
    }
}
/**
 * 获取随机数
 */
if (!function_exists('random')) {
    function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
}
/**
 * 递归分类
 */
if(!function_exists("auth")){
    function auth($menu,$pid){
        $data = [];
        $arr = [];
        foreach ($menu as $val){
            if(isset($val['pid']) && $val['pid']==$pid){
                $data = [
                    'id' => $val['id'],
                    'path' => $val['path'],
                    'name' => $val['name'],
                    'component' => $val['component'],
                    'pid' => $val['pid'],
                    'parentId' => $val['pid'],
                    'redirect' => $val['redirect'],
                    'sort' => $val['sort'],
                    'meta' => [
                        'title' => $val['title'],
                        'icon' => $val['icon'],
                        'breadcrumbHidden' => $val['breadcrumbHidden'],
                        'badge' => $val['badge'],
                        'dot' => $val['dot'],
                        'hidden' => $val['hidden'],
                        'levelHidden' => $val['levelHidden'],
                        'isCustomSvg' => $val['isCustomSvg'],
                        'noClosable' => $val['noClosable'],
                        'noKeepAlive' => $val['noKeepAlive'],
                        'tabHidden' => $val['tabHidden'],
                    ],
                ];
                $data['children'] = auth($menu,$val['id']);
                $arr[] = $data;
            }
        }
        return $arr;
    }
}
/**
 * 判断是否为手机号
 */
if(!function_exists('is_mobile')){
    function is_mobile($phone){
        if(preg_match("/^((13[0-9])|(15[^4])|(16[5,6])|(17[0-8])|(18[0-9])|(19[1,8-9])|(14[4,5,7])|(156))\d{8}$/",$phone)){
            return true;
        }else{
            return false;
        }
    }
}
if(!function_exists('isPhoneNumber')){
    function isPhoneNumber($phone){
        $pattern = '/^55([1-9][0-9])?9[0-9]{8}$/';
        return preg_match($pattern, $phone);
    }
}
/**
 * 获取随机字符串
 */
if(!function_exists('str_rand')) {
    function str_rand($len, $type = 0)
    {
        $chars = [
            'abcdefghijklmnopqrstuvwxyz0123456789',
            'abcdefghijklmnopqrstuvwxyz',
            '0123456789',
        ];

        $str = '';

        $char = &$chars[$type];
        $end = strlen($char) - 1;

        while ($len-- > 0) {
            $str .= $char[rand(0, $end)];
        }

        return $str;
    }
}
/**
 * 实例化模型
 */
if(!function_exists('model')){
    function model($model_name,$cid=null){
        $model = app($model_name);
        if($cid !== null){
            $model->setPartition($cid);
        }
        return $model;
    }
}
function get_inv_code($len = 6,$type = 0){
    $inv_code = str_rand($len,$type);
    if(Db::name('user')->where('inv_code',$inv_code)->count() > 0){
        return get_inv_code($len,$type);
    }
    return $inv_code;
}
/**
 * 格式化时间
 */
if(!function_exists('diff_time')) {
    function diff_time($time)
    {
        $day = 24*60*60;
        $now = time();
        $diff = $now-$time;
        if($diff<60){
            return $diff."秒前";
        }else if($diff>=60 && $diff<3600){
            return floor($diff/60)."分钟前";
        }else if($diff>=3600 && $diff<$day){
            return floor($diff/3600)."小时前";
        }else{
            return floor($diff/$day)."天前";
        }
    }
}
/**
 * 获取订单号
 */
if(!function_exists('getSn')){
    function getSn($prefix="HY"){
        return $prefix . date('ymdHis') . substr(microtime(), 2, 6) . rand(100000,999999);
    }
}
function replaceStr(string $str):string
{
    return substr($str,0,4).'***'.substr($str,-1,2);
}
function replaceStr2(string $str):string
{
    return mb_substr($str,0,4).'***';
}
function getMillisecond(){
    list($msec, $sec) = explode(' ', microtime());
    $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectimes = substr($msectime,0,13);
}
function curl_get_content($url, $conn_timeout=7, $timeout=15, $user_agent=null)
{
    $headers = array(
        "Accept: application/json",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Accept-Charset: utf-8;q=1"
    );
    if ($user_agent === null) {
        $user_agent = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.57 Safari/537.36';
    }
    $headers[] = $user_agent;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $conn_timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    $res = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_errno($ch);
    curl_close($ch);

    if (($err) || ($httpcode !== 200)) {
        Log::record($res,"info",[],true);
        Log::record("err=>{$err}===code=>{$httpcode}","info",[],true);
        return json_encode(['d'=>['code'=>2000]]);
    }
    return $res;
}
/**
 * CURL请求
 * @param $url string 请求url地址
 * @param $method string 请求方法 get post
 * @param mixed $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method="GET", $postfields = null, $headers = array(), $debug = false, $timeout=60)
{
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT,$timeout); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 10); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    }
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);

    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}
function write_log($data,$file_name){
    $years = date('Y');
    //设置路径目录信息
    $url = 'runtime/command/'.$years.'/'.date("m").'/'.date('d').'/'.$file_name.'.txt';
    $dir_name = dirname($url);

    //目录不存在就创建
    if(!file_exists($dir_name)){
        //iconv防止中文名乱码
        $res = @mkdir(iconv("UTF-8", "GBK", $dir_name),0777,true);
    }

    $fp = fopen($url,"a");//打开文件资源通道 不存在则自动创建
    fwrite($fp,date("Y-m-d H:i:s")."    ".var_export($data,true)."\r\n");//写入文件
    fclose($fp);//关闭资源通道
}
function vip_time($time,$type,$num){
    $now = time();
    if($time<$now){
        $time = time();
    }
    switch ($type){
        case 1:
            $time = $time + $num*24*60*60;
            break;
        case 2:
            $time = strtotime("+{$num} month",$time);
            break;
        case 3:
            $ji = $num * 3;
            $time = strtotime("+{$ji} month",$time);
            break;
        case 4:
            $time = strtotime("+{$num} year",$time);
            break;
    }
    return $time;
}
function get_real_ip__()
{
    $ip=false;
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
        for ($i = 0; $i < count($ips); $i++) {
            if (!preg_match("/^(10|172\.16|192\.168)\./", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

function get_ip_city($ip)
{
    $ch = curl_init();
    $url = 'https://whois.pconline.com.cn/ip.jsp?ip=' . $ip;
    //用curl发送接收数据
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //请求为https
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $location = curl_exec($ch);
    curl_close($ch);
    //转码
    $location = mb_convert_encoding($location, 'utf-8', 'GB2312');
    return $location; # 返回数组
}

/**
 * 获取网站配置信息
 */
function get_config(){
    $config = cache("config");
    if(empty($config)){
        $list = Db::name("config")->select();
        foreach ($list as $value){
            $config[$value["code"]] = $value["value"];
        }
        cache("config",$config);
    }
    return $config;
}
function get_extension($file)
{
    return substr(strrchr($file, '.'), 1);
}
/**
 * 图片对称加密
 *
 * @param [string] $filePath 图片路径
 * @return void
 */
function encImg($filePath)
{
    $filePath = ".".$filePath;
    // 文档中建议：为移植性考虑，强烈建议在用 fopen() 打开文件时总是使用 'b' 标记。
    $fileId = fopen($filePath, 'rb+');
    // 取出文件大小的字节数 （29124）
    $fileSize = fileSize($filePath);
    // 读取文件，返回所读取的字符串 （读出来的为二进制序列）
    $img = fread($fileId, $fileSize);
    // 使用“无符号字符”，从二进制字符串对数据进行解包
    // （pack、unpack用法）https://segmentfault.com/a/1190000008305573
    $imgUnpack = unpack('C*', $img); // $fileSize 长度的一维数组 [ 1=>255, 2=>216, 3=>255, ……, 29124=>217 ]
    // 关闭一个已打开的文件指针
    fclose($fileId);
    $tempArr = [];
    // 自定义加密规则
    for ($i = 1; $i <= $fileSize; $i++) {
        $value = 0;
        if ($i % 3 == 0) {
            $value = 2;
        } elseif ($i % 5 == 0) {
            $value = 4;
        } elseif ($i % 7 == 0) {
            $value = 6;
        }
        $byte = $imgUnpack[$i]; // 图片原始字节
        $byte = $byte + $value; // 经过加密规则之后的字节
        // 打包成二进制字符串
        $tempArr[] = pack('C*', $byte);
    }
    $img = implode('', $tempArr);   // 将解包之后的一维数组装换成字符串
    $ext = get_extension($filePath);
    $time = date("Ymd");
    $filename = strrchr($filePath, '/');
    $savePath = "/img/encrypt/".$time.$filename;
    $dir_name = dirname(".".$savePath);

    //目录不存在就创建
    if(!file_exists($dir_name)){
        //iconv防止中文名乱码
        $res = @mkdir(iconv("UTF-8", "GBK", $dir_name),0775,true);
    }
    file_put_contents(".".$savePath, $img); // 重写图片
    return $savePath;
}
/**

 * 图片对称解密

 *

 * @param [string] $filePath    图片路径

 * @return void

 */

function dec($filePath)

{

    $fileId = fopen($filePath, 'rb+');

    $fileSize = filesize($filePath);

    $img = fread($fileId, $fileSize);

    $imgUnpack = unpack('C*', $img);

    fclose($fileId);



    $tempArr = [];

    // 开始解密

    for ($i = 1; $i <= $fileSize; $i++) {

        $value = 0;

        if ($i % 3 == 0) {

            $value = 2;

        } elseif ($i % 5 == 0) {

            $value = 4;

        } elseif ($i % 7 == 0) {

            $value = 6;

        }

        $byte = $imgUnpack[$i];

        $byte = $byte - $value;

        $tempArr[] = pack('C*', $byte);

    }

    $img = implode('', $tempArr);

    file_put_contents($filePath, $img);

}
function formatNumber($number) {
    if ($number >= 10000) {
        return number_format($number / 10000, 1) . 'W';
    } elseif ($number >= 1000) {
        return number_format($number / 1000, 1) . 'K';
    } else {
        return $number;
    }
}
function notifier($chat_id, $salary, $money,$tip,$reward=0,$pid="",$pname="",$score=0)
{
    $data = [
        'chat_id' => $chat_id,
        'sarlary' => round($salary,2),
        'balance' => round($money,2),
        'reward'  => round($reward,2),
        'tip' => $tip,
        'pid' => $pid,
        'pname' => $pname,
        'score' => $score
    ];
    $config = get_config();
    $url = $config['qd_url']."/api/index/sendSalaryMsg";
    $json = json_encode($data);
    $row = httpRequest($url, "POST", $json, array('Content-Type: application/json'), false, 10);
    return $row;
}
function notifierCash($chat_id)
{
    $data = [
        'chat_id' => $chat_id,
        'command' => 'withdrawmoneyerror',
        'msg' => 'Falha na retirada,saldo atual'
    ];
    $config = get_config();
    $url = $config['qd_url']."/api/index/sendMessage";
    $json = json_encode($data);
    $row = httpRequest($url, "POST", $json, array('Content-Type: application/json'), false, 10);
    return $row;
}
function notifierRed($chat_id,$surplus)
{
    $data = [
        'chat_id' => $chat_id,
        'surplus' => $surplus
    ];
    $config = get_config();
    $url = $config['qd_url']."/api/index/red_wrap_return";
    $json = json_encode($data);
    $row = httpRequest($url, "POST", $json, array('Content-Type: application/json'), false, 10);
    return $row;
}
function notifierPro($chat_id,$pid,$pname)
{
    $data = [
        'pid' => $pid,
        'pidname'=>$pname
    ];
    if(is_array($chat_id))
    {
        $data['chat_ids'] = $chat_id;
    }else{
        $data['chat_id'] = $chat_id;
    }
    $config = get_config();
    $url = $config['qd_url']."/api/index/pushplatform";
    $json = json_encode($data);
    $row = httpRequest($url, "POST", $json, array('Content-Type: application/json'), false, 10);
    return $row;
}
function open_room($rid,$winnum,$winmoney,$list)
{
    $data = [
        'rid' => $rid,
        'winnum'=>$winnum,
        'winmoney' => $winmoney,
        'list' => $list
    ];
    $config = get_config();
    $url = $config['qd_url']."/api/room/roomresult";
    $json = json_encode($data);
    $row = httpRequest($url, "POST", $json, array('Content-Type: application/json'), false, 10);
    return $row;
}
/**
 * @param $totalAmount      float    总面额
 * @param $numOfRedPackets  int      红包个数
 * @return array
 */
function generateRedPackets($totalAmount, $numOfRedPackets) {
    $min = 0.01; // 每个红包的最小金额
    $redPackets = []; // 存储生成的每个红包金额

    for ($i = 0; $i < $numOfRedPackets; $i++) {
        // 确保至少每个红包有$min金额，且保证最后一个红包不会超出总金额
        if ($i == $numOfRedPackets - 1) {
            $money = round($totalAmount, 2);
        } else {
            $max = $totalAmount / ($numOfRedPackets - $i) * 2; // 确保金额分配的随机性
            $money = rand($min * 100, $max * 100) / 100;
            $totalAmount -= $money;
        }
        $redPackets[] = $money;
    }

    // 打乱数组，增加随机性
    shuffle($redPackets);
    return $redPackets;
}
function allocateRedPacket($total, $num) {
    $min = 0.01; // 每个红包的最小金额
    $redPackets = array(); // 初始化红包数组
    $remaining = $total - $num * $min; // 计算除去保证每人最小金额后的剩余金额
    for ($i = 0; $i < $num; $i++) {
        // 对于前n-1个红包，随机分配剩余金额的一部分
        if ($i < $num - 1) {
            // 剩余可分配金额的上限
            $max = $remaining / ($num - $i) * 2;
            $rand = mt_rand(0, $max * 100) / 100;
            $money = $min + $rand;
            $remaining -= $rand;
        } else {
            // 最后一个红包，直接分配所有剩余金额
            $money = $min + $remaining;
        }
        $redPackets[] = round($money, 2); // 保留两位小数并加入红包数组
    }

    // 可选：打乱数组，使分配结果看起来更随机
    shuffle($redPackets);

    return $redPackets;
}
function adjustString($str) {
    // 检查并移除字符串前面的"00"
    if (substr($str, 0, 2) === "00") {
        $str = substr($str, 2);
    }

    // 检查并在需要时在字符串前面添加"55"
    if (substr($str, 0, 2) !== "55") {
        $str = "55" . $str;
    }

    return $str;
}
function searchValueInMultiArray($search, $array) {
    $found = false;
    array_walk_recursive($array, function($value) use (&$found, $search) {
        if ($value === $search) {
            $found = true;
        }
    });
    return $found;
}