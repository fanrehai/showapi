<?php

namespace Showapi;

class Validation
{
    /**
     * 验证字符串类型
     * @param mixed $value
     * @param string $fieldName
     * @param bool $required
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function validateString($value, string $fieldName, bool $required = false): string
    {
        if ($required && empty($value)) {
            throw new \InvalidArgumentException("缺少必需的配置项: {$fieldName}");
        }
        
        if ($value !== null && !is_string($value)) {
            throw new \InvalidArgumentException("{$fieldName} 必须是字符串类型");
        }
        
        return $value ?? '';
    }
    
    /**
     * 验证整数类型
     * @param mixed $value
     * @param string $fieldName
     * @param int $default
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function validateInt($value, string $fieldName, int $default = 0): int
    {
        if ($value === null) {
            return $default;
        }
        
        if (!is_int($value)) {
            throw new \InvalidArgumentException("{$fieldName} 必须是整数类型");
        }
        
        return abs($value);
    }
}