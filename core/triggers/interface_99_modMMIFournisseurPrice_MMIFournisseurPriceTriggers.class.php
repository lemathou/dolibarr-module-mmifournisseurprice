<?php
/* Copyright (C) 2022 Moulin Mathieu <contact@iprospective.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modMMIFournisseurPrice_MMIFournisseurPriceTriggers.class.php
 * \ingroup mmifournisseurprice
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modMMIFournisseurPrice_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/productfournisseurprice.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

/**
 *  Class of triggers for MMIFournisseurPrice module
 */
class InterfaceMMIFournisseurPriceTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "MMIFournisseurPrice triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'mmifournisseurprice@mmifournisseurprice';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->mmifournisseurprice) || empty($conf->mmifournisseurprice->enabled)) {
			return 0; // If module is not enabled, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		// You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
		// For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
		$methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
		$callback = array($this, $methodName);
		if (is_callable($callback)) {
			dol_syslog(
				"Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id
			);

			return call_user_func($callback, $action, $object, $user, $langs, $conf);
		};
		
		//var_dump($action);
		// Or you can execute some code here
		switch ($action) {
			// Products
			//case 'PRODUCT_CREATE':
			case 'PRODUCT_MODIFY':
				if (!empty($conf->global->MMIFOURNISSEURPRICE_AUTOCALCULATE)) {
					$this->product_cost_price_calc($object->id);
				}
				break;
			//case 'PRODUCT_DELETE':
			//case 'PRODUCT_PRICE_MODIFY':
			//case 'PRODUCT_SET_MULTILANGS':
			//case 'PRODUCT_DEL_MULTILANGS':

			case 'PRODUCTFOURNISSEURPRICE_MODIFY':
				if (!empty($conf->global->MMIFOURNISSEURPRICE_AUTOCALCULATE)) {
					$this->product_cost_price_calc($object->fk_product);
				}
				break;
			case 'SUPPLIER_PRODUCT_BUYPRICE_MODIFY':
				if (!empty($conf->global->MMIFOURNISSEURPRICE_AUTOCALCULATE)) {
					$this->product_cost_price_calc($object->product_id);
				}
				break;

			// Supplier orders
			//case 'ORDER_SUPPLIER_CREATE':
			case 'ORDER_SUPPLIER_MODIFY':
				if (!empty($conf->global->MMIFOURNISSEURPRICE_AUTOCALCULATE_ORDERS) && $object->status >= 1) {
					$this->commande_fournisseur_calc($object);
				}
				break;
			case 'ORDER_SUPPLIER_VALIDATE':
			case 'ORDER_SUPPLIER_APPROVE':
			case 'ORDER_SUPPLIER_RECEIVE':
				if (!empty($conf->global->MMIFOURNISSEURPRICE_AUTOCALCULATE_ORDERS)) {
					$this->commande_fournisseur_calc($object);
				}
				//var_dump($object);
				//return -1;
				break;

			// and more...

			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
	
	function commande_fournisseur_calc($object)
	{
		$product_ids = [];
		foreach($object->lines as $line)
			if (is_numeric($line->fk_product) && !in_array($line->fk_product, $product_ids))
				$product_ids[] = $line->fk_product;
		
		$this->product_ids_calc($product_ids);
	}
	
	function product_ids_calc($product_ids)
	{
		global $conf, $user;
		
		$delai_max_mois = $conf->global->MMIFOURNISSEURPRICE_DELAI >0 ?$conf->global->MMIFOURNISSEURPRICE_DELAI :18;
		$delai_max_jours = round($delai_max_mois*365.25/12);
		
		// Récupération des cumuls produits des commandes fournisseur ayant au moins un produit de la liste
		// Groupage par fournisseur car selon eux, les tarifs de transport seront différent !
		// Entrepôt réception spécifié sur la commande (pas une commande directe vers client)
		// On récupère l'ensemble des produits pour bien effectuer la répartition, mais on ne met à jour que ceux donnés dans la liste
		$sql = 'SELECT o.rowid, o.date_creation, o.date_valid, DATEDIFF(o.date_valid, NOW()) delai,
				o2.shipping_price,
				o.fk_soc, ps.rowid fk_product_fournisseur_price,
				ol.fk_product, SUM(ol.qty) qty, SUM(ol.total_ht) total_ht
			FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet ol
			INNER JOIN '.MAIN_DB_PREFIX.'commande_fournisseur o
				ON o.rowid=ol.fk_commande
			INNER JOIN '.MAIN_DB_PREFIX.'commande_fournisseur_extrafields o2
				ON o2.fk_object=ol.fk_commande
			INNER JOIN '.MAIN_DB_PREFIX.'product_extrafields p2
				ON p2.fk_object=ol.fk_product
			INNER JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price ps
				ON ps.fk_product=ol.fk_product AND ps.fk_soc=o.fk_soc
			WHERE o.fk_statut >= 2 AND DATEDIFF(o.date_valid, NOW()) >= -'.$delai_max_jours.'
				AND ol.product_type=0 AND o2.shipping_price IS NOT NULL
				AND o.rowid IN (SELECT DISTINCT _o.rowid FROM '.MAIN_DB_PREFIX.'commande_fournisseur _o INNER JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet _ol ON _ol.fk_commande=_o.rowid WHERE _ol.fk_product IN ('.implode(',', $product_ids).'))
				AND o2.fk_entrepot > 0
				GROUP BY ol.fk_product, o.fk_soc, o.rowid';
		//echo '<p>'.$sql.'</p>';
		$q = $this->db->query($sql);
		//var_dump($q);
		
		// Total par fournisseur !
		$total_ht = [];
		// Liste par fournisseur, des lignes qui nous intéressent dans les commandes
		$c = [];
		while($r=$q->fetch_object()) {
			//var_dump($r);
			if (!isset($total_ht[$r->rowid]))
				$total_ht[$r->rowid] = 0;
			$total_ht[$r->rowid] += $r->total_ht;
			
			// On modifie seulement les prix censés avoir bougé
			if (!in_array($r->fk_product, $product_ids))
				continue;
			
			if (!isset($c[$r->fk_soc.'-'.$r->fk_product]))
				$c[$r->fk_soc.'-'.$r->fk_product] = [];
			$c[$r->fk_soc.'-'.$r->fk_product][] = $r;
		}
		//var_dump($c);
		//var_dump($total_ht);
		
		$product = new Product($this->db);
		
		$productfournisseurprice = new ProductFournisseurPrice($this->db);
		
		// Liste par fournisseur
		$l = [];
		foreach($c as $key=>&$rows) {
			$row = [
				'shipping_price' => 0,
				'price' => 0,
				'qty' => 0,
			];
			foreach($rows as $r) {
				if (!isset($row['fk_product_fournisseur_price'])) {
					$row['fk_product_fournisseur_price'] = $r->fk_product_fournisseur_price;
					$row['fk_product'] = $r->fk_product;
				}
				// partie des frais relative au produit, basé sur le rapport prix prod/prix tot
				$row['shipping_price'] += $r->shipping_price*($r->total_ht/$total_ht[$r->rowid]);
				$row['price'] += $r->total_ht;
				$row['qty'] += $r->qty;
			}
			$l[$key] = $row;
		}
		//var_dump($l);
		foreach($l as &$r) {
			//var_dump($r);
			$product_shipping_price_unit = round($r['shipping_price']/$r['qty'], 5);
			
			$productfournisseurprice->fetch($r['fk_product_fournisseur_price']);
			//$productfournisseurprice->fetch_optionals();
			$productfournisseurprice->array_options['options_shipping_calculated_price'] = $product_shipping_price_unit;
			$productfournisseurprice->update($user);
		}
		//var_dump($l);

		return true;
	}

	public function product_cost_price_calc($product_id)
	{
		global $conf, $user;

		$product = new Product($this->db);
		$product->fetch($product_id);
		//var_dump($product->cost_price);
		$product_fourn = new ProductFournisseur($this->db);

		$product_fourn_list = $product_fourn->list_product_fournisseur_price($product_id);
		//var_dump($product_fourn_list);
		if (empty($product_fourn_list))
			return;

		$productfournisseurprice = new ProductFournisseurPrice($this->db);
		
		// On calcule avec le moins cher (@todo pourquoi ?)
		$fourn_shipping_cost_price = 0;
		$fourn_unit_price = 0;
		foreach($product_fourn_list as $product_fourn_elem) {
			// Si un fournisseur par défaut est spécifié, on ne prend que lui
			if (!empty($product->array_options['option_fk_soc_fournisseur']) && $product_fourn_elem->fourn_id != $product->array_options['option_fk_soc_fournisseur'])
				continue;
			$productfournisseurprice->fetch($product_fourn_elem->product_fourn_price_id);
			//$productfournisseurprice->fetch_optionals();
			//var_dump($fourn_shipping_cost_price+$fourn_unit_price, $productfournisseurprice->unitprice, $productfournisseurprice->array_options, $productfournisseurprice->array_options['options_shipping_price']);
			//var_dump($productfournisseurprice);
			// Test si ce fournisseur est moins cher que le moins cher déjà testé
			if ($conf->global->MMIFOURNISSEURPRICE_AUTOCALCULATE_ORDERS && is_null($productfournisseurprice->array_options['options_shipping_price']))
				$new_shipping_cost_price = (float)($productfournisseurprice->array_options['options_shipping_calculated_price']);
			else
				$new_shipping_cost_price = (float)$productfournisseurprice->array_options['options_shipping_price'];
			$new_fourn_unit_price = (float)$productfournisseurprice->unitprice*(1-$productfournisseurprice->remise_percent/100);
			//var_dump($new_shipping_cost_price, $new_fourn_unit_price);
			if(($fourn_shipping_cost_price+$fourn_unit_price) > 0 && ($fourn_shipping_cost_price+$fourn_unit_price) < ($new_shipping_cost_price+$new_fourn_unit_price))
				continue;
			
			$fourn_shipping_cost_price = $new_shipping_cost_price;
			$fourn_unit_price = $new_fourn_unit_price;
		}
		//echo 'ok';
		$product->array_options['options_shipping_cost_price'] = $fourn_shipping_cost_price;

		$product->cost_price = $fourn_unit_price
		+ $product->array_options['options_shipping_cost_price']
		+ $product->array_options['options_misc_cost_price']
		+ $product->array_options['options_logistic_cost_price']
		+ $product->array_options['options_shipping_price'];
		//var_dump($productfournisseurprice->unitprice);
		//var_dump($product->cost_price);
		//var_dump($product);
		$product->update($product->id, $user, true);
		//$product->fetch($product->id);
		
		//var_dump($object);

		return true;
	}
}
