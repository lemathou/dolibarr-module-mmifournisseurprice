<?php




require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

dol_include_once('/mmicommon/class/mmi_generic.class.php');

class MMIFournisseurPriceUtils extends MMI_Generic_1_0
{

	public function updateCostPricePropal()
	{

		global $conf, $langs, $user;;
		$langs->load('mmifournisseurprice@mmifournisseurprice');

		$error = 0;
		$this->output = '';
		$this->errors = [];

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();
		$day_before= dol_time_plus_duree($now,-1,'d');
		$day_before_date= dol_mktime(0, 0, 0, dol_print_date($day_before,'%m'), dol_print_date($day_before,'%d'), dol_print_date($day_before,'%Y'));

		$this->output .= $langs->trans('MMIFournPriceCronCostPricePropalStartJob', dol_print_date($now, 'dayhourtext')) . '<BR>';

		$this->db->begin();


		$sql ="UPDATE " . MAIN_DB_PREFIX . "propaldet as dest,
				" . MAIN_DB_PREFIX . "propal as p,
				" . MAIN_DB_PREFIX . "product as prod,
				" . MAIN_DB_PREFIX . "propal_extrafields as pext
			SET dest.buy_price_ht=prod.cost_price
			WHERE dest.fk_propal = p.rowid
			AND prod.rowid=dest.fk_product
			AND p.fk_statut IN (0,1)
			AND pext.fk_object=p.rowid
			AND dest.buy_price_ht<>prod.cost_price
			AND pext.dt_fin_ope_fourn='".$this->db->idate($day_before_date)."'";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_affected=$this->db->affected_rows($resql);
			if ($num_affected>0) {
				$this->output .= $langs->trans('MMIFournPriceCronCostPriceUpdate', $num_affected).'<BR>';
			}
		} else {
			$error++;
			$this->output .= $langs->trans('MMIFournPriceCronCostPriceSQLError') . ' $sql='.$sql. ' Error=' . $this->db->lasterror . '<BR>';
			$this->errors[] = $langs->trans('MMIFournPriceCronCostPriceSQLError') . ' $sql='.$sql. ' Error=' . $this->db->lasterror;
		}

		if (empty($error)) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}

		// We are in a call from other class specificly for on catalogue ref, no need to log
		$this->output .= $langs->trans('MMIFournPriceCronCostPricePropalEndJob', dol_print_date(dol_now(), 'dayhourtext')) . '<BR>';
		dol_syslog($langs->trans('MMIFournPriceCronCostPricePropalEndJob', dol_print_date($now, 'dayhourtext')), LOG_DEBUG);

		if (strlen($this->output) > 4294967295) {
			$this->output = substr($this->output, 0, 4294967295 - 1);
		}

		return (!empty($error) ? $error : 0);
	}
}
