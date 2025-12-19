<?php
# @Author: fanrehai
# @Date:   2019-11-28 14:33:52
# @Filename: ApiDoc.php
# @Last modified by:   fanrehai
# @Last modified time: 2025-12-15 10:56:52

namespace Showapi;

class Config
{
    /**
     * Showdoc接口key
     * @var string
     */
    private string $apiKey;

    /**
     * Showdoc接口写入地址
     * @var string
     */
    private string $apiUrl;

    /**
     * Showdoc接口token
     * @var string
     */
    private string $apiToken;

    /**
     * 项目API访问地址
     * @var string
     */
    private string $projectUrl;
    
    /**
     * 文件最大限制
     * @var int
     */
    private int $fileMax;
    
    /**
     * Config constructor.
     * @param string $apiKey
     * @param string $apiToken
     * @param string $apiUrl
     * @param string $projectUrl
     * @param int $fileMax
     */
    public function __construct(string $apiKey, string $apiToken, string $apiUrl, string $projectUrl, int $fileMax)
    {
        $this->apiKey = $apiKey;
        $this->apiToken = $apiToken;
        $this->apiUrl = $apiUrl;
        $this->projectUrl = $projectUrl;
        $this->fileMax = $fileMax;
    }
    
    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }
    
    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }
    
    /**
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->apiToken;
    }
    
    /**
     * @return string
     */
    public function getProjectUrl(): string
    {
        return $this->projectUrl;
    }
    
    /**
     * @return int
     */
    public function getFileMax(): int
    {
        return $this->fileMax;
    }
}