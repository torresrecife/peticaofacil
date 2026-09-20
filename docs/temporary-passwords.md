# Acesso inicial e reemissao de senha

Ao cadastrar um usuario, o sistema gera uma senha aleatoria de 24 caracteres
(96 bits de aleatoriedade). O responsavel ve essa senha na resposta do cadastro
e deve entrega-la diretamente ao usuario por um canal seguro. Nao ha envio
automatico de email. A senha em texto nao e armazenada no banco nem na sessao;
apenas seu hash e salvo. A pagina de emissao usa Cache-Control: no-store.

A credencial expira em 24 horas. O usuario so pode acessar a troca obrigatoria
de senha e sair ate definir sua senha pessoal. A senha pessoal deve ter entre
12 e 64 caracteres (ate 72 bytes devido ao bcrypt), com letra maiuscula,
letra minuscula, numero e simbolo. Pode conter espacos (que nao contam como simbolo) e deve
ser diferente da temporaria. Nao ha troca periodica obrigatoria.

Na edicao, marcar "Emitir nova senha temporaria" invalida a senha anterior,
reinicia a validade e obriga nova troca, inclusive para quem ja havia acessado.
Editar outros dados sem marcar essa opcao preserva a senha. As autorizacoes
ADM/GER, setor e carteira continuam sendo aplicadas antes da emissao.

O login limita tentativas por login/IP. As sessoes passam a verificar o hash da
senha: apos alteracao, sessoes com o hash anterior sao encerradas na proxima
requisicao. Sessoes que ja existiam antes desta implantacao ainda nao possuem
esse registro e o receberao no proximo acesso.

## Implantacao

Aplicar a migration antes de liberar o novo codigo para requisicoes:

```sh
php artisan migrate --force
```

Migration: 2026_09_20_000100_add_temporary_password_state_to_users.php.
Ela adiciona must_change_password e temporary_password_expires_at; nao altera
senhas existentes. Usuarios legados sem primeiro acesso continuam obrigados a
trocar a senha pelo criterio anterior (acesso_usu vazio). Credenciais legadas
nao recebem uma validade retroativa; pode-se reemiti-las pela edicao.

Executar os testes apenas no banco de testes configurado no phpunit.xml.
