<?php

namespace App\Services;

use App\Repositories\SetorRepository;

class SetorService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new SetorRepository($db);
	}

	public function insert(array $data)
	{
		return $this->repo->insert($data);
	}

	public function update($id, array $data)
	{
		return $this->repo->update($id, $data);
	}

	public function delete($id)
	{
		return $this->repo->delete($id);
	}

	public function listAll()
	{
		return $this->repo->listAll();
	}

	public function getRow($id)
	{
		return $this->repo->getRow($id);
	}
}
