<?php

namespace Showapi;

class Http
{
    /**
     * 执行POST请求
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param int $timeout
     * @return string
     * @throws \InvalidArgumentException
     */
    public function post(string $url, array $data, array $headers = [], int $timeout = 30): string
    {
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        
        // 启用SSL验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        
        $result = curl_exec($curl);
        
        if (curl_errno($curl)) {
            throw new \InvalidArgumentException(curl_error($curl));
        }
        
        return $result;
    }
    
    /**
     * 获取请求方法
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
    
    /**
     * 获取请求头
     * @return array
     */
    public function getRequestHeaders(): array
    {
        $headers = [];
        
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        
        return $headers;
    }
}