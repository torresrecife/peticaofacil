<?php

namespace App\Services;

use App\Repositories\UsuarioRepository;

class UsuarioService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new UsuarioRepository($db);
	}

	public function getRow($id)
	{
		return $this->repo->getRow($id);
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

	public function updatePassword($id, $senha)
	{
		return $this->repo->updatePassword($id, $senha);
	}

	public function updateAcesso($id, $datetime)
	{
		return $this->repo->updateAcesso($id, $datetime);
	}
}
