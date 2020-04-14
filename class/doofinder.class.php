<?php
/* Copyright (C) 2019 Francis Appels <francis.appels@z-application.com>
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
 * \file    /class/doofinder.class.php
 * \ingroup doofinder
 * \brief   class for communicating to another dolibarr using rest service
 */


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/doofinder/lib/php-doofinder/autoload.php');
dol_include_once('/doofinder/lib/doofinder.lib.php');

/**
 * Class Doofinder
 */
class Doofinder
{
	/**
	 * La clé d'API search engine.
	 *
	 * @var string
	 */
	private $apikeySearch = '';

	/**
	 * La ID HASH d'API search engine.
	 *
	 * @var string
	 */
	private $apiHashidSearch = '';

	/**
	 * La clé d'API managment.
	 *
	 * @var string
	 */
	private $apikeyManagement = '';

	/**
	 * Définit si doofinder search api est bien authentifié ou non.
	 *
	 * @var bool
	 */
	private $isSearchAuthenticated = false;

	/**
	 * Définit si doofinder management api est bien authentifié ou non.
	 *
	 * @var bool
	 */
	private $isManagementAuthenticated = false;

	private $db = null;

	/**
	 * Instance du client search.
	 *
	 * @var \Search\Client
	 */
	private $searchClient;

	/**
	 * Instance du client management.
	 *
	 * @var \Management\Client
	 */
	private $managementClient;

	/**
	 * Instance du searchEngine.
	 *
	 * @var \Search\Client\SearchEngine
	 */
	private $searchEngine;

	private $fieldToSkip = array(
		'options_presta', 
		'options_cover', 
		'barcode_type_code', 
		'barcode_type_label', 
		'barcode_type_coder', 
		'options_rest_sync', 
		'date_creation', 
		'date_modification',
		'element',
		'fk_element',
		'ismultientitymanaged',
		'note_private',
		'oldcopy',
		'regeximgext',
		'table_element',
		'multilangs',
		'stats_propale',
		'stats_proposal_supplier',
		'stats_commande',
		'stats_commande_fournisseur',
		'stats_expedition',
		'stats_reception',
		'stats_contrat',
		'stats_facture',
		'stats_facture_fournisseur'
	);

