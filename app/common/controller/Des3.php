<?php
namespace app\common\controller;
class Des3
{
    /*密钥,24个字符*/
    const KEY='xxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    /*向量，8个或10个字符*/
    const IV='00000000';

    public static function encryptText($data,$key){
        return self::encrypt($data,$key,true);
    }

    /**
     * 加密
     * @param boolean $status 是否加密
     * @return string 处理过的数据
     * Java语言的实现地址：
     * https://www.cnblogs.com/-ccj/p/10372497.html
     * https://blog.csdn.net/xiojing825/article/details/78491374
     */
    public static function encrypt($data,$key,$status=false){
        if ($status){
            //return base64_encode(openssl_encrypt($data, 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, self::IV));
            return urlencode(base64_encode(openssl_encrypt($data, 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, self::IV)));
        }
        return $data;
    }

    public static function decryptText($data,$key){
        return self::decrypt($data,$key,true);
    }
    /**
     * 解密
     * @return string 加密的字符串不是完整的会返回空字符串值
     */
    public static function decrypt($data,$key,$status=false){
        if ($status){
            return openssl_decrypt(base64_decode(urldecode($data)), 'des-ede3-cbc',$key, OPENSSL_RAW_DATA, self::IV);
        }
        return $data;
    }
}