<?php

namespace App\Services;

use App\Repositories\HorizRepository;

class HorizService
{
	private $repo;

	public function __construct($db)
	{
		$this->repo = new HorizRepository($db);
	}

	public function buildOptions(array $data, $ddInput = null)
	{
		return $this->repo->buildOptions($data, $ddInput);
	}
}