	const THROTTLEDELAY = 1;

	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

	}

	/**
	 * Modification de la clé d'API Search.
	 *
	 * @param $apikey
	 * @return $this
	 */
	public function setSearchApiKey($apikey)
	{
		$this->apikeySearch = $apikey;

		return $this;
	}

	/**
	 * Modification de la clé d'API Search.
	 *
	 * @return string apikeySearch
	 */
	public function getSearchApiKey()
	{
		return $this->apikeySearch;
	}

	/**
	 * Modification de la ID HASH d'API Search.
	 *
	 * @param $hasid
	 * @return $this
	 */
	public function setSearchApiHashid($hashid)
	{
		$this->apiHashidSearch = $hashid;

		return $this;
	}

	/**
	 * Modification de la ID HASH d'API Search.
	 *
	 * @return string SearchApiHashid
	 */
	public function getSearchApiHashid()
	{
		return $this->apiHashidSearch;
	}

	/**
	 * Modification de la clé d'API Search.
	 *
	 * @param $apikey
	 * @return $this
	 */
	public function setManagementApiKey($apikey)
	{
		$this->apikeyManagement = $apikey;

		return $this;
	}

	/**
	 * Retourne l'état d'authentification de l'utilisateur courant.
	 *
	 * @param string $mode 'management' or 'search'
	 * @return bool
	 */
	public function isAuthenticated($mode)
	{
		if ($mode === 'management') {
			return $this->isManagementAuthenticated;
		} else {
			return $this->isSearchAuthenticated;
		}
	}

	/**
	 * Retourne l'instance du client search utilisé.
	 *
	 * @return \Search\Client
	 */
	public function getSearchClient()
	{
		return $this->searchClient;
	}

	/**
	 * Retourne l'instance du client management utilisé.
	 *
	 * @return \Management\Client
	 */
	public function getManagementClient()
	{
		return $this->managementClient;
	}


	/**
	 * Connexion à l'API Management.
	 * 
	 * @return boolean true connected, false not connected
	 */
	private function managementConnect()
	{
		$this->managementClient = new \Doofinder\Api\Management\Client($this->apikeyManagement);
		if (! is_object($this->managementClient)) {
			$this->errors[] = 'DoofinderManagementApiError';
			return false;
		} else {
			$this->isManagementAuthenticated = true;
			return true;
		}
	}

	/**
	 * Connexion à l'API Search.
	 * 
	 * @return boolean true connected, false not connected
	 */
	private function searchConnect()
	{
		$this->searchClient = new \Doofinder\Api\Search\Client($this->apiHashidSearch, $this->apikeyManagement);
		if (! is_object($this->searchClient)) {
			$this->errors[] = 'DoofinderSearchApiError';
			return false;
		} else {
			$this->isSearchAuthenticated = true;
			return true;
		}
	}

	/**
	 * Connect to doofinder
	 *
	 */

	public function connect() 
	{
		global $conf, $dolibarr_main_url_root;

		$searchApiKey = '';
		$managementApiKey = '';
		$searchEngineName = '';

		$searchEngines = array();
		$searchEngine = new stdClass();
		
		if (! empty($conf->global->DOOFINDER_SEARCHENGINE)) $searchEngineName=$conf->global->DOOFINDER_SEARCHENGINE;
		if (! empty($conf->global->DOOFINDER_SEARCH_KEY)) $searchApiKey=$conf->global->DOOFINDER_SEARCH_KEY;
		if (! empty($conf->global->DOOFINDER_MANAGEMENT_KEY)) $managementApiKey=$conf->global->DOOFINDER_MANAGEMENT_KEY;

		if (! empty($managementApiKey)) {
			$this->setManagementApiKey($managementApiKey);
			if ($this->managementConnect()) {
				$throttled = true;
				while ($throttled) {
					try {
						$searchEngines = $this->managementClient->getSearchEngines();
						$throttled = false;
					} catch (Exception $e) {
						if (preg_match('/throttled/i', $e->getMessage())) {
							sleep(self::THROTTLEDELAY);
						} else {
							$this->errors[] = $e->getMessage();
							$throttled = false;
						}
					}
				}
				if (is_array($searchEngines) && $searchEngineName) {
					// get hashid of searchengine
					foreach($searchEngines as $dooSearchEngine) {
						if ($dooSearchEngine->name == $searchEngineName) {
							$this->searchEngine = $dooSearchEngine;
							break;
						}
					}
				}
				if (empty($this->searchEngine->hashid) && $searchEngineName) {
					try {
						// create searchengine if not exist
						$this->searchEngine = $this->managementClient->addSearchEngine(
							$searchEngineName,
							array('language'=>'fr', 'currency'=>'EUR', 'site_url'=>$dolibarr_main_url_root)
						);
						// create product type
						$this->searchEngine->addType('product');
					} catch (Exception $e) {
						$this->errors[] = $e->getMessage();
					}
					
				}

				if ($this->searchEngine->hashid && $searchApiKey) {
					$this->setSearchApiKey($searchApiKey);
					$this->setSearchApiHashid($this->searchEngine->hashid);
					$this->searchConnect();
				}
			}
		}
	}

	/**
	 * sync product
	 */
	public function syncProduct($product)
	{
		global $conf,$user,$langs;

		include_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT .'/core/lib/images.lib.php';

		$result = false;

		if (! $this->isAuthenticated('management')) {
			$this->connect();
		}
		if (! $this->isAuthenticated('management')) {
			return --$error;
		}

		// load stock
		$product->load_stock();

		// get photo link
		$sortfield='position_name';
		$sortorder='asc';

		$dir = $conf->product->multidir_output[$product->entity] . '/';
		$pdir = '/';
		$dir .= get_exdir(0,0,0,0,$product,'product').$product->ref.'/';
		$pdir .= get_exdir(0,0,0,0,$product,'product').$product->ref.'/';

		if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
		{
			$dir = $sdir . '/'. get_exdir($product->id,2,0,0,$product,'product') . $product->id ."/photos/";
			$pdir = '/' . get_exdir($product->id,2,0,0,$product,'product') . $product->id ."/photos/";
		}

		// Defined relative dir to DOL_DATA_ROOT
		$relativedir = '';
		if ($dir)
		{
			$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $dir);
			$relativedir = preg_replace('/^[\\/]/','',$relativedir);
			$relativedir = preg_replace('/[\\/]$/','',$relativedir);
		}

		$dirthumb = $dir.'thumbs/';
		$pdirthumb = $pdir.'thumbs/';
		$filearray=dol_dir_list($dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

		completeFileArrayWithDatabaseInfo($filearray, $relativedir);

		if (count($filearray))
		{
			if ($sortfield && $sortorder)
			{
				$filearray=dol_sort_array($filearray, $sortfield, $sortorder);
			}

			$val = $filearray[0]; // foto first position
			$photo='';
			$file = $val['name'];

			if (image_format_supported($file) >= 0)
			{
				$photo = $file;
				$viewfilename = $file;

				// Find name of thumb file
				$photo_vignette=basename(getImageFileNameForSize($dir.$file, '_small'));
				if (! dol_is_file($dirthumb.$photo_vignette)) $photo_vignette='';

				// Get filesize of original file
				$imgarray=dol_getImageSize($dir.$photo);

				$relativefile=preg_replace('/^\//', '', $pdir.$photo);
				

				if ($photo_vignette)
				{
					$image_link = DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$product->entity.'&file='.urlencode($pdirthumb.$photo_vignette);
				}
				else {
					$image_link = DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$product->entity.'&file='.urlencode($pdir.$photo);
				}
			}
		}

		if (! empty($product->barcode)) {
			$product->fetch_barcode(); // make sure coder is fetched
			$barcode_img = DOL_URL_ROOT.'/viewimage.php?modulepart=barcode&generator='.urlencode($product->barcode_type_coder).'&code='.urlencode($product->barcode).'&encoding='.urlencode($product->barcode_type_code);
		} else {
			$barcode_img = '';
		}
		// convert product to array
		$productArray = json_decode(json_encode($product), true);
		unset($productArray['db']); // don't send database handle
		$productArray['title'] = $productArray['label'];
		unset($productArray['label']);
		$productArray['mpn'] = $productArray['ref'];
		unset($productArray['ref']);
		$productArray['gtin'] = $productArray['barcode'];
		unset($productArray['barcode']);
		//$productArray['group_id'] = $product->id; // TODO get parent variant
		$productArray['image_link'] = $image_link;
		$productArray['barcode_img'] = $barcode_img;
		if ($product->isService()) {
			$productArray['stock_reel'] = 1;
		}
		$feedItem = $this->createFeedItem($productArray);
		$result = $this->syncItem('product', $feedItem);
		return $result;
	}

	private function syncItem($type, $object) {
		$throttled = true;
		while ($throttled) {
			try {
				$item = $this->searchEngine->getItem($type, $object['id']);
				$throttled = false;
				if (count($object) > 0) {
					$throttled = true;
					while ($throttled) {
						try {
							$item = $this->searchEngine->updateItem($type, $object['id'], $object);
							$throttled = false;
							return true;
						} catch (Exception $e) {
							if (preg_match('/throttled/i', $e->getMessage())) {
								sleep(self::THROTTLEDELAY);
							} else {
								$this->errors[] = $e->getMessage();
								$throttled = false;
								return false;
							}
						}
					}
				} else {
					return true;
				}
			} catch (Exception $e) {
				if (preg_match('/not found/i', $e->getMessage())) {
					// add
					$throttled = true;
					while ($throttled) {
						try {
							$item = $this->searchEngine->addItem($type, $object);
							$throttled = false;
							return true;
						} catch (Exception $e) {
							if (preg_match('/throttled/i', $e->getMessage())) {
								sleep(self::THROTTLEDELAY);
							} else {
								$this->errors[] = $e->getMessage();
								$throttled = false;
								return false;
							}
						}
					}
				} else if (preg_match('/throttled/i', $e->getMessage())) {
					sleep(self::THROTTLEDELAY);
				} else {
					$this->errors[] = $e->getMessage();
					$throttled = false;
					return false;
				}
			}
		}
	}

	/**
	 * delete product
	 */
	public function deleteProduct($product)
	{
		if (! $this->isAuthenticated('management')) {
			$this->connect();
		}
		if (! $this->isAuthenticated('management')) {
			return --$error;
		}
		$result = $this->deleteItem('product', $product->id);
		return $result;
	}

	private function deleteItem($type, $id) {
		$throttled = true;
		while ($throttled) {
			try {
				$item = $this->searchEngine->deleteItem($type, $id);
				$throttled = false;
				return true;
			} catch (Exception $e) {
				if (preg_match('/throttled/i', $e->getMessage())) {
					sleep(self::THROTTLEDELAY);
				} else {
					$this->errors[] = $e->getMessage();
					$throttled = false;
					return false;
				}
			}
		}
	}

	function createFeedItem($srcArray)
	{
		$param = array();
		foreach ($srcArray as $srcField => $srcValue) {
			if (is_array($srcValue) && ! is_numeric($srcField) && ! in_array($srcField, $this->fieldToSkip)) {
				$param[$srcField] = $this->createFeedItem($srcArray[$srcField]);
				if ((is_array($param[$srcField]) && count($param[$srcField]) == 0) || null === $param[$srcField]) {
					unset($param[$srcField]);
				}
			} else {
				if (null !== $srcValue && '' !== $srcValue && ! in_array($srcField, $this->fieldToSkip)) {
					if (is_numeric($srcField)) {
						// mustachesify
						$param[] = array('index' => $srcField, 'value' => $srcValue);
					} else {
						$param[$srcField] = $srcValue;
					}
				}
			}
		}
		return $param;
	}
}