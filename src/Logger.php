<?php

namespace Showapi;

class Logger
{
    /**
     * 日志路径
     * @var string
     */
    private string $logFilePath;

    /**
     * 日志分隔符
     * @var string
     */
    private string $separator = '//----------------------------------------------//';
    
    /**
     * 文件大小限制（字节）
     * @var int
     */
    private int $fileSizeLimit; // 默认1MB
    
    /**
     * Logger constructor.
     * @param string $logFilePath
     * @param int $fileSizeLimit
     */
    public function __construct(string $logFilePath = __DIR__ . '/../apilogs.txt', int $fileSizeLimit = 10 * 1024 * 1024)
    {
        $this->logFilePath = $logFilePath;
        $this->fileSizeLimit = $fileSizeLimit;
    }
    
    /**
     * 读取日志文件内容
     * @return array
     */
    public function read(): array
    {
        $apiLogs = fopen($this->logFilePath, 'a+');
        $str = "";
        $buffer = 1024;
        
        while (!feof($apiLogs)) {
            $str .= fread($apiLogs, $buffer);
        }
        
        fclose($apiLogs);
        
        $str = explode($this->separator, $str);
        $str = array_filter($str);
        
        if (!empty($str)) {
            foreach ($str as &$v) {
                $v = json_decode($v, true);
            }
            $str = array_column($str, NULL, 'id');
        }
        
        return $str;
    }
    
    /**
     * 写入日志内容
     * @param array $content
     * @return void
     */
    public function write(array $content): void
    {
        // 检查文件大小，如果超过限制则备份并清空
        $fileSize = filesize($this->logFilePath);
        if ($fileSize > $this->fileSizeLimit) {
            $this->backupAndClear();
        }
        
        // 写入新内容
        $apiLogs = fopen($this->logFilePath, 'a+');
        $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        
        fwrite($apiLogs, PHP_EOL . $content . PHP_EOL . $this->separator);
        fclose($apiLogs);
    }
    
    /**
     * 备份日志文件并清空原文件
     * @return void
     */
    private function backupAndClear(): void
    {
        // 创建带时间戳的备份文件名
        $backupPath = dirname($this->logFilePath) . '/apilogs_backup_' . date('YmdHis') . '.txt';
        
        // 复制文件内容到备份文件
        if (copy($this->logFilePath, $backupPath)) {
            // 清空原日志文件
            $this->clear();
        }
    }
    
    /**
     * 清空日志文件
     * @return void
     */
    public function clear(): void
    {
        $apiLogs = fopen($this->logFilePath, 'w+');
        fclose($apiLogs);
    }
}