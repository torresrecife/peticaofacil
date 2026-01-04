<?php
header("Content-Type: text/html; charset=ISO-8859-1",true);

error_reporting(0);
ini_set("display_errors", 0 );
	
include("seguranca.php");

protegePagina();

$inptitle  = $_POST['inptitle']  ? utf8_encode(strtoupper($_POST['inptitle'])) : "";
$inppre    = $_POST['inppre']    ? strtoupper($_POST['inppre']) : "";
$inppos    = $_POST['inppos']    ? strtoupper($_POST['inppos']) : "";
$tipopet   = $_POST['tipopet']	 ? $_POST['tipopet']  : "";
$db_col	   = $_POST['db_col']	 ? $_POST['db_col']   : "";
$inputcol  = $_POST['inputcol']	 ? $_POST['inputcol'] : "";
$inputrol  = $_POST['inputrol']	 ? $_POST['inputrol'] : 0;
$inpcheck  = $_POST['inpcheck']	 ? $_POST['inpcheck'] : "";
$inputReq  = $_POST['inputReq']	 ? $_POST['inputReq'] : "";
$inputFocu = $_POST['inputFocu'] ? addslashes($_POST['inputFocu']) : "";
$inputLoad = $_POST['inputLoad'] ? addslashes($_POST['inputLoad']) : "";
$inputBlur = $_POST['inputBlur'] ? addslashes($_POST['inputBlur']) : "";
$inputOrdn = $_POST['inputOrdn'] ? addslashes($_POST['inputOrdn']) : "";
$tbBase    = $_POST['tbBase']	 ? $_POST['tbBase']   : "";
$inputArqui= $_POST['inputArqui']? $_POST['inputArqui']   : "";
$campoId   = $_POST['campoId']	 !="" ? $_POST['campoId'] 	: "";

