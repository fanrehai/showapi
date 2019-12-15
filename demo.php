<?php
# @Author: fanrehai
# @Date:   2019-11-28 15:58:45
# @Filename: demo.php
# @Last modified by:   fanrehai
# @Last modified time: 2019-11-28 16:03:27
require_once './vendor/autoload.php';

use Showapi\ApiDoc;

$apiKey   = '7d337d34b91ea764e7004c906b6906da1377722134';
$apiToken = 'a5933feb713545b71239c5bd0e1e2cc1357245110';
$apiUrl   = 'https://showdoc.fanrehai.top/server/index.php?s=/api/item/updateByApi';
$projectUrl = 'www.baidu.com';
$api      = new ApiDoc($apiKey, $apiToken, $apiUrl, $projectUrl);

//$api->say();
//var_dump($api->apiKey);
$data = [
    "url"     => "https://qqe2.com",
    "name"    => "欢迎使用JSON在线解析编辑器",
    "array"   => [
        "JSON"   => "http://jsonlint.qqe2.com/",
        "Cron生成" => "http://cron.qqe2.com/",
        "JS加密解密" => "http://edit.qqe2.com/asdasd"
    ],
    "boolean" => true,
    "null"    => null,
    "number"  => 123,
    "object"  => [
        "ac" => [
            [
                "JSON1"  => "http://jsonlint.qqe2.com/",
                "Cron生成" => "http://cron.qqe2.com/",
                "JS加密解密" => "http://edit.qqe2.com/"
            ],
            [
                "JSON2"  => "http://jsonlint.qqe2.com/",
                "Cron生成" => "http://cron.qqe2.com/",
                "JS加密解密" => "http://edit.qqe2.com/"
            ]
        ],
        "c" => "d",
        "e" => "f"
    ]
];

//$data = '{"id":"asd_888","controller":"asd","action":"888","method":"GET","params":["name","phone"]}';

//$api->saveApiToLog( 'asd', 'asddddd', $data );
//$api->saveApiToLog( 'asd', '888', ['name', 'phone', 'asd'] );

$api->saveApiToWeb('asd', '888', $data);