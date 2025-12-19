<?php

// 加载所有必需的类文件
require_once __DIR__ . '/Validation.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Http.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/ApiDoc.php';

use Showapi\ApiDoc;

$apiKey   = '7d3*******************134';
$apiToken = 'a59*******************110';
$apiUrl   = 'https://部署域名/server/index.php?s=/api/open/updatePage';
$projectUrl = 'http://项目域名/';
$fileMax = 5;

// 使用数组方式初始化ApiDoc
$configArray = [
    'api_key' => $apiKey,
    'api_token' => $apiToken,
    'api_url' => $apiUrl,
    'project_url' => $projectUrl,
    'file_max' => $fileMax
];
$api = new ApiDoc($configArray);

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
$api->saveApiToLog('类目', '2222222', ['name', 'phone', 'password', 'city'] , '这还是一个测试接口', 'index/index');
// 保存至服务器
$api->saveApiToWeb('类目', '2222222', $data);
// 内容清空
//$api->saveApiClear();