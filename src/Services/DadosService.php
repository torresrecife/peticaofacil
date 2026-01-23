<?php

namespace App\Services;

use App\Repositories\DadosRepository;

class DadosService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new DadosRepository($db);
	}

	public function listDadosMap()
	{
		return $this->repo->listDadosMap();
	}

	public function listByInput($inputId, $setorId = null)
	{
		return $this->repo->listByInput($inputId, $setorId);
	}
}
