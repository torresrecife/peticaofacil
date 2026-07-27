<?php

namespace App\Infra;

class Config
{
	public static function get($key, $default = null)
	{
		$value = getenv($key);
		return $value !== false ? $value : $default;
	}
}
