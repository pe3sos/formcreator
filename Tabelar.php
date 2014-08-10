<?php

/**
 * Created by Petre Sosa.
 * Autor URI: www.sosa.ro
 * Date: 4:32 PM, 6/18/14
 */

class Tabelar
{
	public $sql = '';
	public $getsql = '';
	public $where = [];
	public $wheresql = '';
	public $limit = '30';
	public $nr = 1;

	public $showTable = '';
	public $tableclass = 'tabelar tclist';
	public $tableid = '';
	public $tstyle = '';
	public $funcbefore = '';

	public $pagenr = '1';
	public $page = 'page';
	public $pagesuf = '&';
	public $ordonare = '';
	public $grupare = '';
	public $clase = [];
	public $fnclass = '';
	public $trattr = '';
	public $filtre = [];
	public $cautare = [];
	public $fcautare = [];
	public $campuri = [];
	public $fcampuri = [];
	public $captabel = [];
	public $hideColumns = [];
	public $appendtr = '';
	public $theads = '';
	public $tbodys = '';
	public $debug = '0';
	public $showtheads = false;

	public function __construct()
	{
		$this->link = basename($_SERVER['PHP_SELF']);
		$this->db = new Database();
	}

	public function sql($sql)
	{
		return $this->sql = $sql;
	}

	private function getSql()
	{
		$this->getsql = $this->sql . $this->getwhere() . $this->getGrupare() . $this->getOrdonare() . $this->getLimit();
		return $this->getsql;
	}

	public function showsql()
	{
		echo $this->getSql();
	}

	public function getTotalRows()
	{
		$grupcnt = $this->getGrupare();
		//$grupcnt .= isset($grupcnt{3})?' with rollup':'';
		$sqlr = $this->sql . $this->getwhere() . $grupcnt;
		$sqlr = str_replace("\r\n", ' ', $sqlr);

		$rowsql = preg_replace('/(select )(.*)( from )/i', '$1count(*)$3', $sqlr);
		$this->db->Execute($rowsql);
		if ($this->db->GetRecord())
		{
			$randuri = $this->db->Record[0];
		}
		return $randuri;
	}

	public function cautare($id, $arr, $sep, $titlu)
	{
		$this->cautare = array($id => $titlu);

		$this->filtre[] = $this->filtre + array('id' => $id, 'filtru' => $arr, 'sep' => $sep, 'titlu' => $titlu);

		$sql = array();

		foreach ($this->filtre as $filtru)
		{
			if (isset($_POST[$filtru['id']]{0}))
			{
				$filterid = $_POST[$filtru['id']];
				$filterid = trim($filterid);

				$arr = $filtru['filtru'];
				$sqls = join(',', $arr);
				if (isset($this->fcautare[$filtru['id']]) && function_exists($this->fcautare[$filtru['id']]))
				{
					$sql[] = $this->fcautare[$filtru['id']]($filtru, $this);
				}
				else
				{
					$sql[] = count($arr) > 0 ? ' and concat(' . $sqls . ') like "%' . $filterid . '%"' : '';
				}
			}
		}
		$this->wheresql = join('', $sql);
	}

	public function hideColumns($hideColumns)
	{
		$this->hideColumns = is_array($hideColumns) ? $hideColumns : array($hideColumns);
	}

	public function fcautare($arr)
	{
		$this->fcautare = $this->fcautare + $arr;
		return $this->fcautare;
	}

	private function getWhere()
	{
		$sql = $this->wheresql;
		return $sql;
	}

	public function grupare($grupare)
	{
		$this->grupare = ' group by ' . join(',', $grupare);
		return $this->grupare;
	}

	public function getGrupare()
	{
		return $this->grupare;
	}

	public function ordonare($ordonare)
	{
		$this->ordonare = ' order by ' . join(',', $ordonare);
		return $this->ordonare;
	}

	public function getOrdonare()
	{
		return $this->ordonare;
	}

	public function setClass($clase)
	{
		$this->clase = $clase + $this->clase;
		return $this->clase;
	}

	public function setLimit($limit, $page = 1)
	{
		$this->limit = $limit;
		$this->pagenr = $page;
		return $this->limit;
	}

	private function getLimit()
	{
		$this->pagenr = isset($_GET[$this->page]) ? $_GET[$this->page] : 1;
		$sql = ' limit ' . $this->limit * ($this->pagenr - 1) . ', ' . $this->limit;
		$this->nr = $this->limit * ($this->pagenr - 1) + 1;
		return $sql;
	}

	public function link($link)
	{
		$this->pagesuf = '&';
		$this->link = $link;
	}

	public function getLink()
	{
		return $this->link;
	}

	public function campuri($arr)
	{
		if (!isset($arr['-1']{0}))
		{
			$this->campuri = array('-1' => 'nr');
			$this->fcampuri = array('-1' => 'tb_nr');
			$this->captabel = array('-1' => 'Nr.');
			$this->clase = array('-1' => 'c');
		}
		$this->campuri = $this->campuri + $arr;
		return $this->campuri;
	}

