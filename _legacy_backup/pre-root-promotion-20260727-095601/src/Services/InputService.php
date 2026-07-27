<?php

namespace App\Services;

use App\Repositories\InputRepository;

class InputService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new InputRepository($db);
	}

	public function create(array $data)
	{
		return $this->repo->create($data);
	}

	public function update($campoId, array $data)
	{
		return $this->repo->update($campoId, $data);
	}

	public function deleteInput($id)
	{
		return $this->repo->deleteInput($id);
	}

	public function getInputRow($campoId)
	{
		return $this->repo->getInputRow($campoId);
	}

	public function listInputsByTipo($tipoId)
	{
		return $this->repo->listInputsByTipo($tipoId);
	}

	public function listFullByTipo($tipoId)
	{
		return $this->repo->listFullByTipo($tipoId);
	}

	public function getNextInputOrder($tipoId)
	{
		return $this->repo->getNextInputOrder($tipoId);
	}

	public function getMaxInputId()
	{
		return $this->repo->getMaxInputId();
	}

	public function getNextInputOrderForTipo($tipoId)
	{
		return $this->repo->getNextInputOrderForTipo($tipoId);
	}

	public function createListSelect(array $data)
	{
		return $this->repo->createListSelect($data);
	}
}
