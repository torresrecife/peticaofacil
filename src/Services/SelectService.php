<?php

namespace App\Services;

use App\Repositories\SelectRepository;

class SelectService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new SelectRepository($db);
	}

	public function listDadosByInput($campoId)
	{
		return $this->repo->listDadosByInput($campoId);
	}

	public function listClientesByArea($areaId)
	{
		return $this->repo->listClientesByArea($areaId);
	}

	public function listRowsByTable($table, $where, $andClause, $orderByField)
	{
		return $this->repo->listRowsByTable($table, $where, $andClause, $orderByField);
	}
}
