<?php

namespace App\Services;

use App\Infra\Database;

class InputService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function create(array $data)
	{
		$input = $this->normalizeInputData($data);
		$tipopet = (int) $input['tipopet'];
		$inptitle = $this->esc($input['inptitle']);
		$dadSel = $input['dadSel'];

		$dupSql = "SELECT id_input FROM tp_inputs_tb "
			. "WHERE input_title = '" . $inptitle . "' "
			. "AND tipo_id = " . $tipopet . " "
			. "AND listsel = 'N' "
			. "AND input_tipo != 'TITLE'";
		$dupQuery = mysqli_query($this->db, $dupSql);
		if ($dupQuery && mysqli_num_rows($dupQuery) > 0) {
			return 2;
		}

		if ($dadSel === 'TIPOINP' || $dadSel === 'TIPOOCT') {
			return $this->createTextInput($input) ? 1 : 0;
		}

		if ($dadSel === 'TIPOSEL') {
			return $this->createSelectInput($input) ? 1 : 0;
		}

		if ($dadSel === 'TIPOTIT') {
			return $this->createTitleInput($input) ? 1 : 0;
		}

		return 0;
	}

	public function update($campoId, array $data)
	{
		$input = $this->normalizeInputData($data);
		$campoId = (int) $campoId;
		$dadSel = $input['dadSel'];

		if ($dadSel === 'TIPOINP' || $dadSel === 'TIPOOCT') {
			return $this->updateTextInput($campoId, $input);
		}

		if ($dadSel === 'TIPOSEL') {
			return $this->updateSelectInput($campoId, $input);
		}

		if ($dadSel === 'TIPOTIT') {
			return $this->updateTitleInput($campoId, $input);
		}

		return false;
	}

	public function deleteInput($id)
	{
		$id = $this->esc($id);
		$ok1 = mysqli_query($this->db, "DELETE FROM tp_inputs_tb WHERE id_input = " . $id . " AND listsel = 'N' LIMIT 1");
		$ok2 = mysqli_query($this->db, "DELETE FROM tp_dados_tb WHERE id_input = " . $id . " AND listsel = 'N'");
		return $ok1 && $ok2;
	}

	public function getInputRow($campoId)
	{
		$campoId = (int) $campoId;
		$query = mysqli_query($this->db, "SELECT * FROM tp_inputs_tb WHERE id_input = " . $campoId);
		if (!$query) {
			return null;
		}
		return mysqli_fetch_row($query);
	}

	public function listInputsByTipo($tipoId)
	{
		$tipoId = (int) $tipoId;
		$query = mysqli_query($this->db, "SELECT id_input, input_title FROM tp_inputs_tb WHERE listsel = 'N' and tipo_id = " . $tipoId . " ");
		if (!$query) {
			return array();
		}
		$rows = array();
		while ($row = mysqli_fetch_assoc($query)) {
			$rows[] = $row;
		}
		return $rows;
	}

	public function createListSelect(array $data)
	{
		$input = $this->normalizeInputData($data);
		if ($input['dadSel'] !== 'TIPOSEL') {
			return false;
		}

		$tipopet = (int) $input['tipopet'];

		$qIns = "INSERT INTO tp_inputs_tb SET ";
		$qIns .= "tipo_id = 1, ";
		$qIns .= $input['inppre'] !== '' ? "input_pre = '" . $this->esc($input['inppre']) . "', " : "";
		$qIns .= $input['inppos'] !== '' ? "input_pos = '" . $this->esc($input['inppos']) . "', " : "";
		$qIns .= $input['inptitle'] !== '' ? "input_title = '" . $this->esc($input['inptitle']) . "', " : "";
		$qIns .= "input_tipo = 'SELECT', ";
		$qIns .= $input['tbBase'] !== '' ? "input_db = '" . $this->esc($input['tbBase']) . "', " : "";
		$qIns .= "input_cols = 1, ";
		$qIns .= $input['db_col'] !== '' ? "input_val = '" . $this->esc($input['db_col']) . "', " : "";
		$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = " . $tipopet . " AND t.listsel = 'Y') ";
		$ok = mysqli_query($this->db, $qIns);
		if (!$ok) {
			return false;
		}

		$mxInp = mysqli_query($this->db, "SELECT MAX(id_input) FROM tp_inputs_tb AND listsel = 'N' limit 1 ");
		$mxWil = mysqli_fetch_array($mxInp);
		$inputId = (int) ($mxWil[0] ?? 0);

		$dadI = explode("_|_", $input['dadI']);
		foreach ($dadI as $dd) {
			$dadT = explode("-|-", $dd);
			if ($dd !== '') {
				$nome = $this->esc($dadT[0] ?? '');
				$ret = $this->esc($dadT[1] ?? '');
				mysqli_query($this->db, "INSERT INTO tp_dados_tb SET id_input = " . $inputId . ", nome_dados = '" . $nome . "', return_1 = '" . $ret . "', id_setor = 1, listsel = 'Y' ");
			}
		}

		return true;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}

	private function normalizeInputData(array $data)
	{
		$inptitleRaw = $data['inptitle'] ?? '';
		$inptitle = $inptitleRaw !== '' ? utf8_encode(strtoupper($inptitleRaw)) : '';

		$inppreRaw = $data['inppre'] ?? '';
		$inppre = $inppreRaw !== '' ? strtoupper($inppreRaw) : '';

		$inpposRaw = $data['inppos'] ?? '';
		$inppos = $inpposRaw !== '' ? strtoupper($inpposRaw) : '';

		return array(
			'inptitle' => $inptitle,
			'inppre' => $inppre,
			'inppos' => $inppos,
			'tipopet' => $data['tipopet'] ?? '',
			'db_col' => $data['db_col'] ?? '',
			'inputcol' => $data['inputcol'] ?? '',
			'inputrol' => $data['inputrol'] ?? 0,
			'inpcheck' => $data['inpcheck'] ?? '',
			'inputReq' => $data['inputReq'] ?? '',
			'inputFocu' => $data['inputFocu'] ?? '',
			'inputLoad' => $data['inputLoad'] ?? '',
			'inputBlur' => $data['inputBlur'] ?? '',
			'inputOrdn' => $data['inputOrdn'] ?? '',
			'tbBase' => $data['tbBase'] ?? '',
			'inputArqui' => $data['inputArqui'] ?? '',
			'dadSel' => $data['dadSel'] ?? '',
			'dadI' => $data['dadI'] ?? '',
			'ckreturn' => $data['ckreturn'] ?? '',
		);
	}

	private function resolveWidth($inputcol)
	{
		if ($inputcol === 1) {
			return 265;
		}
		if ($inputcol === 2) {
			return 560;
		}
		if ($inputcol === 3) {
			return 860;
		}
		return null;
	}

	private function createTextInput(array $input)
	{
		$tipopet = (int) $input['tipopet'];
		$inputcol = (int) $input['inputcol'];
		$inputrol = $this->esc($input['inputrol']);
		$inputReq = $input['inputReq'];
		$inputReqSql = $inputReq === '' ? 0 : (int) $inputReq;
		$twidth = $this->resolveWidth($inputcol);

		$qIns = "INSERT INTO tp_inputs_tb SET ";
		$qIns .= "tipo_id = " . $tipopet . ", ";
		$qIns .= $input['inppre'] !== '' ? "input_pre = '" . $this->esc($input['inppre']) . "', " : "";
		$qIns .= $input['inppos'] !== '' ? "input_pos = '" . $this->esc($input['inppos']) . "', " : "";
		$qIns .= $input['inptitle'] !== '' ? "input_title = '" . $this->esc($input['inptitle']) . "', " : "";
		$qIns .= "input_tipo = '" . ($input['dadSel'] === 'TIPOOCT' ? 'HIDDEN' : 'TEXT') . "', ";
		$qIns .= $input['db_col'] !== '' ? "input_val = '" . $this->esc($input['db_col']) . "', " : "";
		$qIns .= $input['inpcheck'] !== '' ? "input_alt = '" . $this->esc($input['inpcheck']) . "', " : "";
		$qIns .= $input['inputcol'] !== '' ? "input_cols = '" . $inputcol . "', " : "";
		$qIns .= "input_rols = '" . $inputrol . "', ";
		$qIns .= $input['inputFocu'] !== '' ? "input_focu = '" . $this->esc($input['inputFocu']) . "', " : "";
		$qIns .= $input['inputLoad'] !== '' ? "input_load = '" . $this->esc($input['inputLoad']) . "', " : "";
		$qIns .= $input['inputBlur'] !== '' ? "input_blur = '" . $this->esc($input['inputBlur']) . "', " : "";
		$qIns .= $input['inputArqui'] === 'checked' ? "nomepet = 'Y', " : "";
		$qIns .= $twidth !== null ? "input_width = " . $twidth . ", " : "";
		$qIns .= "input_req = " . $inputReqSql . ", ";

		if ($input['inputOrdn'] === '') {
			$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '" . $tipopet . "' AND t.listsel = 'N') ";
		} else {
			$qIns .= "input_order = " . (int) $input['inputOrdn'] . " ";
		}

		return mysqli_query($this->db, $qIns);
	}

	private function createSelectInput(array $input)
	{
		$tipopet = (int) $input['tipopet'];

		$qIns = "INSERT INTO tp_inputs_tb SET ";
		$qIns .= "tipo_id = " . $tipopet . ", ";
		$qIns .= $input['inppre'] !== '' ? "input_pre = '" . $this->esc($input['inppre']) . "', " : "";
		$qIns .= $input['inppos'] !== '' ? "input_pos = '" . $this->esc($input['inppos']) . "', " : "";
		$qIns .= $input['inptitle'] !== '' ? "input_title = '" . $this->esc($input['inptitle']) . "', " : "";
		$qIns .= "input_tipo = 'SELECT', ";
		$qIns .= $input['tbBase'] !== '' ? "input_db = '" . $this->esc($input['tbBase']) . "', " : "";
		$qIns .= $input['db_col'] !== '' ? "input_val = '" . $this->esc($input['db_col']) . "', " : "";
		$qIns .= "input_cols = 1, ";
		if ($input['inputOrdn'] === '') {
			$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '" . $tipopet . "' AND t.listsel = 'N') ";
		} else {
			$qIns .= "input_order = " . (int) $input['inputOrdn'] . " ";
		}

		$ok = mysqli_query($this->db, $qIns);
		if (!$ok) {
			return false;
		}

		$mxInp = mysqli_query($this->db, "SELECT MAX(id_input) FROM tp_inputs_tb WHERE listsel = 'N' limit 1 ");
		$mxWil = mysqli_fetch_array($mxInp);
		$inputId = (int) ($mxWil[0] ?? 0);

		$dadI = explode("_|_", $input['dadI']);
		foreach ($dadI as $dd) {
			$dadT = explode("-|-", $dd);
			if ($dd !== '') {
				$nome = $this->esc($dadT[0] ?? '');
				$ret = $this->esc($dadT[1] ?? '');
				mysqli_query($this->db, "INSERT INTO tp_dados_tb SET id_input = " . $inputId . ", nome_dados = '" . $nome . "', return_1 = '" . $ret . "', id_setor = 1, listsel = 'N' ");
			}
		}

		if ($input['ckreturn'] !== 'Tnenhum') {
			$qIns = "INSERT INTO tp_inputs_tb SET ";
			$qIns .= "tipo_id = " . $tipopet . ", ";
			$qIns .= "input_title = 'RETORNO - " . $this->esc($input['inptitle']) . "', ";
			$qIns .= "input_tipo = '" . ($input['ckreturn'] === 'Textarea' ? 'TEXTAREA' : 'TEXT') . "', ";
			$qIns .= "input_cols = 1, ";
			$qIns .= "input_width = 265, ";
			$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '" . $tipopet . "' AND t.listsel = 'N') ";
			mysqli_query($this->db, $qIns);

			$returnId = $inputId + 1;
			$linkJs = 'fc_ajax_comp("tp_dados_tb","return_1","campo' . $returnId . '","unir","id_dados",this,1); mcampo("campo' . $inputId . '_|_campo' . $returnId . '");';
			$linkSql = $this->esc($linkJs);
			$qUpd = "UPDATE tp_inputs_tb SET "
				. "input_focu = '" . $linkSql . "', "
				. "input_load = '" . $linkSql . "', "
				. "input_blur = '" . $linkSql . "' "
				. "WHERE id_input = " . $inputId;
			mysqli_query($this->db, $qUpd);
		}

		if ($input['tbBase'] !== '') {
			$qIns = "INSERT INTO tp_inputs_tb SET ";
			$qIns .= "tipo_id = " . $tipopet . ", ";
			$qIns .= "input_title = 'RETORNO - " . $this->esc($input['inptitle']) . "', ";
			$qIns .= "input_tipo = 'TEXT', ";
			$qIns .= "input_cols = 1, ";
			$qIns .= "input_width = 265, ";
			$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '" . $tipopet . "' AND t.listsel = 'N') ";
			mysqli_query($this->db, $qIns);

			$mxRet = mysqli_query($this->db, "SELECT MAX(id_input) FROM tp_inputs_tb WHERE listsel = 'N' limit 1 ");
			$wxRet = mysqli_fetch_array($mxRet);
			$returnId = (int) ($wxRet[0] ?? 0);

			$linkJs = 'fc_ajax_comp("tp_lista_tb","return_1","campo' . $returnId . '","unir","id_lista",this,1); mcampo("campo' . $inputId . '_|_campo' . $returnId . '"); $("#campo' . $returnId . '").focus();';
			$linkSql = $this->esc($linkJs);
			$qUpd = "UPDATE tp_inputs_tb SET "
				. "input_focu = '" . $linkSql . "', "
				. "input_load = '" . $linkSql . "', "
				. "input_blur = '" . $linkSql . "' "
				. "WHERE id_input = " . $inputId;
			mysqli_query($this->db, $qUpd);
		}

		return true;
	}

	private function createTitleInput(array $input)
	{
		$tipopet = (int) $input['tipopet'];
		$qIns = "INSERT INTO tp_inputs_tb SET ";
		$qIns .= "tipo_id = " . $tipopet . ", ";
		$qIns .= "input_title = '" . $this->esc($input['inptitle']) . "', ";
		$qIns .= "input_tipo = 'TITLE', ";
		$qIns .= "input_cols = 3, ";
		$qIns .= "input_width = 860, ";
		$qIns .= "input_order = (select if(max(t.input_order),max(t.input_order)+1,1) from tp_inputs_tb as t where t.tipo_id = '" . $tipopet . "' AND t.listsel = 'N') ";
		return mysqli_query($this->db, $qIns);
	}

	private function updateTextInput($campoId, array $input)
	{
		$tipopet = (int) $input['tipopet'];
		$inputcol = (int) $input['inputcol'];
		$twidth = $this->resolveWidth($inputcol);

		$qUpd = "UPDATE tp_inputs_tb SET ";
		$qUpd .= "tipo_id = " . $tipopet . ", ";
		$qUpd .= $input['inppre'] !== '' ? "input_pre = '" . $this->esc($input['inppre']) . "', " : "";
		$qUpd .= $input['inppos'] !== '' ? "input_pos = '" . $this->esc($input['inppos']) . "', " : "";
		$qUpd .= $input['inptitle'] !== '' ? "input_title = '" . $this->esc($input['inptitle']) . "', " : "";
		$qUpd .= "input_tipo = '" . ($input['dadSel'] === 'TIPOOCT' ? 'HIDDEN' : 'TEXT') . "', ";
		$qUpd .= $input['db_col'] !== '' ? "input_val = '" . $this->esc($input['db_col']) . "', " : "";
		$qUpd .= "input_alt = '" . $this->esc($input['inpcheck']) . "', ";
		$qUpd .= $input['inputcol'] !== '' ? "input_cols = '" . $inputcol . "', " : "";
		$qUpd .= "input_rols = '" . $this->esc($input['inputrol']) . "', ";
		$qUpd .= "input_focu = '" . $this->esc($input['inputFocu']) . "', ";
		$qUpd .= "input_load = '" . $this->esc($input['inputLoad']) . "', ";
		$qUpd .= "input_blur = '" . $this->esc($input['inputBlur']) . "', ";
		$qUpd .= "input_order = '" . (int) $input['inputOrdn'] . "', ";
		$qUpd .= $twidth !== null ? "input_width = " . $twidth . ", " : "";
		$qUpd .= $input['inputArqui'] === 'checked' ? "nomepet = 'Y', " : "nomepet = 'N', ";
		if ($input['inputReq'] !== '') {
			$qUpd .= "input_req = " . (int) $input['inputReq'];
		} else {
			$qUpd .= "input_req = 0";
		}
		$qUpd .= " WHERE id_input = " . $campoId . " AND listsel = 'N'";

		return mysqli_query($this->db, $qUpd);
	}

	private function updateSelectInput($campoId, array $input)
	{
		$tipopet = (int) $input['tipopet'];
		$inputcol = (int) $input['inputcol'];
		$twidth = $this->resolveWidth($inputcol);

		$qUpd = "UPDATE tp_inputs_tb SET ";
		$qUpd .= "tipo_id = " . $tipopet . ", ";
		$qUpd .= $input['inppre'] !== '' ? "input_pre = '" . $this->esc($input['inppre']) . "', " : "";
		$qUpd .= $input['inppos'] !== '' ? "input_pos = '" . $this->esc($input['inppos']) . "', " : "";
		$qUpd .= $input['inptitle'] !== '' ? "input_title = '" . $this->esc($input['inptitle']) . "', " : "";
		$qUpd .= $input['db_col'] !== '' ? "input_val = '" . $this->esc($input['db_col']) . "', " : "";
		$qUpd .= "input_tipo = 'SELECT', ";
		$qUpd .= $input['tbBase'] !== '' ? "input_db = '" . $this->esc($input['tbBase']) . "', " : "";
		$qUpd .= $input['inputcol'] !== '' ? "input_cols = '" . $inputcol . "', " : "";
		$qUpd .= $input['inputrol'] !== '' ? "input_rols = '" . $this->esc($input['inputrol']) . "', " : "";
		$qUpd .= "input_focu = '" . $this->esc($input['inputFocu']) . "', ";
		$qUpd .= "input_load = '" . $this->esc($input['inputLoad']) . "', ";
		$qUpd .= "input_blur = '" . $this->esc($input['inputBlur']) . "', ";
		$qUpd .= "input_order = '" . (int) $input['inputOrdn'] . "', ";
		$qUpd .= $twidth !== null ? "input_width = " . $twidth . ", " : "";
		if ($input['inputReq'] !== '') {
			$qUpd .= "input_req = " . (int) $input['inputReq'] . " ";
		} else {
			$qUpd .= "input_req = 0 ";
		}
		$qUpd .= "WHERE id_input = " . $campoId . " AND listsel = 'N' ";

		$ok = mysqli_query($this->db, $qUpd);
		if (!$ok) {
			return false;
		}

		mysqli_query($this->db, "DELETE FROM tp_dados_tb WHERE id_input = " . $campoId . " AND listsel = 'N' ");

		$dadI = explode("_|_", $input['dadI']);
		foreach ($dadI as $dd) {
			$dadT = explode("-|-", $dd);
			if ($dd !== '') {
				$nome = $this->esc($dadT[0] ?? '');
				$ret = $this->esc($dadT[1] ?? '');
				mysqli_query($this->db, "INSERT INTO tp_dados_tb SET id_input = " . $campoId . ", nome_dados = '" . $nome . "', return_1 = '" . $ret . "', id_setor = 1, listsel = 'N'");
			}
		}

		return true;
	}

	private function updateTitleInput($campoId, array $input)
	{
		$tipopet = (int) $input['tipopet'];
		$q = "UPDATE tp_inputs_tb SET "
			. "tipo_id = " . $tipopet . ", "
			. "input_title = '" . $this->esc($input['inptitle']) . "', "
			. "input_tipo = 'TITLE', "
			. "input_cols = 3, "
			. "input_width = 860 "
			. "WHERE id_input = " . $campoId . " AND listsel = 'N' ";
		return mysqli_query($this->db, $q);
	}
}
