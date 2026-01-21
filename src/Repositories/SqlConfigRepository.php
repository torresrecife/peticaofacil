<?php

namespace App\Repositories;

class SqlConfigRepository
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listAll()
	{
		$query = mysqli_query($this->db, "SELECT * from tp_config_db ORDER by id_db");
		if (!$query) {
			return array();
		}

		$rows = array();
		while ($row = mysqli_fetch_array($query)) {
			$rows[] = $row;
		}

		return $rows;
	}
}
