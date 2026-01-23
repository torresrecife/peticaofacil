<?php

namespace App\Services;

use App\Repositories\TipoRepository;

class TipoService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new TipoRepository($db);
	}

	public function create(array $data)
	{
		return $this->repo->create($data);
	}

	public function deleteTipo($tipoId)
	{
		return $this->repo->deleteTipo($tipoId);
	}

	public function updateCabec($tipoId, $texto)
	{
		return $this->repo->updateCabec($tipoId, $texto);
	}

	public function updateRodap($tipoId, $texto)
	{
		return $this->repo->updateRodap($tipoId, $texto);
	}

	public function getSetorCodeByTipo($tipoId)
	{
		return $this->repo->getSetorCodeByTipo($tipoId);
	}

	public function getTipoArquivoById($tipoId)
	{
		return $this->repo->getTipoArquivoById($tipoId);
	}

	public function getCabecRodapById($tipoId)
	{
		return $this->repo->getCabecRodapById($tipoId);
	}
}
