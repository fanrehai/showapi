# showapi
这是一个根据代码返回结果自动生成文档的插件
```
$apiKey   = '7d3*******************134';
$apiToken = 'a59*******************110';
$apiUrl   = 'https://yourwebsite/server/index.php?s=/api/item/updateByApi';
$projectUrl = '*****';
$api      = new ApiDoc($apiKey, $apiToken, $apiUrl, $projectUrl);
```
这是调用前的基础配置，
apiKey 和 apiToken 可以在项目 -> 项目设置 -> 开放API 中获取，
apiUrl 这个是自动文档的网站地址，可以为私有部署服务器，也可以为showdoc官方网址([https://www.showdoc.cc/](https://www.showdoc.cc/))


$api->saveApiToLog( '控制器名称', '函数名称', ['参数名称', '参数名'] , '函数介绍');
saveApiToLog方法会把接口信息保存在文件中，等待下一次调用
用法：
```
$api->saveApiToLog( 'Login', 'login', ['name', 'password'] , '用户基本登录接口');
```

$api->saveApiToWeb('控制器名称', '函数名称', 返回的参数，数组的格式，是否直接输出(默认为false));
saveApiToWeb方法会把保存在文件中的接口转成markdown格式之后，上传至服务器保存或者直接输出markdown格式
用法：
```
$api->saveApiToWeb('Login', 'login', $data);
$api->saveApiToWeb('Login', 'login', $data, true);
```