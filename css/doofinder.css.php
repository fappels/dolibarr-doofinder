<?php
/* Copyright (C) 2018 Francis Appels <francis.appels@z-application.com
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    doofinder/css/doofinder.css.php
 * \ingroup doofinder
 * \brief   CSS file for module Doofinder.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter('public');

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/../main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login']))
{
    $user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

input#search_idprod {
    position: relative;
    border: 2px solid #ca1e8b;
    border-radius: 10px;
}


input#search_idprod::placeholder { /* Chrome, Firefox, Opera, Safari 10.1+ */
   font-size: 15px;
    color: #646464;
}

.df-dol__mpn {
	font-weight: bold;
    display: block;
    text-align: center;
    font-family: roboto,arial,tahoma,verdana,helvetica;
}

.df-dol__stock_reel {
    font-size: 12px;
    font-style: italic;
    font-family: roboto,arial,tahoma,verdana,helvetica;
}

.df-card__title {
    font-size: 14px;
    font-family: roboto,arial,tahoma,verdana,helvetica;
    height: 55px;
    overflow: hidden;
}

.df-card__pricing {
    margin-bottom:0;
}

.df-card__price {
    font-family: roboto,arial,tahoma,verdana,helvetica;
    font-size: 15px;
    display: block !important;
    text-align: center;

}

.df-card__price:nth-of-type(2) {
    font-size: 12px;
    font-style: italic;
    font-family: roboto,arial,tahoma,verdana,helvetica;
    font-weight: 300 !important;
}

.df-card__price:nth-of-type(3) {
    display: none !important;
}

.df-card__pricing button {
    margin: 10px 10px 0 10px;
    font-family: roboto,arial,tahoma,verdana,helvetica;
    font-size: 14px;
    border:none;
    display: inline-block;
    padding: 8px 13px;
    text-align: center;
    cursor: pointer;
    text-decoration: none !important;
    background-image: none;
    background: #ca1e8b;
    border-radius: 10px;
    color:#fff;
}

.df-card__image {
    height: 120px !important;
    margin-bottom: 5px !important;
}

.df-card__image:nth-of-type(1) {
    background: #f4f4f4 !important;
    padding: 3px;
}

.df-card__image:nth-of-type(2) {
    height: 75px !important;
}