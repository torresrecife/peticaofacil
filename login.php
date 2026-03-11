<?php require_once __DIR__ . '/inc/bootstrap.php'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pt-br" lang="pt-br" dir="ltr" >
<head>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo app_charset(); ?>" />
	<?php
		$baseUrl = getenv('APP_URL') ?: '/peticaofacil';
		$basePath = rtrim(parse_url($baseUrl, PHP_URL_PATH), '/');
	?>
	<base href="<?php echo $basePath ? $basePath.'/' : '/'; ?>">
	<title>Petição - Fácil</title>
	<link href="css/images/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
	<link rel="stylesheet" href="css/main.min.css?v=<?php echo asset_version('public/css/main.min.css'); ?>" type="text/css" />
	<!--[if IE 7]><link href="templates/bluestork/css/ie7.css" rel="stylesheet" type="text/css" /><![endif]-->
</head>
<body>
	<script type="text/javascript" src="js/jquery-1.8.0.min.js?v=<?php echo asset_version('public/js/jquery-1.8.0.min.js'); ?>"></script>
	<script type="text/javascript" src="js/main.min.js?v=<?php echo asset_version('public/js/main.min.js'); ?>"></script>
	<div id="border-top" class="h_blue">
		<span class="logo"><img src="css/images/logo.png" alt="Sistema de Petição" /></span>
		<span class="title"><a href="index.php">Petição Fácil</a></span>
	</div>
	<div id="content-box">
			<div id="element-box" class="login">
				<div class="m wbg">
					<h1>Acessar o Painel de Petições</h1>
					<div id="system-message-container"></div>
					<div id="section-box">
						<div class="m">
							<form action="inc/valida.php" method="post" id="form-login">
								<fieldset class="loginform">
									<label id="mod-login-username-lbl" for="mod-login-username">Nome de Usuário</label>
									<input type="text" name="username" id="mod-login-username" class="inputbox" size="15" />
									<label id="mod-login-password-lbl" for="mod-login-password">Senha</label>
									<input type="password" name="passwd" id="mod-login-password" class="inputbox" size="15" />
									
										<div class="button-holder">
											<div class="button1">
												<div class="next">
													<button type="submit" class="login-submit">Acessar</button>
												</div>
											</div>
										</div>
									<div class="clr"></div>
								</fieldset>
							</form>
							<div class="clr"></div>
						</div>
					</div>
					<p>Use um nome de usuário e senha válidos para acessar o Painel de Administração.</p>
					<p></p>
					<div id="lock"></div>
				</div>
			</div>
			<noscript>
				Atenção! JavaScript deve estar habilitado para o bom funcionamento do backend do administrador.			</noscript>
	</div>
	<div id="footer">
		<p class="copyright" style="color:#ccc">
			<a href="#" style="color:#ccc">BVAA</a> - Desenvolvido por: Fábio Torres (<a href="mailto:fabiotorres.adv@gmail.com" style="color:#ccc">fabio@direito2010.com.br</a>).
		</p>
	</div>
</body>
</html>


