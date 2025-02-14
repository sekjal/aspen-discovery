<?php

require_once ROOT_DIR . '/Action.php';

class Archive_AJAX extends Action {

	function launch() {
		global $timer;
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		//JSON Responses
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		echo json_encode($this->$method());
	}

	/** @noinspection PhpUnused */
	function getRelatedObjectsForExhibit(){
		if (isset($_REQUEST['collectionId'])){
			global $interface;
			global $timer;
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$fedoraUtils = FedoraUtils::getInstance();
			$pid = urldecode($_REQUEST['collectionId']);
			$interface->assign('exhibitPid', $pid);

			$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $page);

			$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'title';
			$interface->assign('sort', $sort);

			if (isset($_REQUEST['reloadHeader'])){
				$interface->assign('reloadHeader', $_REQUEST['reloadHeader']);
			}else{
				$interface->assign('reloadHeader', '0');
			}

			$displayType = 'basic';
			$interface->assign('displayType', $displayType);

			$this->setShowCovers();
			$displayMode = $this->setCoversDisplayMode();

			/** @var SearchObject_IslandoraSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			$searchObject->setDebugging(false, false);
			$searchObject->clearHiddenFilters();
			$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$searchObject->addHiddenFilter('!RELS_EXT_isConstituentOf_uri_ms', "*");
			$searchObject->clearFilters();
			$searchObject->addFilter("RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$pid}\"");
			$searchObject->clearFacets();

			$searchObject->setLimit(24);

//			$searchObject->setSort('fgs_label_s');
			$this->setupTimelineSorts($sort, $searchObject);
			//TODO: Do these sorts work for a basic exhibit?

			$relatedObjects = array();
			$response = $searchObject->processSearch(true, false);
			if ($response && isset($response['error'])){
				$interface->assign('solrError', $response['error']['msg']);
				$interface->assign('solrLink', $searchObject->getFullSearchUrl());
			}
			if ($response && isset($response['response']) && $response['response']['numFound'] > 0) {
				$summary = $searchObject->getResultSummary();
				$interface->assign('recordCount', $summary['resultTotal']);
				$interface->assign('recordStart', $summary['startRecord']);
				$interface->assign('recordEnd', $summary['endRecord']);

				$recordIndex = $summary['startRecord'];
				if ($displayMode == 'list') {
					$recordSet = $searchObject->getResultRecordHTML();
					$interface->assign('recordSet', $recordSet);
				} else {
					$interface->assign('showThumbnailsSorted', true);
					foreach ($response['response']['docs'] as $objectInCollection) {
						/** @var IslandoraRecordDriver $firstObjectDriver */
						$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
						$relatedObject     = array(
							'title' => $firstObjectDriver->getTitle(),
							'description' => $firstObjectDriver->getDescription(),
							'image' => $firstObjectDriver->getBookcoverUrl('medium'),
							'dateCreated' => $firstObjectDriver->getDateCreated(),
							'link' => $firstObjectDriver->getRecordUrl(),
							'pid' => $firstObjectDriver->getUniqueID(),
							'recordIndex' => $recordIndex++
						);
						if ($sort == 'dateAdded') {
							$relatedObject['dateCreated'] = date('M j, Y', strtotime($objectInCollection['fgs_createdDate_dt']));
						} elseif ($sort == 'dateModified') {
							$relatedObject['dateCreated'] = date('M j, Y', strtotime($objectInCollection['fgs_lastModifiedDate_dt']));
						}
						$relatedObjects[] = $relatedObject;
						$timer->logTime('Loaded related object');
					}

				}

				//Get a list of sub collections to use for searching
				//TODO: This may not be required
				$fedoraUtils->getObject($pid);

				$interface->assign('relatedObjects', $relatedObjects);
			}
			return array(
					'success' => true,
					'relatedObjects' => $interface->fetch('Archive/relatedObjects.tpl')
			);
		}else{
			return array(
					'success' => false,
					'message' => 'You must supply the collection and place to load data for'
			);
		}
	}

	/** @noinspection PhpUnused */
	function getRelatedObjectsForScroller(){
		if (isset($_REQUEST['pid'])){
			global $interface;
			global $timer;
			global $logger;
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			FedoraUtils::getInstance();
			$pid = urldecode($_REQUEST['pid']);
			$interface->assign('exhibitPid', $pid);

			if (isset($_REQUEST['reloadHeader'])){
				$interface->assign('reloadHeader', $_REQUEST['reloadHeader']);
			}else{
				$interface->assign('reloadHeader', '1');
			}

			$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $page);

			$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'title';
			$interface->assign('sort', $sort);

			$displayType = 'scroller';
			$interface->assign('displayType', $displayType);

			$this->setShowCovers();
			$displayMode = $this->setCoversDisplayMode();

			/** @var SearchObject_IslandoraSearcher $searchObject */
 			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			$searchObject->setDebugging(false, false);
			$searchObject->clearHiddenFilters();
			$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$searchObject->addHiddenFilter('!RELS_EXT_isConstituentOf_uri_ms', "*");
			$searchObject->clearFilters();
			$searchObject->clearFacets();

			$searchObject->setSearchTerms(array(
					'lookfor' => '"' . $pid . '"',
					'index' => 'IslandoraRelationshipsById'
			));

			$searchObject->setLimit(24);

			$this->setupTimelineSorts($sort, $searchObject);
			$interface->assign('showThumbnailsSorted', true);

			$relatedObjects = array();
			$response = $searchObject->processSearch(true, false);
			if ($response && isset($response['error'])){
				$interface->assign('solrError', $response['error']['msg']);
				$interface->assign('solrLink', $searchObject->getFullSearchUrl());
			}
			if ($response && isset($response['response']) && $response['response']['numFound'] > 0) {
				$summary = $searchObject->getResultSummary();
				$interface->assign('recordCount', $summary['resultTotal']);
				$interface->assign('recordStart', $summary['startRecord']);
				$interface->assign('recordEnd', $summary['endRecord']);
				$recordIndex = $summary['startRecord'];
				$page        = $summary['page'];
				$interface->assign('page', $page);

				if ($displayMode == 'list') {
					$recordSet = $searchObject->getResultRecordHTML();
					$interface->assign('recordSet', $recordSet);
				}

				// Save the search with Map query and filters
				$searchObject->close(); // Trigger save search
				$lastExhibitObjectsSearch    = $searchObject->getSearchId(); // Have to save the search first.
				$_SESSION['exhibitSearchId'] = $lastExhibitObjectsSearch;
				$logger->log("Setting exhibit search id to $lastExhibitObjectsSearch", Logger::LOG_DEBUG);

				if ($displayMode == 'covers') {
					foreach ($response['response']['docs'] as $objectInCollection) {
						/** @var IslandoraRecordDriver $firstObjectDriver */
						$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
						$relatedObject     = array(
							'title' => $firstObjectDriver->getTitle(),
							'description' => $firstObjectDriver->getDescription(),
							'image' => $firstObjectDriver->getBookcoverUrl('medium'),
							'dateCreated' => $firstObjectDriver->getDateCreated(),
							'link' => $firstObjectDriver->getRecordUrl(),
							'pid' => $firstObjectDriver->getUniqueID(),
							'recordIndex' => $recordIndex++
						);
						if ($sort == 'dateAdded') {
							$relatedObject['dateCreated'] = date('M j, Y', strtotime($objectInCollection['fgs_createdDate_dt']));
						} elseif ($sort == 'dateModified') {
							$relatedObject['dateCreated'] = date('M j, Y', strtotime($objectInCollection['fgs_lastModifiedDate_dt']));
						}
						$relatedObjects[] = $relatedObject;
						$timer->logTime('Loaded related object');
					}
				}


				$this->processTimelineData($response, $interface);
			}

			$interface->assign('relatedObjects', $relatedObjects);

			return array(
					'success' => true,
					'relatedObjects' => $interface->fetch('Archive/relatedObjects.tpl')
			);
		}else{
			return array(
					'success' => false,
					'message' => 'You must supply the collection and place to load data for'
			);
		}
	}

	function getRelatedObjectsForTimelineExhibit(){
		if (isset($_REQUEST['collectionId'])){
			global $interface;
			global $timer;
			global $logger;
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$pid = urldecode($_REQUEST['collectionId']);
			$interface->assign('exhibitPid', $pid);

			if (isset($_REQUEST['reloadHeader'])){
				$interface->assign('reloadHeader', $_REQUEST['reloadHeader']);
			}else{
				$interface->assign('reloadHeader', '1');
			}

			$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $page);

			$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'title';
			$interface->assign('sort', $sort);

			$displayType = 'timeline';
			$interface->assign('displayType', $displayType);

			$this->setShowCovers();
			$displayMode = $this->setCoversDisplayMode();

			/** @var SearchObject_IslandoraSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			//$searchObject->setDebugging(false, false);
			$searchObject->clearHiddenFilters();
			$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$searchObject->addHiddenFilter('!RELS_EXT_isConstituentOf_uri_ms', "*");
			$searchObject->clearFilters();
			$searchObject->addFilter("RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$pid}\"");
			$searchObject->clearFacets();
			//Add filtering based on date filters
			$timeLineSetUp = false;
			if (!empty($_SESSION['ExhibitContext']) && $_SESSION['ExhibitContext'] == $pid) {

				if (!empty($_REQUEST['dateFilter']) && $_REQUEST['dateFilter'] != 'all') {
					$_SESSION['dateFilter'] = $_REQUEST['dateFilter']; // store applied date filters
				} else {
					// Clear time data
					unset($_SESSION['dateFilter']);
				}
			}
			$this->setupTimelineFacetsAndFilters($searchObject);

			$searchObject->setLimit(24);

			$this->setupTimelineSorts($sort, $searchObject);
			$interface->assign('showThumbnailsSorted', true);

			$relatedObjects = array();
			$response = $searchObject->processSearch(true, false);
			if ($response && isset($response['error'])){
				$interface->assign('solrError', $response['error']['msg']);
				$interface->assign('solrLink', $searchObject->getFullSearchUrl());
			}
			if ($response && isset($response['response']) && $response['response']['numFound'] > 0) {
				$summary = $searchObject->getResultSummary();
				if (!$timeLineSetUp) {
					$interface->assign('recordCount', $summary['resultTotal']);
				}
				$interface->assign('recordStart', $summary['startRecord']);
				$interface->assign('recordEnd',   $summary['endRecord']);
				$recordIndex = $summary['startRecord'];
				$page = $summary['page'];
				$interface->assign('page', $page);

				if ($displayMode == 'list') {
					$recordSet = $searchObject->getResultRecordHTML();
					$interface->assign('recordSet', $recordSet);
				}


				// Save the search with Map query and filters
				$searchObject->close(); // Trigger save search
				$lastExhibitObjectsSearch = $searchObject->getSearchId(); // Have to save the search first.
				$_SESSION['exhibitSearchId'] = $lastExhibitObjectsSearch;
				$logger->log("Setting exhibit search id to $lastExhibitObjectsSearch", Logger::LOG_DEBUG);

				if ($displayMode == 'covers') {
					foreach ($response['response']['docs'] as $objectInCollection) {
						/** @var IslandoraRecordDriver $firstObjectDriver */
						$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
						$relatedObject     = array(
							'title' => $firstObjectDriver->getTitle(),
							'description' => $firstObjectDriver->getDescription(),
							'image' => $firstObjectDriver->getBookcoverUrl('medium'),
							'dateCreated' => $firstObjectDriver->getDateCreated(),
							'link' => $firstObjectDriver->getRecordUrl(),
							'pid' => $firstObjectDriver->getUniqueID(),
							'recordIndex' => $recordIndex++
						);
						if ($sort == 'dateAdded') {
							$relatedObject['dateCreated'] = date('M j, Y', strtotime($objectInCollection['fgs_createdDate_dt']));
						} elseif ($sort == 'dateModified') {
							$relatedObject['dateCreated'] = date('M j, Y', strtotime($objectInCollection['fgs_lastModifiedDate_dt']));
						}
						$relatedObjects[] = $relatedObject;
						$timer->logTime('Loaded related object');
					}
				}

				if (!$timeLineSetUp){
					$this->processTimelineData($response, $interface);
				}
			}

			$interface->assign('relatedObjects', $relatedObjects);
			return array(
					'success' => true,
					'relatedObjects' => $interface->fetch('Archive/relatedObjects.tpl')
			);
		}else{
			return array(
					'success' => false,
					'message' => 'You must supply the collection and place to load data for'
			);
		}
	}

	function getRelatedObjectsForMappedCollection(){
		if (isset($_REQUEST['collectionId']) && isset($_REQUEST['placeId'])){
			global $interface;
			global $timer;
			global $logger;
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$fedoraUtils = FedoraUtils::getInstance();
			$pid = urldecode($_REQUEST['collectionId']);
			/** @var IslandoraRecordDriver $collectionDriver */
			$collectionDriver = RecordDriverFactory::initIslandoraDriverFromPid($pid);
			$interface->assign('exhibitPid', $pid);
			if (isset($_REQUEST['reloadHeader'])){
				$interface->assign('reloadHeader', $_REQUEST['reloadHeader']);
			}else{
				$interface->assign('reloadHeader', '1');
			}

			$additionalCollections = array();
			if ($collectionDriver->getModsValue('pikaCollectionDisplay', 'marmot') == 'custom'){
				//Load the options to show
				$collectionOptionsOriginalRaw = $collectionDriver->getModsValue('collectionOptions', 'marmot');
				$collectionOptionsOriginal = explode("\r\n", html_entity_decode($collectionOptionsOriginalRaw));
				$additionalCollections = array();
				if (isset($collectionOptionsOriginal)){
					foreach ($collectionOptionsOriginal as $collectionOption){
						if (strpos($collectionOption, 'googleMap') === 0){
							$filterOptions = explode('|', $collectionOption);
							if (count($filterOptions) > 1){
								$additionalCollections = explode(',', $filterOptions[1]);
								break;
							}
						}
					}
				}
			}

			if (isset($_REQUEST['showTimeline']) && $_REQUEST['showTimeline'] != 'undefined'){
				$interface->assign('showTimeline', $_REQUEST['showTimeline']);
				$showTimeline = $_REQUEST['showTimeline'] == 'true';
			}else{
				$interface->assign('showTimeline', true);
				$showTimeline = true;
			}

			$placeId = urldecode($_REQUEST['placeId']);
			$logger->log("Setting place information for context $placeId", Logger::LOG_DEBUG);
			@session_start();
			$_SESSION['placePid'] =  $placeId;
			$interface->assign('placePid', $placeId);

			/** @var FedoraObject $placeObject */
			$placeObject = $fedoraUtils->getObject($placeId);
			$_SESSION['placeLabel'] = $placeObject->label;
			$logger->log("Setting place label for context $placeObject->label", Logger::LOG_DEBUG);

			if ($showTimeline){
				$interface->assign('displayType', 'map');
			}else{
				$interface->assign('displayType', 'mapNoTimeline');
			}

			$interface->assign('label', $placeObject->label);

			$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $page);

			$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'title';
			$interface->assign('sort', $sort);

			$this->setShowCovers();
			$displayMode = $this->setCoversDisplayMode();

			/** @var SearchObject_IslandoraSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			$searchObject->setDebugging(false, false);
			$searchObject->clearHiddenFilters();
			$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$searchObject->addHiddenFilter('!RELS_EXT_isConstituentOf_uri_ms', "*");
			$searchObject->clearFilters();
			if (isset($additionalCollections) && count($additionalCollections) > 0){
				$filter = "RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$pid}\"";
				foreach ($additionalCollections as $collection){
					$filter .= " OR RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/" . trim($collection) . "\"";
				}
				$searchObject->addFilter($filter);
			}else{
				$searchObject->addFilter("RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$pid}\"");
			}
			$searchObject->setBasicQuery("mods_extension_marmotLocal_relatedEntity_place_entityPid_ms:\"{$placeId}\" OR " .
					"mods_extension_marmotLocal_relatedPlace_entityPlace_entityPid_ms:\"{$placeId}\" OR " .
					"mods_extension_marmotLocal_militaryService_militaryRecord_relatedPlace_entityPlace_entityPid_ms:\"{$placeId}\" OR " .
					"mods_extension_marmotLocal_describedEntity_entityPid_ms:\"{$placeId}\" OR " .
					"mods_extension_marmotLocal_picturedEntity_entityPid_ms:\"{$placeId}\""
			);

			$searchObject->clearFacets();
			if ($showTimeline) {
				$this->setupTimelineFacetsAndFilters($searchObject);
			}
			$this->setupTimelineSorts($sort, $searchObject);
			$interface->assign('showThumbnailsSorted', true);

			$searchObject->setLimit(24);

			$relatedObjects = array();
			$response = $searchObject->processSearch(true, false, true);
			if ($response && isset($response['error'])){
				$interface->assign('solrError', $response['error']['msg']);
				$interface->assign('solrLink', $searchObject->getFullSearchUrl());
			}
			if ($response && isset($response['response']) && $response['response']['numFound'] > 0) {
				$summary = $searchObject->getResultSummary();
				$interface->assign('recordCount', $summary['resultTotal']);
				$interface->assign('recordStart', $summary['startRecord']);
				$interface->assign('recordEnd',   $summary['endRecord']);
				$recordIndex = $summary['startRecord'];

				if ($displayMode == 'list') {
					$recordSet = $searchObject->getResultRecordHTML();
					$interface->assign('recordSet', $recordSet);
				}

				// Save the search with Map query and filters
				$searchObject->close(); // Trigger save search
				$lastExhibitObjectsSearch = $searchObject->getSearchId(); // Have to save the search first.
				$_SESSION['exhibitSearchId'] = $lastExhibitObjectsSearch;
				$logger->log("Setting exhibit search id to $lastExhibitObjectsSearch", Logger::LOG_DEBUG);

				if ($displayMode == 'covers') {
					foreach ($response['response']['docs'] as $objectInCollection) {
						/** @var IslandoraRecordDriver $firstObjectDriver */
						$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
						$relatedObject     = array(
							'title' => $firstObjectDriver->getTitle(),
							'description' => $firstObjectDriver->getDescription(),
							'image' => $firstObjectDriver->getBookcoverUrl('medium'),
							'dateCreated' => $firstObjectDriver->getDateCreated(),
							'link' => $firstObjectDriver->getRecordUrl(),
							'pid' => $firstObjectDriver->getUniqueID(),
							'recordIndex' => $recordIndex++
						);
						if ($sort == 'dateAdded') {
							$relatedObject['dateCreated'] = date('M j, Y', strtotime($objectInCollection['fgs_createdDate_dt']));
						} elseif ($sort == 'dateModified') {
							$relatedObject['dateCreated'] = date('M j, Y', strtotime($objectInCollection['fgs_lastModifiedDate_dt']));
						}
						$relatedObjects[] = $relatedObject;
						$timer->logTime('Loaded related object');
					}
				}
				if ($showTimeline){
					$this->processTimelineData($response, $interface);
				}
			}

			$interface->assign('relatedObjects', $relatedObjects);
			return array(
					'success' => true,
					'relatedObjects' => $interface->fetch('Archive/relatedObjects.tpl')
			);
		}else{
			return array(
					'success' => false,
					'message' => 'You must supply the collection and place to load data for'
			);
		}
	}

	function getEntityFacetValuesForExhibit(){
		if (!isset($_REQUEST['id'])){
			return array(
					'success' => false,
					'message' => 'You must supply the id to load facet data for'
			);
		}
		if (!isset($_REQUEST['facetName'])){
			return array(
					'success' => false,
					'message' => 'You must supply the facetName to load facet data for'
			);
		}

		$pid = urldecode($_REQUEST['id']);

		//get a list of all collections and books within the main exhibit so we can find all related data.
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();
		$exhibitObject = $fedoraUtils->getObject($pid);
		RecordDriverFactory::initRecordDriver($exhibitObject);

		global $interface;
		$facetName = urldecode($_REQUEST['facetName']);
		$interface->assign('exhibitPid', $pid);
		/** @var SearchObject_IslandoraSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setDebugging(false, false);
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();

		$collectionFilter = "ancestors_ms:\"$pid\"";
		$searchObject->addFilter($collectionFilter);
		$searchObject->clearFacets();
		$searchObject->addFacet($facetName);

		$searchObject->setLimit(1);

		$facetValues = array();
		$response = $searchObject->processSearch(true, false);
		if ($response && isset($response['error'])){
			$interface->assign('solrError', $response['error']['msg']);
			$interface->assign('solrLink', $searchObject->getFullSearchUrl());
		}
		if (isset($response['facet_counts']) && isset($response['facet_counts']['facet_fields'][$facetName])){
			$facetFieldData = $response['facet_counts']['facet_fields'][$facetName];
			foreach ($facetFieldData as $field){
				$entityDriver = RecordDriverFactory::initIslandoraDriverFromPid($field[0]);
				if (!($entityDriver instanceof AspenError) && $entityDriver != null){
					$facetValues[$entityDriver->getTitle()] = array(
							'display' => $entityDriver->getTitle(),
							'url' => $entityDriver->getRecordUrl(),
							'count' => $field[1]
					);
				}
			}
			ksort($facetValues);
		}

		$interface->assign('facetValues', $facetValues);
		$results = array(
				'modalBody' => $interface->fetch("Archive/browseFacetPopup.tpl"),
		);
		return $results;
	}

	/** @noinspection PhpUnused */
	function getFacetValuesForExhibit(){
		if (!isset($_REQUEST['id'])){
			return array(
					'success' => false,
					'message' => 'You must supply the id to load facet data for'
			);
		}
		if (!isset($_REQUEST['facetName'])){
			return array(
					'success' => false,
					'message' => 'You must supply the facetName to load facet data for'
			);
		}

		$pid = urldecode($_REQUEST['id']);

		//get a list of all collections and books within the main exhibit so we can find all related data.
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();
		$exhibitObject = $fedoraUtils->getObject($pid);
		RecordDriverFactory::initRecordDriver($exhibitObject);

		global $interface;
		$facetName = urldecode($_REQUEST['facetName']);
		$interface->assign('exhibitPid', $pid);
		/** @var SearchObject_IslandoraSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setDebugging(false, false);
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();

		$collectionFilter = "ancestors_ms:\"$pid\"";
		$searchObject->addFilter($collectionFilter);
		$searchObject->clearFacets();
		$searchObject->addFacet($facetName);

		$searchObject->setLimit(1);

		$facetValues = array();
		$response = $searchObject->processSearch(true, false);
		if ($response && isset($response['error'])){
			$interface->assign('solrError', $response['error']['msg']);
			$interface->assign('solrLink', $searchObject->getFullSearchUrl());
		}
		if (isset($response['facet_counts']) && isset($response['facet_counts']['facet_fields'][$facetName])){
			$facetFieldData = $response['facet_counts']['facet_fields'][$facetName];
			foreach ($facetFieldData as $field){
				$searchLink = $searchObject->renderLinkWithFilter($facetName, $field[0]);
				$facetValues[$field[0]] = array(
						'display' => $field[0],
						'url' => $searchLink,
						'count' => $field[1]
				);
			}
			ksort($facetValues);
		}

		$interface->assign('facetValues', $facetValues);
		$results = array(
				'modalBody' => $interface->fetch("Archive/browseFacetPopup.tpl"),
		);
		return $results;
	}

	/** @noinspection PhpUnused */
	public function getObjectInfo(){
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$pid = urldecode($_REQUEST['id']);
		$interface->assign('pid', $pid);
		$archiveObject = $fedoraUtils->getObject($pid);
		$recordDriver = RecordDriverFactory::initRecordDriver($archiveObject);
		$interface->assign('recordDriver', $recordDriver);

		$url = $recordDriver->getLinkUrl();
		$interface->assign('url', $url);
		$interface->assign('description', $recordDriver->getDescription());
		$interface->assign('image', $recordDriver->getBookcoverUrl('medium'));

		$urlStr = "<a href=\"$url\" onclick='AspenDiscovery.Archive.setForExhibitNavigation(" . $_COOKIE['recordIndex'] . ", " . $_COOKIE['page'] . ")'>";
		$escapedPid = urlencode($pid);
		$addToFavoritesLabel = translate('Add to list');
		$addToFavoritesButton = "<button onclick=\"return AspenDiscovery.Account.showSaveToListForm(this, 'archive', '$escapedPid');\" class=\"modal-buttons btn btn-primary\" style='float: left'>$addToFavoritesLabel</button>";
		return array(
			'title' => "{$urlStr}{$recordDriver->getTitle()}</a>",
			'modalBody' => $interface->fetch('Archive/archivePopup.tpl'),
			'modalButtons' => "$addToFavoritesButton{$urlStr}<button class='modal-buttons btn btn-primary'>" . translate("More Info") . "</button></a>"
		);
	}

	/** @noinspection PhpUnused */
	public function getMetadata(){
		global $interface;
		$id = urldecode($_REQUEST['id']);
		$interface->assign('pid', $id);

		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$archiveObject = $fedoraUtils->getObject($id);
		/** @var IslandoraRecordDriver $recordDriver */
		$recordDriver = RecordDriverFactory::initRecordDriver($archiveObject);
		$interface->assign('recordDriver', $recordDriver);

		$this->setMoreDetailsDisplayMode();
		//TODO: Not sure what this code blocks ending effect is to be
		if (array_key_exists('secondaryId', $_REQUEST)){
			$secondaryId = urldecode($_REQUEST['secondaryId']);

			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$fedoraUtils = FedoraUtils::getInstance();

			$secondaryObject = $fedoraUtils->getObject($secondaryId);
			/** @var IslandoraRecordDriver $secondaryDriver */
			$secondaryDriver = RecordDriverFactory::initRecordDriver($secondaryObject);

			$secondaryDriver->getMoreDetailsOptions();
		}
		$interface->assign('moreDetailsOptions', $recordDriver->getMoreDetailsOptions());


		$metadata = $interface->fetch('Archive/moredetails-accordion.tpl');
		return array(
				'success' => true,
				'metadata' => $metadata,
		);
	}

	private function setMoreDetailsDisplayMode(){
		// Set Display Mode for More Details Accordion Related Objects and Entities sections
		global $library, $interface;
		$displayMode = empty($library->archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode) ? 'tiled' : $library->archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode;
		$interface->assign('archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode', $displayMode);

	}

	/** @noinspection PhpUnused */
	public function getNextRandomObject(){
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$pids = explode(',', $_REQUEST['id']);
		$pidIndex = rand(0, count($pids) - 1);
		$pid = $pids[$pidIndex];

		$archiveObject = $fedoraUtils->getObject(trim($pid));
		/** @var IslandoraRecordDriver $recordDriver */
		$recordDriver = RecordDriverFactory::initRecordDriver($archiveObject);

		$randomImagePid = $recordDriver->getRandomObject();
		if ($randomImagePid != null){
			$randomObject = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($randomImagePid));
			$randomObjectInfo = array(
					'label' => $randomObject->getTitle(),
					'link' => $randomObject->getRecordUrl(),
					'image' => $randomObject->getBookcoverUrl('medium')
			);
			$interface->assign('randomObject', $randomObjectInfo);
			return array(
					'success' => true,
					'image' => $interface->fetch('Archive/randomImage.tpl')
			);
		}else{
			return array(
					'success' => false,
					'message' => 'No ID provided'
			);
		}
	}

	/** @noinspection PhpUnused */
	public function getTranscript(){
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];
		$transcriptIdentifier = urldecode($_REQUEST['transcriptId']);
		if (strlen($transcriptIdentifier) == 0){
			//Check to see if we can get it based on the
			return array(
					'success' => true,
					'transcript' => "There is no transcription available for this page.",
			);
		}elseif (strpos($transcriptIdentifier, 'mods:') === 0){
			$objectPid = str_replace('mods:', '', $transcriptIdentifier);
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$fedoraUtils = FedoraUtils::getInstance();
			$pageObject = $fedoraUtils->getObject($objectPid);
			$mods = $fedoraUtils->getModsData($pageObject);
			$transcript = $fedoraUtils->getModsValue('transcriptionText', 'marmot', $mods);
			$transcript = str_replace("\r\n", '<br/>', $transcript);
			$transcript = str_replace("&#xD;", '<br>', $transcript);
			if (strlen($transcript) > 0){
				return array(
						'success' => true,
						'transcript' => $transcript,
				);
			}
		}else{
			$transcriptUrl = $objectUrl . '/' . $transcriptIdentifier;
			$transcript = file_get_contents($transcriptUrl);

			if ($transcript) {
				return array(
						'success' => true,
						'transcript' => $transcript,
				);
			}
		}
		return array(
			'success' => false,
		);
	}

	/** @noinspection PhpUnused */
	public function getAdditionalRelatedObjects(){
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$pid = $_REQUEST['id'];
		$interface->assign('pid', $pid);
		$archiveObject = $fedoraUtils->getObject($pid);
		/** @var IslandoraRecordDriver $recordDriver */
		$recordDriver = RecordDriverFactory::initRecordDriver($archiveObject);
		$interface->assign('recordDriver', $recordDriver);
		$directlyRelatedObjects = $recordDriver->getDirectlyRelatedArchiveObjects();

		$interface->assign('directlyRelatedObjects', $directlyRelatedObjects);

		$this->setMoreDetailsDisplayMode();

		return array(
				'success' => true,
				'additionalObjects' => $interface->fetch('Archive/additionalRelatedObjects.tpl')
		);
	}

	/**
	 * @param SearchObject_IslandoraSearcher $searchObject
	 */
	public function setupTimelineFacetsAndFilters($searchObject)
	{
		if (isset($_REQUEST['dateFilter']) && $_REQUEST['dateFilter'] != 'all') {

			$filter = '';
			$date = $_REQUEST['dateFilter'];
			if ($date == 'before1880') {
				$filter .= "dateCreated:[* TO 1879-12-31T23:59:59Z]";
			} elseif ($date == 'unknown') {
				$filter .= '-dateCreated:[* TO *]';
			} else {
				$startYear = substr($date, 0, 4);
				$endYear = (int)$startYear + 9;
				$filter .= "dateCreated:[$date TO $endYear-12-31T23:59:59Z]";
			}

			if (strlen($filter)){
				$filter = trim($filter, '()');
				$searchObject->addFilter($filter);
			}

		}
		$searchObject->addFacet('dateCreated', 'Date Created');

		$searchObject->addFacetOptions(array(
				'facet.range' => array('dateCreated'),
				'f.dateCreated.facet.missing' => 'true',
				'f.dateCreated.facet.range.start' => '1880-01-01T00:00:00Z',
				'f.dateCreated.facet.range.end' => 'NOW/YEAR',
				'f.dateCreated.facet.range.hardend' => 'true',
				'f.dateCreated.facet.range.gap' => '+10YEAR',
				'f.dateCreated.facet.range.other' => 'all',
		));
	}

	/**
	 * @param $sort
	 * @param SearchObject_IslandoraSearcher $searchObject
	 */
	public function setupTimelineSorts($sort, $searchObject)
	{
		if ($sort == 'title') {
			$searchObject->setSort('fgs_label_s');
		} elseif ($sort == 'newest') {
			$searchObject->setSort('mods_originInfo_qualifier__dateIssued_dt desc,dateCreated desc,fgs_label_s asc');
		} elseif ($sort == 'oldest') {
			$searchObject->setSort('mods_originInfo_qualifier__dateIssued_dt asc,dateCreated asc,fgs_label_s asc');
		} elseif ($sort == 'dateAdded') {
			$searchObject->setSort('fgs_createdDate_dt desc,fgs_label_s asc');
		} elseif ($sort == 'dateModified') {
			$searchObject->setSort('fgs_lastModifiedDate_dt desc,fgs_label_s asc');
		}
	}

	/**
	 * @param $response
	 * @param $interface
	 */
	public function processTimelineData($response, $interface)
	{
		if (isset($response['facet_counts']) && count($response['facet_counts']['facet_ranges']) > 0) {
			$dateFacetInfo = array();
			if (isset($response['facet_counts']['facet_ranges']['dateCreated'])) {
				$dateCreatedInfo = $response['facet_counts']['facet_ranges']['dateCreated'];
				if ($dateCreatedInfo['before'] > 0) {
					$dateFacetInfo['1870'] = array(
							'label' => 'Before 1880',
							'count' => $dateCreatedInfo['before'],
							'value' => 'before1880'
					);
				}
				foreach ($dateCreatedInfo['counts'] as $facetInfo) {
					$dateFacetInfo[substr($facetInfo[0], 0, 4) . '\'s'] = array(
							'label' => substr($facetInfo[0], 0, 4) . '\'s',
							'count' => $facetInfo[1],
							'value' => $facetInfo[0]
					);
				}
			}

			//Figure out how many unknown dates there are
			$totalFound = 0;
			foreach($dateFacetInfo as $dateFacet){
				$totalFound += $dateFacet['count'];
			}
			$numUnknown = $response['response']['numFound'] - $totalFound;
			/*if ($numUnknown > 0){
				$dateFacetInfo['Unknown'] = array(
						'label' => 'Unknown',
						'count' => $numUnknown,
						'value' => 'unknown'
				);
			}*/
			$interface->assign('numObjectsWithUnknownDate', $numUnknown);

			ksort($dateFacetInfo);

			$interface->assign('dateFacetInfo', $dateFacetInfo);
		}
	}

	private function setCoversDisplayMode()
	{
		global $interface,
		$library;
		if (!empty($_REQUEST['archiveCollectionView'])) {
			$displayMode = $_REQUEST['archiveCollectionView'];
		} elseif (!empty($_SESSION['archiveCollectionDisplayMode'])) {
			$displayMode = $_SESSION['archiveCollectionDisplayMode'];
		} elseif (!empty($library->defaultArchiveCollectionBrowseMode)) {
			$displayMode = $library->defaultArchiveCollectionBrowseMode;
		} else {
			$displayMode = 'covers'; // default mode is covers
		}

		$_SESSION['archiveCollectionDisplayMode'] = $displayMode;
		$interface->assign('displayMode', $displayMode);
		return $displayMode;
	}

	/** @noinspection PhpUnused */
	public function clearCache(){
		if (!isset($_REQUEST['id'])){
			return array(
					'success' => false,
					'message' => 'You must supply the id to clear cached data for.'
			);
		}
		$id = $_REQUEST['id'];

		$mainCacheCleared = false;
		$cacheMessage = '';
		require_once ROOT_DIR . '/sys/Islandora/IslandoraObjectCache.php';
		$objectCache = new IslandoraObjectCache();
		$objectCache->pid = $id;
		if ($objectCache->find(true)){
			if ($objectCache->delete()){
				global $memCache;
				$memCache->delete('islandora_object_valid_in_pika_' . $id);
				$mainCacheCleared = true;

			}else{
				$cacheMessage = 'Could not delete cached data.<br/>';
			}
		}else{
			$mainCacheCleared = true;
			$cacheMessage = 'Cached data does not exist for that id.<br/>';
		}

		$sameRecordCleared = false;
		require_once ROOT_DIR . '/sys/Islandora/IslandoraSamePikaCache.php';
		//Check for cached links
		$sameCatalogRecordCache = new IslandoraSamePikaCache();
		$sameCatalogRecordCache->pid = $id;
		if ($sameCatalogRecordCache->find(true)){
			if ($sameCatalogRecordCache->delete()){
				$sameRecordCleared = true;
			}else{
				$cacheMessage .= 'Could not delete same catalog record cache';
			}

		}else{
			$cacheMessage .= 'Data not cached for same catalog link';
		}

		return array(
				'success' => $mainCacheCleared || $sameRecordCleared,
				'message' => $cacheMessage
		);
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}
