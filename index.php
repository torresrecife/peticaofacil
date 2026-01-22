<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
	error_reporting(1);
	ini_set("display_errors", 1 );
	header('Cache-Control: no cache'); //no cache 
	session_cache_limiter('private_no_expire'); // works //
	session_cache_limiter('public'); // works too session_start(); 
	
	include("inc/seguranca.php");
	include("inc/functions.php");
	protegePagina();

	if (!empty($_POST) && getenv('APP_DEBUG') === 'true') {
		$logDir = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
		if (is_dir($logDir) && is_writable($logDir)) {
			$logFile = $logDir . DIRECTORY_SEPARATOR . 'post.log';
			$payload = date('Y-m-d H:i:s') . " POST=" . json_encode($_POST, JSON_UNESCAPED_UNICODE) . PHP_EOL;
			file_put_contents($logFile, $payload, FILE_APPEND);
		}
	}

$wdb = null;
$conexao2 = null;
$serv2 = '';
$db2 = '';
$dados = null;
$dados2 = null;
	if (class_exists(\App\Repositories\ConfigRepository::class)) {
		$configRepo = new \App\Repositories\ConfigRepository($conexao1);
		$wdb = $configRepo->findActiveByTipoId($_POST['TIPOPET'] ?? null);
	}
	
	if($_POST['TIPOCHA']!=""){
		if(!empty($wdb)){
			$serv2	= $wdb['ip_db'];
			$user2 	= $wdb['usu_db'];
			$senha2	= $wdb['senha_db'];
			$db2	= $wdb['data_db'];
			$query2	= $wdb['query_db'];
			$where2	= $wdb['where_db'];

			if (class_exists(\App\Services\PeticaoService::class)) {
				$petService = new \App\Services\PeticaoService();
				$dados = $petService->fetchDados($wdb, $_POST['TIPOCHA']);
				$conexao2 = $dados ? true : null;
			} else {
				$dados = null;
				$conexao2 = null;
			}
		}
	} elseif($_POST['TIPOPET']!=""){
		if(!empty($wdb)){
			
			$serv2	  = $wdb['ip_db'];
			$user2 	  = $wdb['usu_db'];
			$senha2	  = $wdb['senha_db'];
			$db2	  = $wdb['data_db'];
			$query2	  = $wdb['query_db'];
			$where2	  = $wdb['where_db'];

			if (class_exists(\App\Services\PeticaoService::class)) {
				$petService = new \App\Services\PeticaoService();
				$dados2 = $petService->fetchSample($wdb);
				$conexao2 = $dados2 ? true : null;
			} else {
				$dados2 = null;
				$conexao2 = null;
			}
		}
	}

	//verifica se foi conectado ao servidor
	if (!$conexao2){
		$cntdo = "<i style='color:#FF2626'> Não foi possível conectar ao servidor: <b>$serv2</b>.</i>";
		$style = "style='display:none'";
	}else{
		$cntdo = "<i> Conectado ao servidor: <b>$serv2</b>, e banco de dados: <b>$db2</b>.</i>";
		$style = "";
	}
	//parâmetros dos usuários
	$usu_setor 	 = $_SESSION['usuarioSetor'];
	$usu_cliente = $_SESSION['usuarioCliente'];
	$usu_nivel 	 = $_SESSION['usuarioNivel'];
	$usu_id    	 = $_SESSION['usuarioID'];
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pt-br" lang="pt-br" dir="ltr" >
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<?php
		$baseUrl = getenv('APP_URL') ?: '/peticaofacil';
		$basePath = rtrim(parse_url($baseUrl, PHP_URL_PATH), '/');
	?>
	<base href="<?php echo $basePath ? $basePath.'/' : '/'; ?>">
		<title>Apresentação - Administração</title>
		<link href="css/images/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
		<link rel="stylesheet" href="css/template.css" type="text/css" />
		<link rel="stylesheet" href="css/custom-theme/jquery-ui-1.8.23.custom.css">
		<script type="text/javascript" src="js/jquery-1.8.0.min.js">		</script>
		<script type="text/javascript" src="js/jquery-ui-1.8.23.custom.min.js"></script>
		<script type="text/javascript" src="js/moment.js"></script>
		<script type="text/javascript" src="js/jquery.meio.mask.js">	 	</script>
		<script type="text/javascript" src="ckeditor/ckeditor.js">			</script>
        <script type="text/javascript" src="ckfinder/ckfinder.js">			</script>
		<script type="text/javascript" src="ckeditor/adapters/jquery.js">	</script>		
		<script type="text/javascript" src="js/default.js">					</script>   	
		<script type="text/javascript">
			if (typeof EnviarDados !== 'function') {
				function EnviarDados(form, hid, pet) {
					var hidInput = document.getElementById('hid_enviar');
					var tipoInput = document.getElementById('TIPOPET');
					if (hidInput) { hidInput.value = hid; }
					if (tipoInput) { tipoInput.value = pet; }
					if (document.form_iniciais) {
						document.form_iniciais.action = form || window.location.pathname;
						document.form_iniciais.submit();
					}
					return false;
				}
			}
			if (typeof PetiDados !== 'function') {
				function PetiDados(valor1, valor2, valor3, valor4, valor5, valor6) {
					var idPecas = document.getElementById('id_pecas');
					var tipoId = document.getElementById('tipo_id');
					var nomepet = document.getElementById('nomepet');
					var nomecli = document.getElementById('nomecli');
					if (idPecas) { idPecas.value = valor3; }
					if (tipoId) { tipoId.value = valor4; }
					if (nomepet) { nomepet.value = valor5; }
					if (nomecli) { nomecli.value = valor6; }
					return EnviarDados(valor1, valor2, valor4);
				}
			}
		</script>
		<!--[if IE 7]><link href="templates/bluestork/css/ie7.css" rel="stylesheet" type="text/css" /><![endif]-->
	</head>
