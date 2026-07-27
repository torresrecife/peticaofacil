<?php

namespace App\Services;

use App\Repositories\CampoPadraoRepository;

class CampoPadraoService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new CampoPadraoRepository($db);
	}

	public function createForTipo($tipoId)
	{
		return $this->repo->createForTipo($tipoId);
	}
}
