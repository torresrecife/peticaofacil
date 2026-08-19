<?php

namespace App\Support;

trait DatabaseEncoding
{
    protected function toUtf8($value)
    {
        if (!is_string($value) || $value === '' || preg_match('//u', $value)) {
            return $value;
        }

        $converted = function_exists('iconv') ? @iconv('ISO-8859-1', 'UTF-8//IGNORE', $value) : false;

        return $converted !== false ? $converted : utf8_encode($value);
    }

    protected function fromUtf8($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $converted = function_exists('iconv') ? @iconv('UTF-8', 'ISO-8859-1//IGNORE', $value) : false;

        return $converted !== false ? $converted : $value;
    }

    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);

        return property_exists($this, 'databaseEncodedFields') && in_array($key, $this->databaseEncodedFields, true)
            ? $this->toUtf8($value)
            : $value;
    }

    public function setAttribute($key, $value)
    {
        if (property_exists($this, 'databaseEncodedFields') && in_array($key, $this->databaseEncodedFields, true)) {
            $value = $this->fromUtf8($value);
        }

        return parent::setAttribute($key, $value);
    }
}
