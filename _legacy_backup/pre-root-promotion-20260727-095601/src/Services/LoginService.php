<?php

namespace App\Services;

use App\Repositories\UsuarioAuthRepository;

class LoginService
{
	private $repo;
	private $caseSensitive;
	private $alwaysValidate;

	public function __construct(UsuarioAuthRepository $repo, $caseSensitive = false, $alwaysValidate = true)
	{
		$this->repo = $repo;
		$this->caseSensitive = $caseSensitive;
		$this->alwaysValidate = $alwaysValidate;
	}

	public function authenticate($usuario, $senhaHash)
	{
		$resultado = $this->repo->findByLoginAndSenha($usuario, $senhaHash, $this->caseSensitive);
		if (empty($resultado)) {
			return false;
		}

		$_SESSION['usuarioID'] 	  	= $resultado['id_usu']; 
		$_SESSION['usuarioNome']  	= $resultado['nome_usu']; 
		$_SESSION['usuarioNivel'] 	= $resultado['nivel_usu'];
		$_SESSION['usuarioST'] 	  	= $resultado['status_usu'];
		$_SESSION['usuarioSetor'] 	= $resultado['id_setor'];
		$_SESSION['usuarioCliente']	= $resultado['id_cliente'];

		if ($this->alwaysValidate) {
			$_SESSION['usuarioLogin'] = $usuario;
			$_SESSION['usuarioSenha'] = $senhaHash;
		}

		return true;
	}

	public function getAcesso($usuarioId)
	{
		return $this->repo->getAcessoById($usuarioId);
	}

	public function updateAcesso($usuarioId, $datetime)
	{
		$this->repo->updateAcesso($usuarioId, $datetime);
	}
}
