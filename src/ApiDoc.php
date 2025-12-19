<?php
# @Author: fanrehai
# @Date:   2019-11-28 14:33:52
# @Filename: ApiDoc.php
# @Last modified by:   fanrehai
# @Last modified time: 2025-12-15 10:56:52

namespace Showapi;

use Showapi\Config;
use Showapi\Http;
use Showapi\Logger;
use Showapi\Validation;

class ApiDoc
{
    /**
     * 语言
     */
    private $lang;

    /**
     * 配置对象
     * @var Config
     */
    private Config $config;

    /**
     * 日志对象
     * @var Logger
     */
    private Logger $logger;

    /**
     * HTTP请求对象
     * @var Http
     */
    private Http $http;

    /**
     * ApiDoc constructor.
     * @param array $configArray 配置数组
     * @throws \InvalidArgumentException 如果缺少必需的配置项
     */
    public function __construct(array $configArray)
    {
        $apiKey = $configArray['api_key'] ?? null;
        $apiToken = $configArray['api_token'] ?? null;
        $apiUrl = $configArray['api_url'] ?? null;
        $projectUrl = $configArray['project_url'] ?? null;
        $fileMax = $configArray['file_max'] ?? null;

        // 使用验证类验证配置项
        $apiKey = Validation::validateString($apiKey, 'api_key', true);
        $apiToken = Validation::validateString($apiToken, 'api_token', true);
        $apiUrl = Validation::validateString($apiUrl, 'api_url', true);
        $projectUrl = Validation::validateString($projectUrl, 'project_url');
        $fileMax = Validation::validateInt($fileMax, 'file_max', 10);

        // 创建Config实例
        $this->config = new Config($apiKey, $apiToken, $apiUrl, $projectUrl, $fileMax);

        // 将fileMax从MB转换为字节单位
        $fileSizeLimit = $this->config->getFileMax() * 1024 * 1024;
        $this->logger = new Logger(__DIR__ . '/../apilogs.txt', $fileSizeLimit);
        $this->http = new Http();
    }

    /**
     * 保存至日志文件
     * @param string $cateName 类目名称
     * @param string $actionName     方法名称
     * @param array  $apiParams      接口需要的参数
     * @param string $apiDesc        接口名称
     * @param string $requestUrl     访问地址
     */
    public function saveApiToLog(string $cateName, string $actionName, array $apiParams = [], string $apiDesc = '', string $requestUrl): void
    {
        if (!is_string($cateName) || !is_string($actionName)) {
            throw new \InvalidArgumentException("The argument must be of string type");
        }
        if (!is_array($apiParams)) {
            throw new \InvalidArgumentException("The parameter is not a valid array");
        }
        // 获取头部参数
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        $header_arr = ['Connection', 'Accept-Encoding', 'Host', 'Postman-Token', 'Cache-Control', 'Accept', 'User-Agent'];
        $diff = array_merge(array_diff(array_keys($headers), $header_arr));

        $actionIds   = $cateName . '_' . $actionName;
        $fileContent = $this->logger->read();
        $isHandle    = 0;
        foreach ($fileContent as &$v) {
            if (!isset($v['id'])) {
                continue;
            }
            if ($v['id'] == $actionIds && $v['params'] == $apiParams) {
                $isHandle = 1;
                break;
            }
        }
        if ($isHandle == 1) {
            return;
        }
        // 获取接收参数
        $method = $this->http->getRequestMethod();
        if (empty($apiDesc)) {
            $apiDesc = self::langTranslate('empty_desc');
        }
        $writeContent = [
            'id'          => $actionIds,
            'controller'  => $cateName,
            'action'      => $actionName,
            'method'      => $method,
            'header'      => $diff,
            'params'      => $apiParams,
            'desc'        => $apiDesc,
            'request_url' => $requestUrl
        ];
        $this->logger->write($writeContent);
    }