if($_POST['flag']=="I"){
	
	if($inputcol==1){$twidth=265;}elseif($inputcol==2){$twidth=560;}elseif($inputcol==3){$twidth=860;}
	
	$Qconf = mysqli_query($conexao1,"SELECT id_input FROM tp_inputs_tb WHERE input_title = '" . $inptitle . "' AND tipo_id = '" . $tipopet ."' AND listsel = 'N' AND input_tipo != 'TITLE'");
	if(mysqli_num_rows($Qconf)>0){
		echo 2;
		exit;
	}
	
	if($_POST['dadSel']=="TIPOINP" || $_POST['dadSel']=="TIPOOCT"){
		$qIns  = "INSERT INTO tp_inputs_tb SET ";
		$qIns .= "tipo_id 	  = $tipopet, ";
 	    $qIns .= $inppre     != "" ? "input_pre   = '$inppre', 	 " : "";
 	    $qIns .= $inppos     != "" ? "input_pos   = '$inppos', 	 " : "";
 	    $qIns .= $inptitle   != "" ? "input_title = '$inptitle', " : "";
		$qIns .= "input_tipo  = '" . ($_POST['dadSel']=='TIPOOCT'?'HIDDEN':'TEXT') . "', ";
	    $qIns .= $db_col     != "" ? "input_val   = '$db_col',  "   : "";
	    $qIns .= $inpcheck   != "" ? "input_alt   = '$inpcheck',  " : "";
	    $qIns .= $inputcol   != "" ? "input_cols  = '$inputcol',  " : "";
	    $qIns .= "input_rols = '$inputrol',";
	    $qIns .= $inputFocu  != "" ? "input_focu  = '$inputFocu', " : "";
	    $qIns .= $inputLoad  != "" ? "input_load  = '$inputLoad', " : "";
	    $qIns .= $inputBlur  != "" ? "input_blur  = '$inputBlur', " : "";
	    $qIns .= $inputArqui == "checked" ?"nomepet= 'Y'," : "";
	    $qIns .= "input_width = $twidth, ";
	    $qIns .= "input_req   = $inputReq, ";
		if($inputOrdn==""){
			$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '$tipopet' AND t.listsel = 'N') ";
		}else{
			$qIns .= "input_order   = $inputOrdn ";
		}
		//echo $qIns;
		$query = mysqli_query($conexao1,$qIns);
		echo 1;
	}elseif($_POST['dadSel']=="TIPOSEL"){
		$qIns  = "INSERT INTO tp_inputs_tb SET ";
		$qIns .= "tipo_id 	  = $tipopet, ";
		$qIns .= $inppre   	 != "" ? "input_pre = '$inppre', " : "";
		$qIns .= $inppos   	 != "" ? "input_pos = '$inppos', " : "";
		$qIns .= $inptitle   != "" ? "input_title = '$inptitle', " : "";
		$qIns .= "input_tipo = 'SELECT', ";
		$qIns .= $tbBase 	 != "" ? "input_db 	= '$tbBase', " : "";
		$qIns .= $db_col 	 != "" ? "input_val 	= '$db_col', " : "";
		$qIns .= "input_cols  = 1, ";
		if($inputOrdn==""){
			$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '$tipopet' AND t.listsel = 'N') ";
		}else{
			$qIns .= "input_order   = $inputOrdn ";
		}
		$query = mysqli_query($conexao1,$qIns);
		
		///pega o ID do novo campo////
		$mxInp = mysqli_query($conexao1,"SELECT MAX(id_input) FROM tp_inputs_tb WHERE listsel = 'N' limit 1 ");
		$mxWil = mysqli_fetch_array($mxInp);
		
		$dadI  = explode("_|_",$_POST['dadI']);
		foreach($dadI as $dd){
			$dadT = explode("-|-",$dd);
			if($dd!=""){
				$q = mysqli_query($conexao1,"INSERT INTO tp_dados_tb SET id_input =  " . $mxWil[0] . ", nome_dados = '" . $dadT[0] . "', return_1 = '" . $dadT[1] . "', id_setor = 1, listsel = 'N' ");
			}
		}
		if($_POST['ckreturn']!="Tnenhum"){
			//fc_ajax_comp("tp_dados_tb","return_1","campo1429","unir","id_dados",this,1); mcampo("campo1428_|_campo1429");
			$qIns  = " INSERT INTO tp_inputs_tb SET";
			$qIns .= " tipo_id = " . $tipopet . ",";
			$qIns .= " input_title = 'RETORNO - " . $inptitle . "',";
			if($_POST['ckreturn']=="Textarea"){
				$qIns .= " input_tipo = 'TEXTAREA',";
			}else{
				$qIns .= " input_tipo = 'TEXT',";
			}
			$qIns .= " input_cols=1,";
			$qIns .= " input_width=265,";
			$qIns .= " input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '$tipopet' AND t.listsel = 'N') ";
			$query = mysqli_query($conexao1,$qIns);
			
			$qUpd  = " UPDATE tp_inputs_tb SET ";
			$qUpd .= " input_focu  = concat('fc_ajax_comp(\"tp_dados_tb\",\"return_1\",\"','campo" . ($mxWil[0]+1) . "','\",\"unir\",\"id_dados\",this,1); mcampo(\"campo" . $mxWil[0] . "_|_campo" . ($mxWil[0]+1) . "\");'), ";
			$qUpd .= " input_load  = concat('fc_ajax_comp(\"tp_dados_tb\",\"return_1\",\"','campo" . ($mxWil[0]+1) . "','\",\"unir\",\"id_dados\",this,1); mcampo(\"campo" . $mxWil[0] . "_|_campo" . ($mxWil[0]+1) . "\");'), ";
			$qUpd .= " input_blur  = concat('fc_ajax_comp(\"tp_dados_tb\",\"return_1\",\"','campo" . ($mxWil[0]+1) . "','\",\"unir\",\"id_dados\",this,1); mcampo(\"campo" . $mxWil[0] . "_|_campo" . ($mxWil[0]+1) . "\");')  ";
			$qUpd .= " WHERE id_input = " . $mxWil[0] . " ";
			$query = mysqli_query($conexao1,$qUpd);
		}
		////cria o campo de retorno da lista pré definida/////
		if($tbBase!=""){
			$qIns  = " INSERT INTO tp_inputs_tb SET";
			$qIns .= " tipo_id = " . $tipopet . ",";
			$qIns .= " input_title = 'RETORNO - " . $inptitle . "',";
			$qIns .= " input_tipo = 'TEXT',";
			$qIns .= " input_cols=1,";
			$qIns .= " input_width=265,";
			$qIns .= " input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '$tipopet' AND t.listsel = 'N') ";
			$query = mysqli_query($conexao1,$qIns);
			
			///pega o ID do novo campo, criado para o retorno da lista, que acabou de criar/////
			$mxRet = mysqli_query($conexao1,"SELECT MAX(id_input) FROM tp_inputs_tb WHERE listsel = 'N' limit 1 ");
			$wxRet = mysqli_fetch_array($mxRet);
			
			$qUpd  = " UPDATE tp_inputs_tb SET ";
			$qUpd .= " input_focu  = 'fc_ajax_comp(\"tp_lista_tb\",\"return_1\",\"campo".$wxRet[0]."\",\"unir\",\"id_lista\",this,1); mcampo(\"campo" . $mxWil[0] . "_|_campo".$wxRet[0]."\"); $(\"#campo".$wxRet[0]."\").focus();', ";
			$qUpd .= " input_load  = 'fc_ajax_comp(\"tp_lista_tb\",\"return_1\",\"campo".$wxRet[0]."\",\"unir\",\"id_lista\",this,1); mcampo(\"campo" . $mxWil[0] . "_|_campo".$wxRet[0]."\"); $(\"#campo".$wxRet[0]."\").focus();', ";
			$qUpd .= " input_blur  = 'fc_ajax_comp(\"tp_lista_tb\",\"return_1\",\"campo".$wxRet[0]."\",\"unir\",\"id_lista\",this,1); mcampo(\"campo" . $mxWil[0] . "_|_campo".$wxRet[0]."\"); $(\"#campo".$wxRet[0]."\").focus();'  ";
			$qUpd .= " WHERE id_input = " . $mxWil[0] . " ";
			$query = mysqli_query($conexao1,$qUpd);
		}
		echo 1;	
	}elseif($_POST['dadSel']=="TIPOTIT"){
		$qIns  = " INSERT INTO tp_inputs_tb SET";
		$qIns .= " tipo_id = " . $tipopet . ",";
		$qIns .= " input_title = '" . $inptitle . "',";
		$qIns .= " input_tipo = 'TITLE',";
		$qIns .= " input_cols=3,";
		$qIns .= " input_width=860,";
		$qIns .= " input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '$tipopet' AND t.listsel = 'N') ";
		$query = mysqli_query($conexao1,$qIns);
		echo 1;
	}
}elseif($_POST['flag']=="E" && $_POST['campoId']!=''){
	$campoId  = $_POST['campoId'];
	$i = 0;
	$q  = " SELECT * FROM tp_inputs_tb";
	$q .= " WHERE id_input = " . $campoId . " ";
	$query = mysqli_query($conexao1,$q);
	$while = mysqli_fetch_row($query);
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	foreach($while as $w){
		echo utf8_decode($w) . "-|-";
	}
}elseif($_POST['flag']=="U" && $_POST['campoId']!=''){
	
	if($inputcol==1){$twidth=265;}elseif($inputcol==2){$twidth=560;}elseif($inputcol==3){$twidth=860;}
	
	if($_POST['dadSel']=="TIPOINP" || $_POST['dadSel']=="TIPOOCT"){
		$qUpd  = " UPDATE tp_inputs_tb SET ";
		$qUpd .= " tipo_id 	 =  $tipopet," ; 
		$qUpd .= $inppre 	!= "" ? " input_pre = '$inppre'," 	:""; 
		$qUpd .= $inppos	!= "" ? " input_pos = '$inppos'," 	:""; 
		$qUpd .= $inptitle 	!= "" ? " input_title = '$inptitle'," 	:""; 
		$qUpd .= "input_tipo  = '" . ($_POST['dadSel']=='TIPOOCT'?'HIDDEN':'TEXT') . "', ";
		$qUpd .= $db_col 	!= "" ? " input_val   = '$db_col',"		:"";
		$qUpd .= " input_alt = '$inpcheck',";
		$qUpd .= $inputcol 	!= "" ? " input_cols  = '$inputcol',"	:"";
		$qUpd .= " input_rols  = '$inputrol',";
		$qUpd .= " input_focu  = '$inputFocu',";
		$qUpd .= " input_load  = '$inputLoad',";
		$qUpd .= " input_blur  = '$inputBlur',";
		$qUpd .= " input_order = '$inputOrdn',";
		$qUpd .= $twidth	!= "" ? " input_width =  $twidth,"		:"";
		$qUpd .= $inputArqui == "checked" ?"nomepet = 'Y'," : "nomepet = 'N'," ;
		$qUpd .= $inputReq 	!= "" ? " input_req   =  $inputReq"	:"";
		$qUpd .= " WHERE id_input = $campoId ";
		$qUpd .= " AND listsel = 'N'";
		//echo $qUpd;
		$query = mysqli_query($conexao1,$qUpd);
		echo 1;
	}elseif($_POST['dadSel']=="TIPOSEL"){
		
		$qUpd  = " UPDATE tp_inputs_tb SET ";
		$qUpd .= " tipo_id 	= $tipopet, ";
		$qUpd .= $inppre != "" ? " input_pre = '$inppre', " : "";
		$qUpd .= $inppos != "" ? " input_pos = '$inppos', " : "";
		$qUpd .= $inptitle != "" ? " input_title = '$inptitle', " : "";
		$qUpd .= $db_col != "" ? " input_val = '$db_col'," : ""; 
		//$qUpd .= " input_alt = '$inpcheck',";
		$qUpd .= " input_tipo = 'SELECT', ";
		$qUpd .= $tbBase != "" ? " input_db = '$tbBase', " : "";
		$qUpd .= $inputcol 	!= "" ? " input_cols  = '$inputcol',"	:"";
		$qUpd .= $inputrol 	!= "" ? " input_rols  = '$inputrol',"	:"";
		$qUpd .= " input_focu  = '$inputFocu',";
		$qUpd .= " input_load  = '$inputLoad',";
		$qUpd .= " input_blur  = '$inputBlur',";
		$qUpd .= " input_order = '$inputOrdn',";
		$qUpd .= $twidth	!= "" ? " input_width =  $twidth,"		:"";
		$qUpd .= $inputReq 	!= "" ? " input_req   =  $inputReq"		:"";
		$qUpd .= " WHERE id_input = $campoId ";
		$qUpd .= " AND listsel = 'N' ";
		$query = mysqli_query($conexao1,$qUpd);
								
		//deleta os possíveis antigos dados da seleção anterior
		$mxInp = mysqli_query($conexao1,"DELETE FROM tp_dados_tb WHERE id_input = $campoId AND listsel = 'N' ");
		$mxWil = mysqli_fetch_array($mxInp);
		
		$dadI  = explode("_|_",$_POST['dadI']);
		foreach($dadI as $dd){
			$dadT  = explode("-|-",$dd);
			if($dd!=""){
				$q = mysqli_query($conexao1,"INSERT INTO tp_dados_tb SET id_input =  $campoId, nome_dados = '" . $dadT[0] . "', return_1 = '" . $dadT[1] . "', id_setor = 1, listsel = 'N'");
			}
		}
		echo 1;
	}elseif($_POST['dadSel']=="TIPOTIT"){
		$query = mysqli_query($conexao1,"UPDATE tp_inputs_tb SET tipo_id = " . $tipopet . ", input_title = '" . $inptitle . "', input_tipo = 'TITLE', input_cols=3, input_width=860 WHERE id_input = $campoId AND listsel = 'N' ");
		echo 1;
	}
}elseif($_POST['flag']=="D"){
	$query = mysqli_query($conexao1,"DELETE FROM `tp_inputs_tb` WHERE `id_input`= " . $_POST['idvalor'] . " AND listsel = 'N' LIMIT 1 ");
	$query = mysqli_query($conexao1,"DELETE FROM `tp_dados_tb` WHERE `id_input`= " . $_POST['idvalor'] . " AND listsel = 'N' ");
	echo 1;
}elseif($_POST['flag']=="L"){
	if($_POST['dadSel']=="TIPOSEL"){
		$qIns  = "INSERT INTO tp_inputs_tb SET ";
		$qIns .= "tipo_id 	 = 1, ";
		$qIns .= $inppre  	 != "" ? "input_pre = '$inppre', " : "";
		$qIns .= $inppos   	 != "" ? "input_pos = '$inppos', " : "";
		$qIns .= $inptitle	 != "" ? "input_title = '$inptitle', " : "";
		$qIns .= "input_tipo = 'SELECT', ";
		$qIns .= $tbBase 	 != "" ? "input_db 	= '$tbBase', " : "";
		$qIns .= "input_cols = 1, ";
		$qIns .= $db_col 	 != "" ? "input_val 	= '$db_col', " : "";
		$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = $tipopet AND t.listsel = 'Y') ";
		$query = mysqli_query($conexao1,$qIns);
		
		$mxInp = mysqli_query($conexao1,"SELECT MAX(id_input) FROM tp_inputs_tb AND listsel = 'N' limit 1 ");
		$mxWil = mysqli_fetch_array($mxInp);
		
		$dadI  = explode("_|_",$_POST['dadI']);
		foreach($dadI as $dd){
			$dadT  = explode("-|-",$dd);	
			if($dd!=""){
				$q = mysqli_query($conexao1,"INSERT INTO tp_dados_tb SET id_input =  " . $mxWil[0] . ", nome_dados = '" . $dadT[0] . "', return_1	= '" . $dadT[1] . "', id_setor = 1, listsel = 'Y' ");
			}
		}
		echo 1;
	}
}elseif($_POST['flag']=="G"){
	$idvalor = $_POST['idvalor'];
	?>
	<select name="inputLoad" id="inputLoad" class="input-default" style="width:160px; height:20px">
		<?php
		header("Content-Type: text/html; charset=ISO-8859-1",true);
		$qlist = mysqli_query($conexao1,"SELECT * FROM tp_inputs_tb WHERE listsel = 'N' and tipo_id=" . $idvalor ." ");
		echo "<option></option>";
		while($wlist = mysqli_fetch_array($qlist))
		{
			echo "<option value='$(this).val($(\"#campo" . $wlist['id_input'] . "\").val());'>" . $wlist['input_title'] . "</option>";
		}
		?>
	</select>
	<?php
}
?>