<body id="minwidth-body">
<form name="form_iniciais" action="index.php" method="POST">
	<div class="head_bk" ></div>
	<div class="head_fixed">
		<div id="border-top" class="h_blue">
			<span class="logo"><img src="css/images/logo.png" alt="Sistema de Petição" /></span>
			<span class="title"><a href="index.php">Petição Fácil - NEO</a></span>
		</div>
		<?php
		// echo ">>>>>>>>>>>>>>>>>>>".$_POST['hid_enviar']."<<<<<<<";
		if($_POST['hid_enviar']==2 || $_POST['hid_enviar']==3 || $_POST['hid_enviar']==4){
			?>
			<div id="header-box">
				<div id="module-status">
					<span class="viewsite"><a href="javascript:EnviarDados('index.php','','');">In&iacute;cio</a></span>
					<span class="voltar"><a href="javascript:window.history.go(-1)">Voltar</a></span>
					<span class="logout"><a href="sair.php">Sair</a></span>
				</div>
				<div id="module-menu">
					<?php
					if($_POST['hid_enviar']==6){
						?>
						<ul id="menu" >
							<li class="node"><a href="#">Lista de modelos</a>
								<?php 
								fc_select_li("tp_tipo_tb","6","tipo_id","tipo_nome",$usu_cliente,$conexao1,$usu_setor); 
								?>
							</li>
						</ul>
						<?php
					}else{
						?>
						<span id="up_topo" style="margin-left:10px" onclick="recolher_topo('#border-top','off')"><img src="css/images/menu/icon-16-upload.png"/></span>
						<span id="dw_topo" style="margin-left:10px;display:none" onclick="recolher_topo('#border-top','on')"><img src="css/images/menu/icon-16-download.png"/></span>
						<?php 
					}
					?>
				</div>
				<div class="clr" ></div>
				<div id="topSpace" ></div>
			</div>
			<?php
		}elseif($_POST['hid_enviar']==5 || $_POST['hid_enviar']==6 || $_POST['hid_enviar']==7 || $_POST['hid_enviar']==8 || $_POST['hid_enviar']==9 || $_POST['hid_enviar']==13){
			?>
			<!--Painel de Administração-->
			<div id="header-box">
				<div id="module-status">
					<span class="viewsite"><a href="javascript:EnviarDados('index.php','','');">Início</a></span>
					<?php 
					if($usu_nivel=='ADM'){
						if($_POST['hid_enviar']==8){
							?>
							<span class='newuser'><a href='javascript:fc_edit_usu("","I");'>Novo Usuário</a></span>
							<?php
						}elseif($_POST['hid_enviar']==9){
							?>
							<span class='newsetor'><a href='javascript:fc_edit_setor("","I");'>Novo Setor</a></span>
							<?php
						}elseif($_POST['hid_enviar']==13){
							?>
							<span class='newcliente'><a href='javascript:fc_edit_cliente("","I");'>Novo Cliente</a></span>
							<?php
						}
						?>
						<span class="viewconfig"><a href="#" onclick="return EnviarDados('index.php','5','')">Administrar</a></span>
						<?php
					}
					?>
					<span class="voltar"><a href="javascript:window.history.go(-1)">Voltar</a></span>
					<span class="logout"><a href="sair.php">Sair</a></span>
				</div>
				<div id="module-menu">
					<?php
					if($_POST['hid_enviar']==6 || $_POST['hid_enviar']==7){
						?>
						<ul id="menu" >
							<li class="node"><a href="#">Lista de modelos</a>
								<?php 
									fc_select_li("tp_tipo_tb",$_POST['hid_enviar'],"tipo_id","tipo_nome",$usu_cliente,$conexao1,$usu_setor); 
								?>
							</li>
						</ul>
						<?php
						if($_GET['TIPOPET']!='' || $_POST['TIPOPET']!=''){
							$TIPOPET = $_POST['TIPOPET'] ? $_POST['TIPOPET'] : $_GET['TIPOPET'];
							if (class_exists(\App\Repositories\TipoRepository::class)) {
								$tipoRepo = new \App\Repositories\TipoRepository($conexao1);
								$tw = $tipoRepo->findWithClienteById($TIPOPET);
							} else {
								$tw = null;
							}
							//echo $tw[0];
						}
						?>
						<?php
					}
					?>
				</div>
				<div class="clr"></div>
				<div id="topSpace" ></div>
			</div>
			<?php
		}else{
			?>
			<!--Painel do Usuário-->
			<div id="header-box">
				<div id="module-status">
					<span class="viewsite"><a href="javascript:EnviarDados('index.php','','');">Início</a></span>
					<?php 
					if($usu_nivel=='ADM' || $usu_nivel=='GER'){
						?>
						<span class="viewconfig"><a href="#" onclick="return EnviarDados('index.php','5','')">Administrar</a></span>
						<?php
					}
					if($usu_nivel=='USU'){
						?>
						<span class="viewcopy"><a href="#" onclick="return EnviarDados('index.php','10','')">Minhas Petições</a></span>
						<?php
					}
					if($usu_nivel=='ADM' || $usu_nivel=='GER'){
						if($_POST['hid_enviar']==12){
						?>
						<span class="new_list"><a href="javascript:void(0)" onclick="return fc_edit_list('','I');">Nova Lista</a></span>
						<?php 
						}else{
						?>
						<span class="viewcopy"><a href="#" onclick="return EnviarDados('index.php','10','')">Petições Salvas</a></span>
						<?php
						}
					}
					?>
					<span class="voltar"><a href="javascript:window.history.go(-1)">Voltar</a></span>
					<span class="logout"><a href="sair.php">Sair</a></span>
				</div>
				<div id="module-menu">
					<ul id="menu" >
						<li class="node"><a href="#">Lista de modelos</a>							
							<?php 
								fc_select_li("tp_tipo_tb",'1',"tipo_id","tipo_nome",$usu_cliente,$conexao1,$usu_setor); 
							?>
						</li>
						<?php if(isset($_POST['TIPOPET']) && $_POST['TIPOPET']!="" ){ ?>
						<li class="node" <?php echo $style; ?>><table><tr><td>Pesquisa: <input type="text" name="TIPOCHA" id="TIPOCHA" class="inputbox"></td></tr></table></li>
						<li class="node" <?php echo $style; ?>><a href="#">Buscar</a>
							<ul>
								<li><a class="icon-16-help" href="#" onclick="return EnviarDados('index.php','1','<?php echo $_POST['TIPOPET']; ?>');"><?php echo $wdb['chave_db']; ?></a></li>
								<li class="separator"><span></span></li>
							</ul>
						</li>
						<?php } ?>
					</ul>
				</div>
				<div class="clr"></div>
				<div id="topSpace" ></div>
			</div>
			<?php
			if($_GET['TIPOPET']!='' || $_POST['TIPOPET']!=''){
				$TIPOPET = $_POST['TIPOPET'] ? $_POST['TIPOPET'] : $_GET['TIPOPET'];
				if (class_exists(\App\Repositories\TipoRepository::class)) {
					$tipoRepo = new \App\Repositories\TipoRepository($conexao1);
					$tw = $tipoRepo->findWithClienteById($TIPOPET);
				} else {
					$tw = null;
				}
				//echo $tw[0];
			}
		}
		?>
	</div>
	<div id="content-box" <?php echo ($_POST['hid_enviar']==5?'style="background: #ffe;"':'');?> >
		<div id="element-box">
			<div class="m wbg" >
				<div class="adminform">
					<?php
						if($_POST['hid_enviar']==1){
							include 'dados.php';
						}elseif($_POST['hid_enviar']==2){
							include 'parag.php';
						}elseif($_POST['hid_enviar']==3){
							include 'editor.php';
						}elseif($_POST['hid_enviar']==5){
							include 'admin.php';
						}elseif($_POST['hid_enviar']==6){
							include 'config2.php';
						}elseif($_POST['hid_enviar']==7){
							include 'dados.php';
						}elseif($_POST['hid_enviar']==8){
							include 'usu.php';
						}elseif($_POST['hid_enviar']==9){
							include 'setor.php';
						}elseif($_POST['hid_enviar']==13){
							include 'cliente.php';
						}elseif($_POST['hid_enviar']==10){
							include 'pecas.php';
						}elseif($_POST['hid_enviar']==11){
							include 'sql.php';
						}elseif($_POST['hid_enviar']==12){
							include 'list.php';
						}else{
							?>
							<div class="content_body">
								<div class="cpanel-left">
									<div class="cpanel">
										<?php fc_select_div("tp_tipo_tb",'1',"tipo_id","tipo_nome",$usu_cliente,"S",$conexao1,$usu_setor); ?>
									</div>
								</div>
								
								<div class="cpanel-right">
									<div class="cpanel">
										<table border="0" width="100%" align="center" style="padding-left: 8px;padding: 4px; border-spacing: 1px;background-color: #FFF;color: #666;">
											<?php
												
												$texto_peças="";
												if($usu_nivel=='USU'){
													$texto_peças="Suas últimas petições salvas:";
												}else{
													if($usu_setor!=0){
														$texto_peças="Últimas petições do setor:";
														if($usu_cliente!=0){
															$texto_peças="Últimas petições da carteira:";
														}
													}else{
														$texto_peças="Últimas petições:";
													}
												}
												$qpet1 = array();
												$qpet2 = array();
												if (class_exists(\App\Services\PecaService::class)) {
													$pecaService = new \App\Services\PecaService($conexao1);
													$qpet1 = $pecaService->listRecent($usu_nivel, $usu_id, $usu_setor, $usu_cliente, 10);
													$qpet2 = $pecaService->listToday($usu_nivel, $usu_id, $usu_setor, $usu_cliente);
												}
												$a = 0;
												$usu = array();
												$set = array();
												$crt = array();
												foreach ($qpet2 as $wpet2) {
													$usu[$wpet2["nome_usu"]] = ($usu[$wpet2["nome_usu"]] ?? 0) + 1;
													$a++;
												}
											?>
											<tr>
												<td colspan="3" style="height:30px;border-bottom:1px dotted #ccc;"><label><?php echo $texto_peças; ?></label></td>
											</tr>
											<?php 
												foreach ($qpet1 as $wpet){
													?>
													<tr>
														<td class="order" style="border-bottom:1px dotted #ccc;text-align:left"><span><img src="img/pdf2.png" style="padding-right:10px;margin-top:-10px"></span></td>
														<td style="border-bottom:1px dotted #ccc;">
															<a href="javascript:void(0)" onclick="return PetiDados('index.php','3','<?php echo $wpet['id_pecas'];?>','<?php echo $wpet['tipo_id']; ?>','<?php echo $wpet['nome_pecas']; ?>','<?php echo $wpet['nome_cli']; ?>');" title="<?php echo strftime("%d/%m/%Y %H:%M:%S", strtotime($wpet['datacad'])); ?>">
																<?php echo $wpet['nome_cli']; ?>
															</a>
														</td>
														<td style="border-bottom:1px dotted #ccc;"><?php echo $wpet['nome_usu']; ?></td>
													</tr>
													<?php 
												}
												
											?>
										</table>
									</div>								
									<div style="margin-top:30px;width:100%;text-align:center;">
										<?php 
										echo "<div style='border-bottom:1px dotted #ccc;margin-bottom:10px;height:20px;width:100%;text-align:center;'>";
											echo "<div style='float:left;width:80%;text-align:left;padding-left:30px;'>Petições elaboradas hoje:</div>";
											echo "<div style='float:left;width:auto;text-align:center;'>"  . $a  . " </div>";
											echo "</div>";
										arsort($usu);
										foreach($usu as $us => $u){
											echo "<div style='height:20px;width:100%;text-align:center;'>";
											echo "<div style='float:left;width:80%;text-align:left;padding-left:30px;'>" . $us . ":</div>";
											echo "<div style='float:left;width:auto;text-align:center;'>"  . $u  . " </div>";
											echo "</div>";
										}
										?>
									</div>
									<div style="width:100%;text-align:center;">
										<?php 
										//foreach($set as $us => $u){
										//	echo "<div style='height:20px;width:100%;text-align:center;'>";
										//	echo "<div style='float:left;width:80%;text-align:left;padding-left:30px;'>" . $us . ":</div>";
										//	echo "<div style='float:left;width:auto;text-align:center;'>"  . $u  . " </div>";
										//	echo "</div>";
										//}
										?>
									</div>										
								</div>								
							</div>	
							<input type="hidden" name="is_pecas" id="is_pecas" value="1" />
							<input type="hidden" name="id_pecas" id="id_pecas" value=""  />
							<input type="hidden" name="tipo_id"  id="tipo_id"  value=""  />
							<input type="hidden" name="nomepet"  id="nomepet"  value=""  />
							<input type="hidden" name="nomecli"  id="nomecli"  value=""  />
							<input type="hidden" name="codsav"	 id="codsav"   value="<?php echo date("YmdHis"); ?>" />
							<?php
						}
						?>
				</div>
			</div>
		</div>
	</div>
	
	<input type="hidden" name="hid_enviar" id="hid_enviar" value="<?php echo $_POST['hid_enviar']; ?>" />
	<input type="hidden" id="TIPOPET" name="TIPOPET" value="<?php echo $TIPOPET; ?>">
	<div id="footer">
		<p class="copyright">
			Criado por Fábio Torres.		
		</p>
	</div>
