<?php

namespace App\Repositories;

class CompRepository
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
		for ($q = 0; $q < count($campo); $q++) {
			if (isset($campo[$q]) && $campo[$q] !== '') {
				$sel .= ($q > 0 ? (',' . $campo[$q]) : $campo[$q]);
			}
		}

		$sel .= " FROM $tabela";
		$sel .= " where ";
		if (is_numeric($idVal)) {
			$sel .= " $idRef = $idVal";
		} else {
			$safeVal = mysqli_real_escape_string($this->db, (string) $idVal);
			$sel .= " $idRef = '" . $safeVal . "'";
		}
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
		for ($i = 0; $i < count($while); $i++) {
			if (isset($while[$i]) && $while[$i] !== '') {
				$result .= $while[$i] . '_|_';
			}
		}

		return $result;
	}

	public function fetchSingleValue($tabela, $campo0, $idRef, $idVal)
	{
		$sel = " SELECT " . $campo0;
		$sel .= " FROM " . $tabela;
		$sel .= " where ";
		if (is_numeric($idVal)) {
			$sel .= " " . $idRef . " = " . $idVal;
		} else {
			$safeVal = mysqli_real_escape_string($this->db, (string) $idVal);
			$sel .= " " . $idRef . " = '" . $safeVal . "'";
		}
		$sel = str_replace("\\'", "'", $sel);

		$query = mysqli_query($this->db, $sel);
		if (!$query) {
			return '';
		}
		$while = mysqli_fetch_array($query);
		return $while[0] ?? '';
	}
}
