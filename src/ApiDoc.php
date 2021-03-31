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
        $diff = array_merge(array_diff(array_keys($headers), $header_arr));
//        if(!empty($diff)){
//            foreach ($diff as &$v) {
//                $v = '[header]'.$v;
//            }
//            $apiParams = array_merge($diff, $apiParams);
//        }

        $actionIds   = $controllerName.'_'.$actionName;
        $fileContent = self::fileContentReadHandle();
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
        $method = $_SERVER['REQUEST_METHOD'] ? $_SERVER['REQUEST_METHOD'] : 'GET';
        if(empty($apiDesc)){
            $apiDesc = self::langTranslate('empty_desc');
        }
        $writeContent = [
            'id'          => $actionIds,
            'controller'  => $controllerName,
            'action'      => $actionName,
            'method'      => $method,
            'header'      => $diff,
            'params'      => $apiParams,
            'desc'        => $apiDesc,
            'request_url' => $requestUrl
        ];
        self::fileContentWriteHandle($writeContent);
    }

    /**
     * 保存至API页面
     * @param $controllerName  string 控制器名称
     * @param $actionName      string 方法名称
     * @param $apiResult       array  数据
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
        $fileContent = self::fileContentReadHandle();

        if(!empty($fileContent) && isset($fileContent[$actionIds])) {
            $apiParams = $fileContent[$actionIds]['params'];
            $paramsInfo = "";

            // 判断参数数组
            if(count($apiParams) > 1){
                $keys = array_keys($apiParams);
                $values = array_values($apiParams);
                if ($this->judegSortArray($keys) && $this->numericArray($keys)) {
                    $apiParams = $values;
                } else {
                    $apiParams = $keys;
                }
            }else{
                if(is_numeric(key($apiParams))){
                    $apiParams[0] = reset($apiParams);
                }else{
                    $apiParams[0] = key($apiParams);
                }
            }
            if (!empty($apiParams)) {
                foreach ($apiParams as &$v) {
                    $desc = self::langTranslate(array_merge(array_filter(explode('-', $v)))[0]) ?: self::langTranslate('Empty');
                    $paramsInfo .= "|" . $v . "|" . gettype($v) . "|" . $desc . "|\n";
                }
            } else {
                $paramsInfo .= "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|\n";
            }

            $headerParams = $fileContent[$actionIds]['header'];
            $paramsInfob = "";
            if (!empty($headerParams)) {
                foreach ($headerParams as &$v) {
                    $desc = self::langTranslate(array_merge(array_filter(explode('-', $v)))[0]) ?: self::langTranslate('Empty');
                    $paramsInfob .= "|" . $v . "|" . gettype($v) . "|" . $desc . "|\n";
                }
            } else {
                $paramsInfob .= "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|\n";
            }
            $paramsMK = "\n**" . self::langTranslate('Simple Desc') . "：**\n- " . $fileContent[$actionIds]['desc'] . "\n\n**";
            $paramsMK .= self::langTranslate('Request Url') . "：**\n- ` " . $this->projectUrl . '/' . $fileContent[$actionIds]['request_url'] . " `\n\n**";
            $paramsMK .= self::langTranslate('Request Method') . "：**\n- " . $fileContent[$actionIds]['method'] . "\n\n**";
            $paramsMK .= "Header：**\n\n|";
            $paramsMK .= self::langTranslate('Param Name') . "|" . self::langTranslate('Type') . "|" . self::langTranslate('Desc') . "|\n";
            $paramsMK .= "|:----|:-----|-----|\n" . $paramsInfob . "**";
            $paramsMK .= self::langTranslate('Param') . "：**\n\n|";
            $paramsMK .= self::langTranslate('Param Name') . "|" . self::langTranslate('Type') . "|" . self::langTranslate('Desc') . "|\n";
            $paramsMK .= "|:----|:-----|-----|\n" . $paramsInfo . "**";
            $paramsMK .= self::langTranslate('Return Example') . "**\n";
            //多维数组处理
            $resultParamNameArr = self::resultArrayHandle($apiResult);
            $resultParamNameArr = self::resultArrayTransform($resultParamNameArr);
            // 替补解决多维数组中多参数的情况
            $resultParamNameArr = self::resultArrayFurtherHandle($resultParamNameArr);
            // 原始数组去重
            $apiResultMK = self::resultArrayHandle($apiResult, 1);
            // 转回数组格式
            $apiResultMK = self::toArrayHandle($apiResultMK);

            $resultMK = '';
            if (!empty($resultParamNameArr)) {
                foreach ($resultParamNameArr as &$v) {
                    if (count(array_merge(array_filter(explode('-', $v['param_name'])))) == 0) {
                        continue;
                    }
                    $resultMK .= "|" . $v['param_name'] . "|" . $v['param_type'] . "|" . self::langTranslate(array_merge(array_filter(explode('-', $v['param_name'])))[0]) . "|\n";
                }
            } else {
                $resultMK = "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|\n";
            }

            $paramsMK .= "```\n" . json_encode($apiResultMK, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n```\n**";
            $paramsMK .= self::langTranslate('Return Param Desc') . "**\n\n|";
            $paramsMK .= self::langTranslate('Param Name') . "|";
            $paramsMK .= self::langTranslate('Type') . "|";
            $paramsMK .= self::langTranslate('Desc') . "|\n|:-----|:-----|-----|\n" . $resultMK;

            if (!$mkExport) {
                $data = [
                    "api_key" => $this->apiKey,
                    "api_token" => $this->apiToken,
                    "cat_name" => $controllerName,
                    "page_title" => $fileContent[$actionIds]['desc'],
                    "page_content" => $paramsMK
                ];
                $this->doCurl($data, $this->apiUrl);
            } else {
                echo $paramsMK;
            }
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
     * @return array
     */
    private function fileContentReadHandle()
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
     * @param $content
     */
    private function fileContentWriteHandle($content)
    {
        $res = filesize(__DIR__.'/../apilogs.txt');
//        if($res / 1024 / 1024 > $this->fileMax){
//            echo 'The file size has exceeded the limit';
//        }
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
     * @param array $apiResult 原始数组
     * @param int $isKeep 是否保留值,0为否，1为是
     * @param int $index 数组层级
     * @return array
     */
    private function resultArrayHandle($apiResult, $isKeep = 0, $index = 0)
    {
        if(empty($apiResult)){
            return [];
        }
        $index += 1;
        $resultParamNameArr = [];
        foreach ($apiResult as $k => $v) {
            $resultParamNameArr[$k]['param_name'] = $k;
            $resultParamNameArr[$k]['param_type'] = gettype($v);
            if($isKeep){
                if(gettype($v) == 'array'){
                    $resultParamNameArr[$k]['param_value'] = '';
                }else{
                    $resultParamNameArr[$k]['param_value'] = $v;
                }
            }
            if(is_array($v)){
                if(count($v) == count($v,1)){
                    $resultParamNameArr[$k]['children'] = self::resultArrayHandle($v, $isKeep, $index);
                }elseif(is_numeric(key($v))){
                    $v = $v[key($v)];
                    $resultParamNameArr[$k]['children'] = self::resultArrayHandle($v, $isKeep, $index);
                }else{
                    $resultParamNameArr[$k]['children'] = self::resultArrayHandle($v, $isKeep, $index);
                }
                $resultParamNameArr[$k]['level'] = $index;
                $resultParamNameArr = array_values($resultParamNameArr);
            }
            $resultParamNameArr = array_values($resultParamNameArr);
        }

        $oneDim = [];
        $MultiDim = [];
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
                $MultiDim[] = $v;
            }else{
                $oneDim[] = $v;
            }
        }
        // 参数排序
        if($oneDim&&$MultiDim){
            $resultParamNameArr = array_merge($oneDim, $MultiDim);
        }elseif($oneDim){
            $resultParamNameArr = $oneDim;
        }else{
            $resultParamNameArr = $MultiDim;
        }

        return $resultParamNameArr;
    }

    /**
     * 数组转换
     * @param array $data
     * @return array
     */
    private function resultArrayTransform($data)
    {
        if(empty($data)){
            return [];
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

    /**
     * 数组进一步操作
     * @param array $data
     * @return array
     */
    private function resultArrayFurtherHandle($data)
    {
        if(empty($data)){
            return [];
        }

        $newArra = [];
        $newArrb = [];
        $result  = [];
        list($arr1, $arr2) = [array_column($data, 'param_name'), array_column($data, 'param_type')];
        list($arr1, $arr2) = [array_diff_assoc($arr1, array_unique($arr1)), array_diff_assoc($arr2, array_unique($arr2))];
        foreach($arr1 as $k => $v)
            if(array_key_exists($k, $arr2))
                $result[] = $v;
//                $result[] = $data[$k];

        foreach ($data as $k => $v) {
            if(in_array($v['param_name'], $result) && in_array($v['param_name'], $newArra)){
                continue;
            }else{
                array_push($newArrb, $v);
                array_push($newArra, $v['param_name']);
            }
        }
        return $newArrb;
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

    /**
     * 判断数组是否有序
     * @param array $array 数组
     * @return int 0为无序，1为有序
     */
    private function JudegSortArray($array) {
        $len = count($array);
        $flag = -1;
        // 判断数组可能为升序or逆序
        for ($firLoc = 0, $secLoc = 1; $secLoc < $len; $firLoc ++, $secLoc ++) {
            if ($array[$firLoc] < $array[$secLoc]) {
                $flag = 0;
                break;
            }
            if ($array[$firLoc] > $array[$secLoc]) {
                $flag = 1;
                break;
            }
        }

        if ($flag == -1) {
            return 0;
        }

        $temp = $flag;
        for($i = $secLoc; $i < $len - 1; $i ++) {
            if ($flag == 0) {
                if ($array [$i] <= $array [$i + 1]) {
                    continue;
                } else {
                    $flag = 1;
                    break;
                }
            }
            if ($flag == 1) {
                if ($array [$i] >= $array [$i + 1]) {
                    continue;
                } else {
                    $flag = 0;
                    break;
                }
            }
        }
        if ($flag != $temp) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * 转回数组
     * @param array $array
     * @return array $newArray
     */
    private function toArrayHandle($array)
    {
        $newArray = [];
        foreach ($array as $k => $v) {
            $v['param_name'] = str_replace('-', '', $v['param_name']);
            if($v['param_type'] != 'array'){
                $newArray[$v['param_name']] = $v['param_value'];
            }else{
                $newArray[$v['param_name']][] = self::toArrayHandle($v['children']);
            }
        }
        return $newArray;
    }

    /**
     * 判断数组
     * @param $array
     * @return int 0为无序，1为有序
     */
    private function numericArray($array)
    {
        $num = 0;
        $all = count($array);
        foreach ($array as &$v) {
            if(is_numeric($v)){
                $num += 1;
            }else{
                $num += 0;
            }
        }
        if($num != $all){
            return 0;
        }else{
            return 1;
        }
    }
}
