<?php

namespace App\Services;

use App\Repositories\ParagrafoRepository;

class ParagrafoService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new ParagrafoRepository($db);
	}

	public function create($tipoId, $titulo)
	{
		return $this->repo->create($tipoId, $titulo);
	}

	public function updateText($fundId, $text)
	{
		return $this->repo->updateText($fundId, $text);
	}

	public function delete($fundId)
	{
		return $this->repo->delete($fundId);
	}

	public function listByTipoWithArquivo($tipoId)
	{
		return $this->repo->listByTipoWithArquivo($tipoId);
	}
}
