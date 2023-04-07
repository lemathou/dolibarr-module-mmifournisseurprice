<?php

dol_include_once('custom/mmicommon/class/mmi_actions.class.php');

class ActionsMMIFournisseurPrice extends MMI_Actions_1_0
{
	const MOD_NAME = 'mmifournisseurprice';

	function ObjectExtraFields($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter
		$print = '';
		
		if ($this->in_context($parameters, 'pricesuppliercard'))
		{
			$form = $parameters['form'];
			$usercancreate = $parameters['usercancreate'];
			global $langs;

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
