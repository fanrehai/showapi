<?php
# @Author: fanrehai
# @Date:   2019-11-28 14:33:52
# @Filename: ApiDoc.php
# @Last modified by:   fanrehai
# @Last modified time: 2020-06-22 20:42:30

namespace Showapi;

Class ApiDoc
{
    /**
     * 语言
     */
    private $lang;
    /**
     * Showdoc接口可以
     */
    private $apiKey;
    /**
     * Showdoc接口写入地址
     */
    private $apiUrl;
    /**
     * Showdoc接口token
     */
    private $apiToken;
    /**
     * 项目API访问地址
     */
    private $projectUrl;
    /**
     * 文件最大限制
     */
    private $fileMax;

    public function __construct($apiKey, $apiToken, $apiUrl, $projectUrl, $fileMax){
        $this->apiKey     = $apiKey;
        $this->apiUrl     = $apiUrl;
        $this->fileMax    = $fileMax;
        $this->apiToken   = $apiToken;
        $this->projectUrl = $projectUrl;
    }
    /**
     * 保存至日志文件
     * @param string $controllerName 控制器名称
     * @param string $actionName     方法名称
//     * @param string $method         传输方式 Get Post Put Delete Patch
     * @param array  $apiParams      接口需要的参数
     * @param string $apiDesc        接口描述
     * @param string $requestUrl     访问地址
     */
    public function saveApiToLog($controllerName, $actionName, $apiParams = [], $apiDesc = '', $requestUrl)
    {
        if(!is_string($controllerName) || !is_string($actionName)){
            throw new \InvalidArgumentException("The argument must be of string type");
        }
        if(!is_array($apiParams)){
            throw new \InvalidArgumentException("The parameter is not a valid array");
        }
        // 获取头部参数
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        $header_arr = ['Connection', 'Accept-Encoding', 'Host', 'Postman-Token', 'Cache-Control', 'Accept', 'User-Agent'];
        $diff = array_diff(array_keys($headers), $header_arr);
        if(!empty($diff)){
            foreach ($diff as &$v) {
                $v = '[header]'.$v;
            }
            $apiParams = array_merge($diff, $apiParams);
        }

        $actionIds   = $controllerName.'_'.$actionName;
        $fileContent = self::fileContentReadHandle($actionIds);
        $isHandle    = 0;
        foreach ($fileContent as &$v) {
            if(!isset($v['id'])){
                continue;
            }
            if($v['id'] == $actionIds && $v['params'] == $apiParams){
                $isHandle = 1;
                break;
            }
        }
        if($isHandle == 1){
            return ;
        }
        // 获取接收参数
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if(empty($apiDesc)){
            $apiDesc = self::langTranslate('empty_desc');
        }
        $writeContent = [
            'id'          => $actionIds,
            'controller'  => $controllerName,
            'action'      => $actionName,
            'method'      => $method,
            'params'      => $apiParams,
            'desc'        => $apiDesc,
            'request_url' => $requestUrl
        ];
        self::fileContentWriteHandle($writeContent);
    }

    /**
     * 保存至API页面
     * @param $mkExport bool 是否直接输出
     */
    public function saveApiToWeb($controllerName, $actionName, $apiResult, $mkExport = false){
        if(!is_string($controllerName) || !is_string($actionName)){
            throw new \InvalidArgumentException(self::langTranslate('The argument must be of string type'));
        }
        if(!is_array($apiResult) && is_null(@json_decode($apiResult, true))){
            throw new \InvalidArgumentException(self::langTranslate('The parameter is not a valid array or JSON data'));
        }
        if(!is_array($apiResult)){
            $apiResult = json_decode($apiResult, true);
        }
        $actionIds = $controllerName.'_'.$actionName;
        $fileContent = self::fileContentReadHandle($actionIds);

        if(empty($fileContent) || isset($fileContent[$actionIds])){
            throw new \InvalidArgumentException(self::langTranslate('Please call saveApiToLog method first'));
        }
        $apiParams = $fileContent[$actionIds]['params'];
        $paramsInfo = "";
        if(!empty($apiParams)){
            foreach ($apiParams as &$v) {
                $paramsInfo .= "|".$v."|".gettype($v)."|".self::langTranslate(array_merge(array_filter(explode('-', $v)))[0])."|\n";
            }
        }else{
            $paramsInfo .= "|".self::langTranslate('Empty')."|".self::langTranslate('Empty')."|".self::langTranslate('Empty')."|\n";
        }

        $paramsMK  = "\n**".self::langTranslate('Simple Desc')."：**\n- ".$fileContent[$actionIds]['desc']."\n\n**";
        $paramsMK .= self::langTranslate('Request Url')."：**\n- ` ".$this->projectUrl.'/'.$fileContent[$actionIds]['request_url']." `\n\n**";
        $paramsMK .= self::langTranslate('Request Method')."：**\n- ".$fileContent[$actionIds]['method']."\n\n**";
        $paramsMK .= self::langTranslate('Param')."：**\n\n|";
        $paramsMK .= self::langTranslate('Param Name')."|";
        $paramsMK .= self::langTranslate('Type')."|";
        $paramsMK .= self::langTranslate('Desc')."|\n";
        $paramsMK .= "|:----|:-----|-----|\n".$paramsInfo."**";
        $paramsMK .= self::langTranslate('Return Example')."**\n";

        $resultParamNameArr = self::resultArrayHandle($apiResult);
        $resultParamNameArr = self::resultArrayTransform($resultParamNameArr);

        $resultMK = '';
        if(!empty($resultParamNameArr)){
            foreach ($resultParamNameArr as &$v) {
                if(count(array_merge(array_filter(explode('-', $v['param_name'])))) == 0){
                    continue;
                }
                $resultMK .= "|".$v['param_name']."|".$v['param_type']."|".self::langTranslate(array_merge(array_filter(explode('-', $v['param_name'])))[0])."|\n";
            }
        }else{
            $resultMK = "|".self::langTranslate('Empty')."|".self::langTranslate('Empty')."|".self::langTranslate('Empty')."|\n";
        }

        $paramsMK .= "```\n".json_encode($apiResult, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n```\n**";
        $paramsMK .= self::langTranslate('Return Param Desc')."**\n\n|";
        $paramsMK .= self::langTranslate('Param Name')."|";
        $paramsMK .= self::langTranslate('Type')."|";
        $paramsMK .= self::langTranslate('Desc')."|\n|:-----|:-----|-----|\n".$resultMK;

        if(!$mkExport){
            $data = [
                "api_key"      => $this->apiKey,
                "api_token"    => $this->apiToken,
                "cat_name"     => $controllerName,
                "page_title"   => $actionName,
                "page_content" => $paramsMK
            ];
            $this->doCurl($data, $this->apiUrl);
        }else{
            echo $paramsMK;
        }
    }

    /**
     * 语音包翻译
     * @param string $langName 要翻译的名称
     */
    private function langTranslate($langName)
    {
        $langFile = require(__DIR__.'/lang/zh_cn.php');
        $nameKeys = array_keys($langFile);
        if(!in_array($langName, $nameKeys, true) && !isset($langFile[$langName])){
            return '';
        }
        return $langFile[$langName];
    }

    /**
     * 文件读取操作
     */
    private function fileContentReadHandle($actionIds)
    {
        $apiLogs = fopen(__DIR__.'/../apilogs.txt', 'a+');
        $str = "";
        //每次读取 1024 字节
        $buffer = 1024;
        //循环读取，直至读取完整个文件
        while(!feof($apiLogs)) {
            $str .= fread($apiLogs, $buffer);
        }
        fclose($apiLogs);
        $separator = '//----------------------------------------------//';
        $str = explode($separator, $str);
        $str = array_filter($str);

        if(!empty($str)){
            foreach ($str as &$v) {
                $v = json_decode($v, true);
            }
            $str = array_column($str, NULL, 'id');

//            $str_ids = array_column($str, 'id');
//            if(!in_array($actionIds, $str_ids)){
//                throw new \InvalidArgumentException('请先调用saveApiToLog方法');
//            }
        }

        return $str;
    }

    /**
     * 文件写入操作
     */
    private function fileContentWriteHandle($content)
    {
        $res = filesize(__DIR__.'/../apilogs.txt');
        if($res / 1024 / 1024 > $this->fileMax){
            echo 'The file size has exceeded the limit';
        }
        // $this->fileContentReadHandle();
        $apiLogs = fopen(__DIR__.'/../apilogs.txt', 'a+');
        $separator = '//----------------------------------------------//';
        $content = json_encode($content, JSON_UNESCAPED_UNICODE);

        fwrite($apiLogs,PHP_EOL.$content.PHP_EOL.$separator);
        fclose($apiLogs);

        return ;
    }

    /**
     * 文件清空
     */
    public function saveApiClear()
    {
        $apiLogs = fopen(__DIR__.'/../apilogs.txt', 'w+');
        fclose($apiLogs);
        return ;
    }

    /**
     * 数组操作
     */
    private function resultArrayHandle($apiResult, $index = 0)
    {
        if(empty($apiResult)){
            return false;
        }
        $index += 1;
        $resultParamNameArr = [];
        foreach ($apiResult as $k => $v) {
            $resultParamNameArr[$k]['param_name'] = $k;
            $resultParamNameArr[$k]['param_type'] = gettype($v);
            if(is_array($v)){
                if(count($v) == count($v,1)){
                    $resultParamNameArr[$k]['children'] = self::resultArrayHandle($v, $index);
                }elseif(is_numeric(key($v))){
                    $v = $v[key($v)];
                    $resultParamNameArr[$k]['children'] = self::resultArrayHandle($v, $index);
                }else{
                    $resultParamNameArr[$k]['children'] = self::resultArrayHandle($v, $index);
                }
                $resultParamNameArr[$k]['level'] = $index;
                $resultParamNameArr = array_values($resultParamNameArr);
            }
            $resultParamNameArr = array_values($resultParamNameArr);
        }

        foreach ($resultParamNameArr as &$v) {
            if(is_array($v) && count($v) == 1){
                $v = array_shift($v);
            }
            if(!empty($v['children'])){
                $level = '';
                for ($i = 0; $i < $v['level']; $i++) {
                    $level .= '-';
                }
                foreach ($v['children'] as &$v1) {
                    $v1['param_name'] = $level.$v1['param_name'];
                }
                unset($v['level']);
            }
        }
        return $resultParamNameArr;
    }

    /**
     * 数组转换
     */
    private function resultArrayTransform($data)
    {
        if(empty($data)){
            return false;
        }
        foreach ($data as &$v) {
            if(!empty($v['children'])){
                $place = array_search($v, $data);
                array_splice($data, $place + 1, 0, $v['children']);
                foreach ($v['children'] as &$v1) {
                    if(!empty($v1['children'])){
                        $v2[] = $v1;
                        $v1 = self::resultArrayTransform($v2);
                    }
                }
                unset($v['children']);
            }
        }
        foreach ($data as &$v) {
            if(count($v) != count($v,1)){
                $place = array_search($v, $data);
                unset($data[$place]);
                // 截取之后再填充
                $arrSliceStart = array_slice($data, 0, $place);
                $arrSliceEnd   = array_slice($data, $place);
                foreach ($v as &$v1) {
                    $arrSliceStart[$place] = $v1;
                    $place += 1;
                }
                $data = array_merge($arrSliceStart, $arrSliceEnd);
            }
        }
        return $data;
    }

    private function doCurl($data, $url){
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
        if (curl_errno($curl)) {
            throw new \InvalidArgumentException(curl_error($curl));
        }
        //关闭URL请求
        curl_close($curl);
    }
}
