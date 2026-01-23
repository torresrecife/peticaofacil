<?php

namespace App\Services;

use App\Repositories\CompRepository;

class CompService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new CompRepository($db);
	}

	public function fetchResult($tabela, $campo0, $idRef, $idVal)
	{
		return $this->repo->fetchResult($tabela, $campo0, $idRef, $idVal);
	}

	public function fetchSingleValue($tabela, $campo0, $idRef, $idVal)
	{
		return $this->repo->fetchSingleValue($tabela, $campo0, $idRef, $idVal);
	}
}
