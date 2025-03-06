#!/bin/php
<?php

$script_file = basename(__FILE__);

// Include Dolibarr environment
require_once __DIR__.'/../master_load.inc.php';

$days_min = 28;
$days_max = 35;

$email_to = getDolGlobalString('MMIFOURNISSEURPRICE_VALIDITY_DATE_EMAIL_ALERT_TO');
$email_from = getDolGlobalString('MMIFOURNISSEURPRICE_VALIDITY_DATE_EMAIL_ALERT_FROM');
$email_subject = 'Fournisseurs à recontacter pour mise à jour grille tarifaire';
$email_msg = 'Liste des fournisseurs à recontacter pour mettre à jour la grille tarifaire : '."\n";
$email_headers = 'Content-type: text/plain; charset=utf-8'."\r\n"
	.'From: '.$email_from."\r\n";

$sql = 'SELECT s2.*, s.*, DATEDIFF(s2.validity_date, NOW()) AS validity_days
	FROM '.MAIN_DB_PREFIX.'societe s
	INNER JOIN '.MAIN_DB_PREFIX.'societe_extrafields s2 ON s.rowid=s2.fk_object
	WHERE s.entity IN ('.getEntity('societe', 1).')
		AND s.fournisseur=1 AND DATEDIFF(s2.validity_date, NOW())>'.$days_min.' AND DATEDIFF(s2.validity_date, NOW())<='.$days_max;
echo $sql;
$q = $db->query($sql);
while($r=$q->fetch_assoc()) {
	$email_msg .= '* '.$r['nom'].' - '.$r['validity_date'].' soit'.($r['validity_days']>0 ?' dans '.$r['validity_days'].' jours' :' il y a '.$r['validity_days'].' jours')."\n";
}

//var_dump($msg);

mail($email_to, $email_subject, $email_msg, $email_headers);
