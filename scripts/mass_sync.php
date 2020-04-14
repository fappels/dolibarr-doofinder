#!/usr/bin/env php
<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) Copyright (C) 2018 Francis Appels    <francis.appels@z-application.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       scripts/mass_sync_shaper.php
 *		\ingroup    viralsync
 *      \brief      Mass sync shaper activated product to shaper dolibarr using REST services
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

// Global variables
$version='1.0';
$error=0;


// -------------------- START OF YOUR CODE HERE --------------------
@set_time_limit(0);							// No timeout for this script
define('EVEN_IF_ONLY_LOGIN_ALLOWED',1);		// Set this define to 0 if you want to lock your script when dolibarr setup is "locked to admin user only".

// Include and load Dolibarr environment variables
$res=0;
if (! $res && file_exists($path."master.inc.php")) $res=@include($path."master.inc.php");
if (! $res && file_exists($path."../master.inc.php")) $res=@include($path."../master.inc.php");
if (! $res && file_exists($path."../../master.inc.php")) $res=@include($path."../../master.inc.php");
if (! $res && file_exists($path."../../../master.inc.php")) $res=@include($path."../../../master.inc.php");
if (! $res) die("Include of master fails");
// After this $db, $mysoc, $langs, $conf and $hookmanager are defined (Opened $db handler to database will be closed at end of file).
// $user is created but empty.

//$langs->setDefaultLang('en_US'); 	// To change default language of $langs
$langs->load("main");				// To load language file for default language

// Load user and its permissions
$result=$user->fetch('','admin');	// Load user for login 'ekow'
if (! $result > 0) { dol_print_error('',$user->error); exit; }
$user->getrights();
$objectTypes = array('product'); //, 'commande', 'customer');
$objectSubTypes = array('all', 'main');//, 'files', 'price', 'stock');

$start = dol_now();
print "***** ".$script_file." (".$version.") date=".dol_print_date($start)." *****\n";
if (! isset($argv[1])) {	// Check parameters
	print "Usage: ".$script_file." objecttype (".implode('|',$objectTypes).") objectsubtype (".implode('|',$objectSubTypes).")\n";
	exit(1);
} else {
	$objectType = $argv[1]; // 'product', 'commande', 'customer'
	$argv[2] ? $objectSubType = $argv[2] : $objectSubType = 'main'; // 'all', 'main', 'multilang', 'files', 'price' , 'stock'
}
if (! in_array($objectType, $objectTypes)) {
	print "Usage: ".$script_file." objecttype (".implode('|',$objectTypes).") objectsubtype (".implode('|',$objectSubTypes).")\n";
	exit(1);
}
if (! in_array($objectSubType, $objectSubTypes)) {
	print "Usage: ".$script_file." objecttype (".implode('|',$objectTypes).") objectsubtype (".implode('|',$objectSubTypes).")\n";
	exit(1);
}
print '--- start'."\n";
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
dol_include_once('/doofinder/class/doofinder.class.php');


if ($objectType == 'product') {
	$sql = "SELECT DISTINCT p.rowid as id from ".MAIN_DB_PREFIX."product p";
	$sql .= " WHERE (p.tosell > 0 OR p.tobuy > 0)";
	
	$resql = $db->query($sql);
	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
			$product = new Product($db);
			$product->fetch($obj->id);
			if ($product->id) {
				print " syncing product id=".$product->id." ref=".$product->ref."\n";
				$error = sync_product($product, $objectSubType);
			}
			// if ($error) break;
			unset($product);
			// break; // test only one
		}
	}
	else
	{
		$error = 1;
	}
}

$time = dol_now()-$start;

print '--- end in '.$time." seconds\n";

exit($error);

function getDolError($errors = null, $error = null)
{
	global $langs;
	$errorText = '';
	
	$langs->load("errors");
	$langs->load("main");
	$langs->load("products");
	
	if (is_array($errors) && (count($errors) > 0)) {
		foreach ($errors as $error) {
			$transError = $langs->trans($error);
			$errorText = $errorText . ' ' . $transError ? $transError : $error;
		}
	} else if (is_string($error)) {
		$transError = $langs->trans($error);
		$errorText = $transError ? $transError : $error;
	} else {
		$errorText = 'Undefined error';
	}
	
	return $errorText;
}

function sync_product($product, $objectSubType) {
	global $conf;

	$error = 0;
	// update product
	if (in_array($objectSubType, array('all', 'main', 'main_price'))) {
		$doofinderApi = new Doofinder($db);
		$result = $doofinderApi->syncProduct($product);
		if (!$result) {
			$error--;
		}
	}

	if ($error) {
		$errorMsg = getDolError($pdoofinderApiroduct->errors, $doofinderApi->error);
		print ('"'.$errorMsg.'"'.chr(10));
		return $error;
	} else {
		return 0;
	}
}
