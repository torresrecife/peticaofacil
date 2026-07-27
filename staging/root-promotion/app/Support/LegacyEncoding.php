<?php

namespace App\Support;

trait LegacyEncoding
{
    protected function legacyToUtf8($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        if (preg_match('//u', $value)) {
            return $value;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $value);
            if ($converted !== false) {
                return $converted;
            }
        }

        return utf8_encode($value);
    }

    protected function utf8ToLegacy($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $value);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    }

    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);

        if (property_exists($this, 'legacyUtf8Fields') && in_array($key, $this->legacyUtf8Fields, true)) {
            return $this->legacyToUtf8($value);
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if (property_exists($this, 'legacyUtf8Fields') && in_array($key, $this->legacyUtf8Fields, true)) {
            $value = $this->utf8ToLegacy($value);
        }

        return parent::setAttribute($key, $value);
    }
}
