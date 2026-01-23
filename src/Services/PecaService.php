<?php

namespace App\Services;

use App\Repositories\PecaRepository;

class PecaService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new PecaRepository($db);
	}

	public function fetchList($tipoId, $limit, $search, $usuarioNivel, $usuarioId)
	{
		return $this->repo->fetchList($tipoId, $limit, $search, $usuarioNivel, $usuarioId);
	}

	public function listRecent($usuarioNivel, $usuarioId, $setorId, $clienteId, $limit = 10)
	{
		return $this->repo->listRecent($usuarioNivel, $usuarioId, $setorId, $clienteId, $limit);
	}

	public function listToday($usuarioNivel, $usuarioId, $setorId, $clienteId, $date = null)
	{
		return $this->repo->listToday($usuarioNivel, $usuarioId, $setorId, $clienteId, $date);
	}

	public function listTodayCounts($usuarioNivel, $usuarioId, $setorId, $clienteId, $date = null)
	{
		return $this->repo->listTodayCounts($usuarioNivel, $usuarioId, $setorId, $clienteId, $date);
	}

	public function getEditInfo($id)
	{
		return $this->repo->getEditInfo($id);
	}

	public function getById($id)
	{
		return $this->repo->getById($id);
	}

	public function findByCodSavOrId($codSav, $idPecas)
	{
		return $this->repo->findByCodSavOrId($codSav, $idPecas);
	}

	public function savePeca($tipoId, $idPecas, $nomePecas, $nomeCli, $texto, $codSav, $usuarioId)
	{
		return $this->repo->savePeca($tipoId, $idPecas, $nomePecas, $nomeCli, $texto, $codSav, $usuarioId);
	}
}
