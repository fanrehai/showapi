<?php
# @Author: fanrehai
# @Date:   2019-11-28 14:33:52
# @Filename: ApiDoc.php
# @Last modified by:   fanrehai
# @Last modified time: 2019-11-28 16:03:46

namespace Showapi;

Class ApiDoc
{
    public function __construct(){

    }
    /**
     * ShowAPI设置获取
     */
    public $apiKey;
    /**
     * ShowAPI设置获取
     */
    public $apiToken;

    public static function hello()
    {
        echo "hello world";
    }

    public function showdocApi($controllerName, $actionName, $info){
        $data = [
            "api_key"      => "dde6ce61cea704a9407d167f077d3144756972081",
            "api_token"    => "95e8b7b770ce5fdff34bb8a5fea8cc9b2053901276",
            "cat_name"     => $controllerName,
            "page_title"   => $actionName,
            "page_content" => $info
        ];
        doCurl($data, 'https://showdoc.fanrehai.top/server/index.php?s=/api/item/updateByApi');
    }

    public function doCurl($data, $url, $isReturn = 0){
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        // 关闭SSL验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        if(gettype($data) == 'string'){
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data),
                'X-AjaxPro-Method:ShowList',
                'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36'
            ]);
        }
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        //执行命令
        $result = curl_exec($curl);
        $errs   = curl_error($curl);
        Log::info($result);
        Log::info($errs);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        if($isReturn){
            return $result;
        }
    }
}