	public function fcampuri($arr)
	{
		$this->fcampuri = $this->fcampuri + $arr;
	}

	public function capTabel($captabel)
	{
		$this->captabel = $this->captabel + $captabel;
		return $this->captabel;
	}

	public function getCautare()
	{
		$filter = '';
		if (count($this->filtre) > 0)
		{
			$c = '';
			foreach ($this->filtre as $f)
			{
				$inputv = isset($_POST[$f['id']]{0}) ? $_POST[$f['id']] : '';

				$c .= $f['titlu'] . '<span class="tb_' . $f['id'] . '"><input type="text" name="' . $f['id'] . '" id="' . $f['id'] . '" value="' . $inputv . '"/></span>';
			}

			$link = $this->getlink();

			$filter = '<form action="' . $link . '" method="post" class="tabelar form" >' . $c . '
<span class="submitb"><input type="submit" value="Cauta" /></span>
</form>';
		}
		return $filter;
	}

	public function isCautare()
	{
		foreach ($this->filtre as $f)
		{
			if (isset($_POST[$f['id']]{0}))
			{
				return true;
			}
		}
		return false;
	}

	public function getPaginator()
	{
		$randuri = $this->GetTotalRows();
		if ($randuri > $this->limit)
		{
			return paginator(
				$randuri,
				$this->pagenr,
				$this->limit,
				$this->getlink() . $this->pagesuf . $this->page . '=',
				$endpag = ""
			);
		}
	}

	public function setRowClass($fnclass)
	{
		$this->fnclass = $fnclass;
	}

	public function trBefore($func = '')
	{
		if (isset($func{0}) && function_exists($func))
		{
			$this->funcbefore = $func;
		}
	}

	public function Exec()
	{
		$db = $this->db;
		$db->debug = $this->debug;
		$sql = $this->getsql();
		$sir = $sirh = '';
		$db->Execute($sql);
		$nr = $this->nr;
		while ($db->GetRecord())
		{
			$sirh = '';
			$fnclass = $this->fnclass;
			$trclass = isset($this->fnclass{3}) ? ' class="' . $fnclass($db->Record) . '"' : '';
			$funcbefore = $this->funcbefore;
			isset($funcbefore{2}) ? $funcbefore($db->Record, $this) : '';
			$trattr = $this->trattr;
			$sir .= '<tr ' . $trclass . $trattr . '>';
			foreach ($this->campuri as $k => $camp)
			{
				if (!in_array($k, $this->hideColumns))
				{

					if (isset($this->fcampuri[$k]) && function_exists($this->fcampuri[$k]))
					{
						$data = $this->fcampuri[$k]($db->Record, $this, $k);
					}
					elseif (isset($this->fcampuri[$k]) && method_exists($this, $this->fcampuri[$k]))
					{
						$data = $this->{$this->fcampuri[$k]}($db->Record, $this, $k);
					}
					elseif (isset($db->Record[$camp]))
					{
						$data = $db->Record[$camp];
					}
					else
					{
						$data = '';
					}
					$cdata = isset($this->captabel[$k]{0}) ? $this->captabel[$k] : $camp;
					$class = isset($this->clase[$k]) ? ' class="' . $this->clase[$k] . '"' : '';

					$sirh .= '<td ' . $class . '>' . $cdata . '</td>';
					$sir .= '<td ' . $class . '>' . $data . '</td>';
				}
			}

			$sir .= '</tr>';
			$sir .= "\n";
		}
		$this->theads = $sirh;
		$this->tbodys = $sir;
		// return $sql;
	}

	public function getThead()
	{
		if (!isset($this->theads{1}) && $this->showtheads)
		{
			$txt = '';
			foreach ($this->campuri as $k => $camp)
			{
				if (!in_array($k, $this->hideColumns))
				{

					$cdata = isset($this->captabel[$k]{0}) ? $this->captabel[$k] : $camp;
					$class = isset($this->clase[$k]) ? ' class="' . $this->clase[$k] . '"' : '';

					$txt .= '<td ' . $class . '>' . $cdata . '</td>';
				}
			}
			$this->theads = $txt;
		}

		$text = '<thead><tr>' . $this->theads . '</tr></thead>';
		return $text;
	}

	public function getTable()
	{
		$this->showTable = '<table class="' . $this->tableclass . '" id="' . $this->tableid . '" style="' . $this->tstyle . '">';
		$this->showTable .= $this->getThead();
		$this->showTable .= '<tbody>' . $this->tbodys . $this->appendtr . '</tbody>';
		$this->showTable .= '</table>';

		return $this->showTable;
	}

	public function appendRow($data)
	{
		return $this->appendtr .= $data;
	}

	public function getShowTable()
	{
		echo $this->GetCautare();
		echo $this->getPaginator();
		echo $this->getTable();
		echo $this->getPaginator();
	}

	public function showTable()
	{
		echo $this->get_showTable();
	}
	
	public function tb_nr($d, $t, $k)
	{
		return $this->nr++; 
	}
}
