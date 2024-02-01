<?php

dol_include_once('custom/mmicommon/class/mmi_actions.class.php');

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

class ActionsMMIFournisseurPrice extends MMI_Actions_1_0
{
	const MOD_NAME = 'mmifournisseurprice';
	
	function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		if ($this->in_context($parameters, ['productservicelist']))
		{
			//var_dump($parameters);
			//$this->results = [];
			$this->resprints .= '<option value="fourn_price">'.img_picto('', 'supplier', 'class="pictofixedwidth"').$langs->trans("MMIFOURNISSEURPRICE_FOURN_PRICE_UPDATE").'</option>';
			$this->resprints .= '<option value="fourn_remise">'.img_picto('', 'supplier', 'class="pictofixedwidth"').$langs->trans("MMIFOURNISSEURPRICE_FOURN_REMISE_UPDATE").'</option>';
			//var_dump($this->resprints);
		}

		return 0;
	}

	function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $user;
		
		$error = 0; // Error counter
		$myvalue = 'test'; // A result value
		$print = '';
		
		if ($this->in_context($parameters, ['productservicelist']))
		{
			//var_dump($parameters);
			//var_dump($action); var_dump($confirm); var_dump($_POST); die();
			
			if ($action == 'confirm_fourn_price') {

				$confirm = GETPOST('confirm');
				if (empty($confirm) || $confirm=='no') {
					$error++;
					$this->errors[] = 'Merci de confirmer...';
				}

				$fk_soc = GETPOST('fk_soc');
				$fourn = new Fournisseur($db);
				$fourn->fetch($fk_soc);
				//var_dump($fourn);
				if (empty($fk_soc) || empty($fourn->id)) {
					$error++;
					$this->errors[] = 'Merci de sélectionner un fournisseur...';
				}

				$fourn_price_percent = GETPOST('fourn_price_percent');
				if (empty($fourn_price_percent) || $fourn_price_percent==0) {
					$error++;
					$this->errors[] = 'Merci de saisir un pourcentage d\'augmentation non nul...';
				}

				$fourn_price_limit_date = GETPOST('fourn_price_limit_date');

				if (!$error && !empty($parameters['toselect'])) {
					$product_ids = $parameters['toselect'];
					$product_static = new Product($db);
					$product_fourn_static = new ProductFournisseur($db);
					foreach($product_ids as $id) {
						$product_static->fetch($id);
						$product_fourn_list = $product_fourn_static->list_product_fournisseur_price($id, '', '');
						foreach($product_fourn_list as $pf) {
							/** @var ProductFournisseur $pf **/
							if ($pf->fourn_id != $fk_soc)
								continue;
							$buyprice = $pf->fourn_unitprice*(1+$fourn_price_percent/100);
							$charges = 0;
							//var_dump("update_buyprice($pf->fourn_qty, $buyprice, $user, $fourn, $pf->fk_availability, $pf->fourn_ref, $pf->fourn_tva_tx, $charges, $pf->fourn_remise_percent)"); die();
							$res = $pf->update_buyprice($pf->fourn_qty, $buyprice, $user, $pf->price_base_type, $fourn, $pf->fk_availability, $pf->ref_supplier, $pf->fourn_tva_tx, $charges, $pf->fourn_remise_percent);
							if (!$res) {
								$error++;
								$errors[] = 'Erreur de calcul du prix achat pour '.$product_static->name;
								continue;
							}
							if (!empty($fourn_price_limit_date)) {
								$sql = 'UPDATE '.$db->prefix().'product_fournisseur_price_extrafields
									SET validity_date="'.$fourn_price_limit_date.'"
									WHERE fk_object='.$pf->product_fourn_price_id;
								//echo $sql;
								$res = $db->query($sql);
								if (!$res) {
									$error++;
									$errors[] = 'Erreur de calcul du prix achat pour '.$product_static->name;
								}
							}
							//var_dump($pf);
						}
					}
					//var_dump($parameters, $_POST);
					//die('Yeah baby yeah');
				}
			}
			// Modifuication de la remise fournisseur
			elseif ($action == 'confirm_fourn_remise') {

				$confirm = GETPOST('confirm');
				if (empty($confirm) || $confirm=='no') {
					$error++;
					$this->errors[] = 'Merci de confirmer...';
				}

				$fk_soc = GETPOST('fk_soc');
				$fourn = new Fournisseur($db);
				$fourn->fetch($fk_soc);
				//var_dump($fourn);
				if (empty($fk_soc) || empty($fourn->id)) {
					$error++;
					$this->errors[] = 'Merci de sélectionner un fournisseur...';
				}

				$fourn_remise_percent = GETPOST('fourn_remise_percent');
				if (empty($fourn_remise_percent) || $fourn_remise_percent==0) {
					$error++;
					$this->errors[] = 'Merci de saisir un pourcentage de remise non nul...';
				}

				if (!$error && !empty($parameters['toselect'])) {
					$product_ids = $parameters['toselect'];
					$product_static = new Product($db);
					$product_fourn_static = new ProductFournisseur($db);
					foreach($product_ids as $id) {
						$product_static->fetch($id);
						$product_fourn_list = $product_fourn_static->list_product_fournisseur_price($id, '', '');
						foreach($product_fourn_list as $pf) {
							/** @var ProductFournisseur $pf **/
							if ($pf->fourn_id != $fk_soc)
								continue;
							$charges = 0;
							//var_dump($pf); die();
							//var_dump($pf, $pf->fourn_qty, $pf->fourn_unitprice, $pf->price_base_type, $user, $fourn, $pf->fk_availability, $pf->fourn_ref, $pf->fourn_tva_tx, $charges, $fourn_remise_percent); die();
							$res = $pf->update_buyprice($pf->fourn_qty, $pf->fourn_unitprice, $user, $pf->price_base_type, $fourn, $pf->fk_availability, $pf->ref_supplier, $pf->fourn_tva_tx, $charges, $fourn_remise_percent);
							//var_dump($pf);
							if (!$res) {
								$error++;
								$errors[] = 'Erreur de calcul du prix achat pour '.$product_static->name;
								continue;
							}
						}
					}
					//var_dump($parameters, $_POST);
					//die('Yeah baby yeah');
				}
			}
		}

		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = $print;
			return 0; // or return 1 to replace standard code
		}
		else
		{
			if (empty($this->errors))
				$this->errors[] = 'Error message';
			return -1;
		}
	}

	function doPreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter
		$myvalue = 'test'; // A result value
		$print = '';

		//print_r($parameters);
		//echo "action: " . $action;
		//print_r($object);
		
		$db = $GLOBALS['db'];

		$massaction = GETPOST('massaction');
		
		if ($this->in_context($parameters, ['productservicelist']))
		{
			//var_dump($parameters);
			if ($massaction=='fourn_price') {
				$print .= dol_get_fiche_head(null, '', '');
				$print .= '<p>Modification de prix d\'achat en masse ?</p>';
				$print .= '<input type="hidden" name="action" value="confirm_fourn_price">';

				// Form sélection
				$print .= '<p>Restriction au fournisseur : <select name="fk_soc">';
				$print .= '<option value="">-- Choisir --</option>';
				$sql = 'SELECT s.rowid, s.nom label
					FROM '.MAIN_DB_PREFIX.'societe s
					WHERE s.fournisseur=1
					ORDER BY s.nom';
				$resql = $db->query($sql);
				//var_dump($resql); die();
				while($row=$resql->fetch_assoc()) {
					$print .= '<option value="'.$row['rowid'].'">'.$row['label'].'</option>';
				}
				$print .= '</select></p>';
				
				// Form sélection
				$print .= '<p>Pourcentage d\'augmentation : <input type="text" name="fourn_price_percent" value="" />&nbsp;%</p>';
				
				// Form sélection
				$print .= '<p>Date limite de validité du prix : <input type="date" name="fourn_price_limit_date" value="" /></p>';
				
				$print .= '<p>Confirmation : <select class="flat width75 marginleftonly marginrightonly" id="confirm" name="confirm"><option value="yes">Oui</option>
<option value="no" selected="">Non</option></select>';
				$print .= '<input class="button valignmiddle confirmvalidatebutton" type="submit" value="Mettre à jour" /></p>';
				$print .= dol_get_fiche_end();
			}
			//var_dump($parameters);
			if ($massaction=='fourn_remise') {
				$print .= dol_get_fiche_head(null, '', '');
				$print .= '<p>Modification de remise fournisseur en masse ?</p>';
				$print .= '<input type="hidden" name="action" value="confirm_fourn_remise">';

				// Form sélection
				$print .= '<p>Restriction au fournisseur : <select name="fk_soc">';
				$print .= '<option value="">-- Choisir --</option>';
				$sql = 'SELECT s.rowid, s.nom label
					FROM '.MAIN_DB_PREFIX.'societe s
					WHERE s.fournisseur=1
					ORDER BY s.nom';
				$resql = $db->query($sql);
				//var_dump($resql); die();
				while($row=$resql->fetch_assoc()) {
					$print .= '<option value="'.$row['rowid'].'">'.$row['label'].'</option>';
				}
				$print .= '</select></p>';
				
				// Form sélection
				$print .= '<p>Pourcentage de remise : <input type="text" name="fourn_remise_percent" value="" />&nbsp;%</p>';
				
				$print .= '<p>Confirmation : <select class="flat width75 marginleftonly marginrightonly" id="confirm" name="confirm"><option value="yes">Oui</option>
<option value="no" selected="">Non</option></select>';
				$print .= '<input class="button valignmiddle confirmvalidatebutton" type="submit" value="Mettre à jour" /></p>';
				$print .= dol_get_fiche_end();
			}
		}

		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = $print;
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	function ObjectExtraFields($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter
		$print = '';
		
		// @todo : mettre les champs dans le module mmiproduct, ne laisser que la partie calcul auto
		if ($this->in_context($parameters, 'pricesuppliercard'))
		{
			global $conf, $langs;

			$form = $parameters['form'];
			$autocalculate = !empty($conf->global->MMIFOURNISSEURPRICE_AUTOCALCULATE);
			$usercancreate = $parameters['usercancreate'];
			// disabled @todo make it realley editable...
			$usercancreate = false;

			// Cost price. Can be used for margin module for option "calculate margin on explicit cost price
			print '<tr><td>';
			$textdesc = $langs->trans("ExtrafieldToolTip_product_logistic_cost_price");
			$text = $form->textwithpicto($langs->trans("Extrafield_product_logistic_cost_price"), $textdesc, 1, 'help', '');
			print $form->editfieldkey($text, 'logistic_cost_price', $object->array_options['options_logistic_cost_price'], $object, $usercancreate, 'amount:6');
			print '</td><td>';
			print $form->editfieldval($text, 'logistic_cost_price', $object->array_options['options_logistic_cost_price'], $object, $usercancreate, 'amount:6');
			print '</td></tr>';

			// Cost price. Can be used for margin module for option "calculate margin on explicit cost price
			print '<tr><td>';
			$textdesc = $langs->trans("ExtrafieldToolTip_product_misc_cost_price");
			$text = $form->textwithpicto($langs->trans("Extrafield_product_misc_cost_price"), $textdesc, 1, 'help', '');
			print $form->editfieldkey($text, 'misc_cost_price', $object->array_options['options_misc_cost_price'], $object, $usercancreate, 'amount:6');
			print '</td><td>';
			print $form->editfieldval($text, 'misc_cost_price', $object->array_options['options_misc_cost_price'], $object, $usercancreate, 'amount:6');
			print '</td></tr>';

			// Cost price. Can be used for margin module for option "calculate margin on explicit cost price
			print '<tr><td>';
			$textdesc = $langs->trans("ExtrafieldToolTip_product_shipping_cost_price");
			$text = $form->textwithpicto($langs->trans("Extrafield_product_shipping_cost_price"), $textdesc, 1, 'help', '');
			print $form->editfieldkey($text, 'shipping_cost_price', $object->array_options['options_shipping_cost_price'], $object, $usercancreate && !$autocalculate, 'amount:6');
			print '</td><td>';
			print $form->editfieldval($text, 'shipping_cost_price', $object->array_options['options_shipping_cost_price'], $object, $usercancreate && !$autocalculate, 'amount:6');
			print '</td></tr>';

			if ($autocalculate) {
				print '<tr><td></td><td>'.'ATTENTION : Le montant des frais d\'acheminements et le prix de revient sont recalculés automatiquement à chaque modification du produit ou d\'un prix d\'achat fournisseur.<br />Le prix fournisseur le plus bas est utilisé, sur la base du minimum de "frais d\'acheminements" + "prix d\'achat".'.'</td></tr>';
			}
		}

		if (! $error)
		{
			$this->resprints = $print;
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}
	
	public function h()
	{
		//pricesuppliercard
	}
}

ActionsMMIFournisseurPrice::__init();