    /**
     * 保存至API页面
     * @param $cateName  string 类目名称    
     * @param $actionName      string 方法名称
     * @param $apiResult       array  数据
     * @param $mkExport bool 是否直接输出
     */
    public function saveApiToWeb(string $cateName, string $actionName, $apiResult, bool $mkExport = false): void
    {
        if (!is_string($cateName) || !is_string($actionName)) {
            throw new \InvalidArgumentException(self::langTranslate('The argument must be of string type'));
        }
        if (!is_array($apiResult) && is_null(@json_decode($apiResult, true))) {
            throw new \InvalidArgumentException(self::langTranslate('The parameter is not a valid array or JSON data'));
        }
        if (!is_array($apiResult)) {
            $apiResult = json_decode($apiResult, true);
        }
        $actionIds = $cateName . '_' . $actionName;
        $fileContent = $this->logger->read();
        var_dump($fileContent, $actionIds);
        if (!empty($fileContent) && isset($fileContent[$actionIds])) {
            $apiParams = $fileContent[$actionIds]['params'];
            $tmpApiParams = "";

            // 判断参数数组
            if (count($apiParams) > 1) {
                $keys = array_keys($apiParams);
                $values = array_values($apiParams);
                if ($this->judgeSortArray($keys) && $this->numericArray($keys)) {
                    $apiParams = $values;
                } else {
                    $apiParams = $keys;
                }
            }
            if (count($apiParams) == 1) {
                if (is_numeric(key($apiParams))) {
                    $apiParams[0] = reset($apiParams);
                } else {
                    $apiParams[0] = key($apiParams);
                }
            }
            if (!empty($apiParams)) {
                foreach ($apiParams as &$v) {
                    $desc = self::langTranslate(array_merge(array_filter(explode('-', $v)))[0]) ?: self::langTranslate('Empty');
                    $tmpApiParams .= "|" . $v . "|" . gettype($v) . "|" . $desc . "|\n";
                }
            } else {
                $tmpApiParams .= "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|\n";
            }

            $headerParams = $fileContent[$actionIds]['header'];
            $tmpHeaderParams = "";
            if (!empty($headerParams)) {
                foreach ($headerParams as &$v) {
                    $desc = self::langTranslate(array_merge(array_filter(explode('-', $v)))[0]) ?: self::langTranslate('Empty');
                    $tmpHeaderParams .= "|" . $v . "|" . gettype($v) . "|" . $desc . "|\n";
                }
            } else {
                $tmpHeaderParams .= "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|" . self::langTranslate('Empty') . "|\n";
            }
            $paramsMK = "\n**" . self::langTranslate('Simple Desc') . "：**\n- " . $fileContent[$actionIds]['desc'] . "\n\n**";
            $paramsMK .= self::langTranslate('Request Url') . "：**\n- ` " . $this->config->getProjectUrl() . '/' . $fileContent[$actionIds]['request_url'] . " `\n\n**";
            $paramsMK .= self::langTranslate('Request Method') . "：**\n- " . $fileContent[$actionIds]['method'] . "\n\n**";
            $paramsMK .= "Header：**\n\n|";
            $paramsMK .= self::langTranslate('Param Name') . "|" . self::langTranslate('Type') . "|" . self::langTranslate('Desc') . "|\n";
            $paramsMK .= "|:----|:-----|-----|\n" . $tmpHeaderParams . "**";
            $paramsMK .= self::langTranslate('Param') . "：**\n\n|";
            $paramsMK .= self::langTranslate('Param Name') . "|" . self::langTranslate('Type') . "|" . self::langTranslate('Desc') . "|\n";
            $paramsMK .= "|:----|:-----|-----|\n" . $tmpApiParams . "**";
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
                    "api_key" => $this->config->getApiKey(),
                    "api_token" => $this->config->getApiToken(),
                    "cat_name" => $cateName,
                    "page_title" => $fileContent[$actionIds]['desc'],
                    "page_content" => $paramsMK
                ];
                try {
                    $this->http->post($this->config->getApiUrl(), $data);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            } else {
                echo $paramsMK;
            }
        }
    }

    /**
     * 语音包翻译
     * @param string $langName 要翻译的名称
     */
    private function langTranslate(string $langName): string
    {
        $langFile = require(__DIR__ . '/lang/zh_cn.php');
        $nameKeys = array_keys($langFile);
        if (!in_array($langName, $nameKeys, true) && !isset($langFile[$langName])) {
            return '';
        }
        return $langFile[$langName];
    }

    /**
     * 文件清空
     */
    public function saveApiClear(): void
    {
        $this->logger->clear();
    }

    /**
     * 数组操作
     * @param array $apiResult 原始数组
     * @param int $isKeep 是否保留值,0为否，1为是
     * @param int $index 数组层级
     * @return array
     */
    private function resultArrayHandle(array $apiResult, int $isKeep = 0, int $index = 0): array
    {
        if (empty($apiResult)) {
            return [];
        }
        $index += 1;
        $resultParamNameArr = [];
        foreach ($apiResult as $k => $v) {
            $resultParamNameArr[$k]['param_name'] = $k;
            $resultParamNameArr[$k]['param_type'] = gettype($v);
            if ($isKeep) {
                if (gettype($v) == 'array') {
                    $resultParamNameArr[$k]['param_value'] = '';
                } else {
                    $resultParamNameArr[$k]['param_value'] = $v;
                }
            }
            if (is_array($v)) {
                if (count($v) == count($v, 1)) {
                    $resultParamNameArr[$k]['children'] = self::resultArrayHandle($v, $isKeep, $index);
                } elseif (is_numeric(key($v))) {
                    $v = $v[key($v)];
                    $resultParamNameArr[$k]['children'] = self::resultArrayHandle($v, $isKeep, $index);
                } else {
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
            if (is_array($v) && count($v) == 1) {
                $v = array_shift($v);
            }
            if (!empty($v['children'])) {
                $level = '';
                for ($i = 0; $i < $v['level']; $i++) {
                    $level .= '-';
                }
                foreach ($v['children'] as &$v1) {
                    $v1['param_name'] = $level . $v1['param_name'];
                }
                unset($v['level']);
                $MultiDim[] = $v;
            } else {
                $oneDim[] = $v;
            }
        }
        // 参数排序
        if ($oneDim && $MultiDim) {
            $resultParamNameArr = array_merge($oneDim, $MultiDim);
        } elseif ($oneDim) {
            $resultParamNameArr = $oneDim;
        } else {
            $resultParamNameArr = $MultiDim;
        }

        return $resultParamNameArr;
    }

    /**
     * 数组转换
     * @param array $data
     * @return array
     */
    private function resultArrayTransform(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        foreach ($data as $item) {
            $current = $item;

            if (isset($current['children']) && !empty($current['children'])) {
                // 处理children数组
                $children = $current['children'];
                unset($current['children']);

                $result[] = $current;

                // 递归转换children
                $transformedChildren = $this->resultArrayTransform($children);
                $result = array_merge($result, $transformedChildren);
            } else {
                $result[] = $current;
            }
        }

        return $result;
    }

    /**
     * 数组进一步操作
     * @param array $data
     * @return array
     */
    private function resultArrayFurtherHandle(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $seen = [];
        $result = [];

        foreach ($data as $item) {
            $paramName = $item['param_name'];

            // 如果参数名未被处理过，添加到结果中
            if (!in_array($paramName, $seen)) {
                $result[] = $item;
                $seen[] = $paramName;
            }
        }

        return $result;
    }

    /**
     * 判断数组是否有序
     * @param array $array 数组
     * @return int 0为无序，1为有序
     */
    private function judgeSortArray(array $array): int
    {
        $len = count($array);
        $flag = -1;
        // 判断数组可能为升序or逆序
        for ($firLoc = 0, $secLoc = 1; $secLoc < $len; $firLoc++, $secLoc++) {
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
        for ($i = $secLoc; $i < $len - 1; $i++) {
            if ($flag == 0) {
                if ($array[$i] <= $array[$i + 1]) {
                    continue;
                } else {
                    $flag = 1;
                    break;
                }
            }
            if ($flag == 1) {
                if ($array[$i] >= $array[$i + 1]) {
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
     * @return array
     */
    private function toArrayHandle(array $array): array
    {
        $result = [];

        foreach ($array as $item) {
            $paramName = str_replace('-', '', $item['param_name']);

            if ($item['param_type'] !== 'array') {
                $result[$paramName] = $item['param_value'];
            } else {
                // 如果是数组类型，递归处理
                $result[$paramName][] = $this->toArrayHandle($item['children']);
            }
        }

        return $result;
    }

    /**
     * 判断数组
     * @param $array
     * @return int 0为无序，1为有序
     */
    private function numericArray(array $array): int
    {
        $num = 0;
        $all = count($array);
        foreach ($array as &$v) {
            if (is_numeric($v)) {
                $num += 1;
            } else {
                $num += 0;
            }
        }
        if ($num != $all) {
            return 0;
        } else {
            return 1;
        }
    }
}
