<?php

namespace App\Infra;

class Database
{
	private static $mysql;

	public static function mysql()
	{
		if (self::$mysql instanceof \mysqli) {
			return self::$mysql;
		}

		$host = getenv('DB_HOST') ?: '127.0.0.1';
		$user = getenv('DB_USER') ?: 'root';
		$pass = getenv('DB_PASS') ?: '';
		$name = getenv('DB_NAME') ?: '';
		$port = (int) (getenv('DB_PORT') ?: 3306);

		$mysqli = mysqli_connect($host, $user, $pass, $name, $port);
		if (!$mysqli) {
			die("MySQL: Não foi possível conectar-se ao servidor [{$host}].");
		}

		$charset = getenv('DB_CHARSET') ?: 'latin1';
		@mysqli_set_charset($mysqli, $charset);

		self::$mysql = $mysqli;
		return self::$mysql;
	}

	public static function sqlsrv(array $config)
	{
		$server = $config['server'] ?? null;
		$user = $config['user'] ?? null;
		$pass = $config['password'] ?? null;
		$db = $config['database'] ?? null;

		if (!$server) {
			return null;
		}

		$connectionInfo = array(
			'UID' => $user,
			'PWD' => $pass,
			'Database' => $db
		);

		return sqlsrv_connect($server, $connectionInfo);
	}
}
