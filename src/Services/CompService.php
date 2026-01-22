<?php

namespace App\Services;

class CompService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function fetchResult($tabela, $campo0, $idRef, $idVal)
	{
		$campo = explode("|_|", $campo0);

		$sel = " SELECT ";
		for ($q = 0; $q <= count($campo); $q++) {
			if ($campo[$q] != '') {
				$sel .= ($q > 0 ? (',' . $campo[$q]) : $campo[$q]);
			}
		}

		$sel .= " FROM $tabela";
		$sel .= " where ";
		$sel .= " $idRef = $idVal";
		$sel = str_replace("\\'", "'", $sel);

		$query = mysqli_query($this->db, $sel);
		if (!$query) {
			return '';
		}
		$while = mysqli_fetch_array($query);
		if (!$while) {
			return '';
		}

		$result = '';
		for ($i = 0; $i <= count($while); $i++) {
			$result .= $while[$i] ? ($while[$i] . '_|_') : "";
		}

		return $result;
	}

	public function fetchSingleValue($tabela, $campo0, $idRef, $idVal)
	{
		$sel = " SELECT " . $campo0;
		$sel .= " FROM " . $tabela;
		$sel .= " where ";
		$sel .= " " . $idRef . " = " . $idVal;
		$sel = str_replace("\\'", "'", $sel);

		$query = mysqli_query($this->db, $sel);
		if (!$query) {
			return '';
		}
		$while = mysqli_fetch_array($query);
		return $while[0] ?? '';
	}
}