</form>
<!--Crinado Inputs dinâmicos-->
	<div id="dialog_inputs" style="display:none;overflow-y: scroll;">
		<div style="height:280px">
			<center>
				<table width="100%" class="my_title" style="padding-left:20px">
					<tr>
						<td align="left" colspan="10" >Tipo de campo:</td>
					</tr>
					<tr>
						<td align="right" style="width:10px"><input type="radio" name="SELEINPUT" class="input-default SELEINPUT" value="TIPOINP" TIPO="TEXT"   onclick="fc_optTexto(this.value);" checked></td><td align="left">Texto  </td>
						<td align="right" style="width:10px"><input type="radio" name="SELEINPUT" class="input-default SELEINPUT" value="TIPOSEL" TIPO="SELECT" onclick="fc_optTexto(this.value);"></td>   	 <td align="left">Opções </td>
						<td align="right" style="width:10px"><input type="radio" name="SELEINPUT" class="input-default SELEINPUT" value="TIPOTIT" TIPO="TITLE"  onclick="fc_optTexto(this.value);"></td>   	 <td align="left">Título </td>
						<td align="right" style="width:10px"><input type="radio" name="SELEINPUT" class="input-default SELEINPUT" value="TIPOOCT" TIPO="HIDDEN" onclick="fc_optTexto(this.value);"></td>   	 <td align="left">Oculto </td>
					</tr>                                                                        
				</table>
				<table width="100%" class="my_table" >
					<tr height="30px"><td align="left">Nome:<br/><input type="text" name="INPTITLE" id="INPTITLE" class="input-default" style="width:410px" onkeyup="maiuscula(this);" /></td></tr>
					<tr height="30px">
						<td align="left" style="float:left" colspan="2" >Texto pré:<br/><input type="text"  name="INPTITLE_PRE" id="INPTITLE_PRE" class="input-default" style="width:203px" /></td>
						<td align="left" style="float:left">Texto pós:			   <br/><input type="text"  name="INPTITLE_POS" id="INPTITLE_POS" class="input-default" style="width:203px" /></td>
					</tr>
				</table> 
				<table id="tb_addText" width="100%" class="my_table" >
					<tr>
						<td>
							<table width="100%" align="left">
								<tr height="20px">
									<td align="right"><input type="radio" name="INPCHECK" class="input-default INPCHECK" INALT="" 			value=""   checked  /></td><td align="left"><b>Padrão	</b></td>
									<td align="right"><input type="radio" name="INPCHECK" class="input-default INPCHECK" INALT="date" 		value="date"		/></td><td align="left"><b>Data		</b></td>
									<td align="right"><input type="radio" name="INPCHECK" class="input-default INPCHECK" INALT="decimal" 	value="decimal"		/></td><td align="left"><b>Valor	</b></td>
									<td align="right"><input type="radio" name="INPCHECK" class="input-default INPCHECK" INALT="cpf" 		value="cpf"			/></td><td align="left"><b>Cpf		</b></td>
								</tr>                                                     
								<tr height="20px">                                        
									<td align="right"><input type="radio" name="INPCHECK" class="input-default INPCHECK" INALT="cnpj" 		value="cnpj"		/></td><td align="left"><b>Cnpj		</b></td>
									<td align="right"><input type="radio" name="INPCHECK" class="input-default INPCHECK" INALT="phone" 		value="phone"		/></td><td align="left"><b>Fone		</b></td>
									<td align="right"><input type="radio" name="INPCHECK" class="input-default INPCHECK" INALT="cep" 		value="cep"			/></td><td align="left"><b>CEP		</b></td>
									<td align="right"><input type="radio" name="INPCHECK" class="input-default INPCHECK" INALT="integer" 	value="integer"		/></td><td align="left"><b>Número	</b></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<table id="tb_addSel" class="my_table tb_addSel" width="100%" style="display:none;">
					<tr style="height:10px">
						<td align="left" colspan="8"><h3>Adic/remover texto</h3></td>
					</tr>
					<tr>
						<td align="left" style="width:106px">
							<div id="tdInputs" >
								<a href="javascript:void(0)" class="add" onclick="return false;">	<img src="img/add.png" 	  alt="add" 		 height="24" width="24" title="add input"></a> 
								<a href="javascript:void(0)" class="remove" onclick="return false;">	<img src="img/remove.png" alt="remove input" height="24" width="24"></a>
								<a href="javascript:void(0)" class="reset" onclick="return false;">	<img src="img/reset.png"  alt="reset" 		 height="24" width="24"></a>
							</div>
						</td>
						<td align="right" style="width:60px">Retorno:</td>
						<td align="right" style="width:10px"><input type="radio" name="CKRETURN" class="CKRETURN" value="Tnenhum"  style="margin-left:-2px" checked> </td><td align="left" style="width:65px">Nenhum&nbsp;</td>
						<td align="right" style="width:10px"><input type="radio" name="CKRETURN" class="CKRETURN" value="Tsimples" style="margin-left:-2px">		 </td><td align="left" style="width:65px">Texto Simples&nbsp;</td>
						<td align="right" style="width:10px"><input type="radio" name="CKRETURN" class="CKRETURN" value="Textarea" style="margin-left:-2px">		 </td><td align="left" style="width:65px">Area de Texto&nbsp;</td>
					</tr>
					<tr>
						<td align="left" colspan="8">
							<div id="inputs"></div>
						</td>
					</tr>
				</table>
				<table id="tb_addTit" width="100%" class="my_table" style=" display:none;">
					<tr>
						<td></td>
					</tr>
				</table>
				<table id="tb_addBase" width="100%" class="my_table">
					<tr>
						<td align="left">Colunas:<br/>
							<select name="inputcol" id="inputcol" class="input-default" style="width:194px;height:20px">
								<option>1</option>
								<option>2</option>
								<option>3</option>
							</select>
						</td>
						<td align="left">Proxima Linha:<br/>
							<select name="inputrol" id="inputrol" class="input-default" style="width:194px;height:20px">
								<option value='0'>Não</option>
								<option value='1'>Sim</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align="left">Obrigatório:<br/>
							<select name="inputReq" id="inputReq" class="input-default" style="width:194px;height:20px">
								<option value="2">Sim</option>
								<option value="1">Nao</option>
							</select>
						</td>
						<td align="left">Associar com o Banco de Dados:<br/>
							<select name="db_col" id="db_col" class="input-default" style="width:194px;height:20px">
								<?php
								echo "<option></option>";
								if(isset($dados)){
									foreach($dados as $k => $v){
										echo "<option>" . $k . "</option>";
									}
								}elseif(isset($dados2)){
									foreach($dados2 as $k => $v){
										echo "<option>" . $k . "</option>";
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2" align="left" class="tb_addSel" style="display:none">Associar com Base existente:<br/>
							<select name="tbBase" id="tbBase" class="input-default" style="width:194px;height:20px">
								<?php
								$listGroups = array();
								if (class_exists(\App\Repositories\ListaRepository::class)) {
									$listRepo = new \App\Repositories\ListaRepository($conexao1);
									$listGroups = $listRepo->listGroups();
								}
								echo "<option></option>";
								foreach($listGroups as $wlist){
									echo "<option value='tp_lista_tb_|_nome_lista_|_return_1_|_id_grupo=" . $wlist['id_grupo'] . "_|_vert'>" . $wlist['nome_grupo'] . "</option>";
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td align="left" width="20%">
							<div id="div_InLivre" width="80px" align="left">
								<input type="checkbox" name="inputLivre" id="inputLivre" onclick="manter_inp(this)" class="input-default" style="float:left" />
								<span style="margin-top:5px;float:left">Manter campos livres.</span>
							</div>
						</td>
						<td align="left" width="20%">
							<div id="div_InArqui" width="80px" align="left">
								<input type="checkbox" name="inputArqui" id="inputArqui" class="input-default" value="Y" style="float:left" />
								<span style="margin-top:5px;float:left">Utilizar no nome do arquivo.</span>
							</div>
						</td>
					</tr>
					<tr>
						<td align="left">Ao Entrar:<br/>
							<div id="div_InFocu">
								<input type="text" name="inputFocu" id="inputFocu" class="input-default" style="width:193px;"/>
							</div>
						</td>
						<td align="left">Ao Carregar:<br/>
							<div id="div_InLoad">
								<input type="text" name="inputLoad" id="inputLoad" class="input-default" style="width:193px;"/>
							</div>
						</td>
					</tr>
					<tr>
						<td align="left">Ao Sair:<br/>
							<div id="div_InBlur">
								<input type="text" name="inputBlur" id="inputBlur" class="input-default" style="width:193px;"/>
							</div>
						</td>
						<!--td align="left">Extra:<br/>
							<input type="text" name="inptFunc" id="inptFunc" class="input-default" style="width:194px;"/>
						</td-->
						<td align="left">Ordenar:<br/>
								<?php
								
								$wlinput = 1;
								if (class_exists(\App\Services\InputService::class)) {
									$inputService = new \App\Services\InputService($conexao1);
									$wlinput = $inputService->getNextInputOrder($_POST['TIPOPET']);
								}
								?>
							<div id="div_InOrdn">
								<input type="text" name="inputOrdn" id="inputOrdn" class="input-default" style="width:193px;" value="<?php echo (int) trim($wlinput); ?>"/>
							</div>
						</td>
					</tr>
				</table>
			</center>	
		</div>
	</div><br/>
	<?php 
	mysqli_close($conexao1);
	?>
</body>
</html>


