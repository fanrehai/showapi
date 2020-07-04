<?php
require_once './ApiDoc.php';

use Showapi\ApiDoc;

$apiKey   = '7d3*******************134';
$apiToken = 'a59*******************110';
$apiUrl   = 'https://yourwebsite/server/index.php?s=/api/item/updateByApi';
$projectUrl = '*****';
$fileMax = '1';
$api      = new ApiDoc($apiKey, $apiToken, $apiUrl, $projectUrl, $fileMax);

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
// 保存至文件
$api->saveApiToLog( 'asd', 'bbbb', ['name', 'phone', 'password', 'city'] , '这还是一个测试接口', 'index/index');
// 保存至服务器
$api->saveApiToWeb('asd', 'asddddd', $data);
// 内容情况
//$api->saveApiClear();