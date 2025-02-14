<?php

/**
 * RecordDriverFactory Class
 *
 * This is a factory class to build record drivers for accessing metadata.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class RecordDriverFactory {
	/**
	 * initSearchObject
	 *
	 * This constructs a search object for the specified engine.
	 *
	 * @access  public
	 * @param   array|AbstractFedoraObject   $record     The fields retrieved from the Solr index.
	 * @return  RecordInterface     The record driver for handling the record.
	 */
	static function initRecordDriver($record)
	{
		global $configArray;
		global $timer;

		$path = '';

		$timer->logTime("Starting to load record driver");

		// Determine driver path based on record type:
		if (is_object($record) && $record instanceof AbstractFedoraObject){
			return self::initIslandoraDriverFromObject($record);

		}elseif (is_array($record) && !array_key_exists('recordtype', $record)){
			require_once ROOT_DIR . '/sys/Islandora/IslandoraObjectCache.php';
			$islandoraObjectCache = new IslandoraObjectCache();
			$islandoraObjectCache->pid = $record['PID'];
			$hasExistingCache = false;
			$driver = '';
			if ($islandoraObjectCache->find(true) && !isset($_REQUEST['reload'])){
				$driver = $islandoraObjectCache->driverName;
				$path = $islandoraObjectCache->driverPath;
				$hasExistingCache = true;
			}
			if (empty($driver)){
				if (!isset($record['RELS_EXT_hasModel_uri_s'])){
					//print_r($record);
					AspenError::raiseError('Unable to load Driver for ' . $record['PID'] . " model did not exist");
				}
				$recordType = $record['RELS_EXT_hasModel_uri_s'];
				//Get rid of islandora namespace information
				$recordType = str_replace(array(
						'info:fedora/islandora:', 'sp_', 'sp-', '_cmodel', 'CModel',
				), '', $recordType);

				$driverNameParts = explode('_', $recordType);
				$normalizedRecordType = '';
				foreach ($driverNameParts as $driverPart) {
					$normalizedRecordType .= (ucfirst($driverPart));
				}

				if ($normalizedRecordType == 'Compound'){
					$genre = isset($record['mods_genre_s']) ? $record['mods_genre_s'] : null;
					if ($genre != null){
						$normalizedRecordType = ucfirst($genre);
						$normalizedRecordType = str_replace(' ', '', $normalizedRecordType);

						$driver = $normalizedRecordType . 'Driver';
						$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
						if (!is_readable($path)) {
							//print_r($record);
							$normalizedRecordType = 'Compound';
						}
					}
				}

				$driver = $normalizedRecordType . 'Driver';
				$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";

				// If we can't load the driver, fall back to the default, index-based one:
				if (!is_readable($path)) {
					//print_r($record);
					AspenError::raiseError('Unable to load Driver for ' . $recordType . " ($normalizedRecordType)");
				}else{
					if (!$hasExistingCache){
						$islandoraObjectCache = new IslandoraObjectCache();
						$islandoraObjectCache->pid = $record['PID'];
					}
					$islandoraObjectCache->driverName = $driver;
					$islandoraObjectCache->driverPath = $path;
					$islandoraObjectCache->title = $record['fgs_label_s'];
					if (!$hasExistingCache) {
						$islandoraObjectCache->insert();
					}else{
						$islandoraObjectCache->update();
					}
				}
			}
			$timer->logTime("Found Driver for archive object from solr doc {$record['PID']} " . $driver);
		}else{
			$driver = ucwords($record['recordtype']) . 'Record';
			$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
			// If we can't load the driver, fall back to the default, index-based one:
			if (!is_readable($path)) {
				//Try without appending Record
				$recordType = $record['recordtype'];
				$driverNameParts = explode('_', $recordType);
				$recordType = '';
				foreach ($driverNameParts as $driverPart){
					$recordType .= (ucfirst($driverPart));
				}

				$driver = $recordType . 'Driver' ;
				$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";

				// If we can't load the driver, fall back to the default, index-based one:
				if (!is_readable($path)) {

					$driver = 'IndexRecordDriver';
					$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
				}
			}
		}

		return self::initAndReturnDriver($record, $driver, $path);
	}

	static $recordDrivers = array();
	/**
	 * @param $id
	 * @param  GroupedWork $groupedWork;
	 * @return ExternalEContentDriver|MarcRecordDriver|null|OverDriveRecordDriver
	 */
	static function initRecordDriverById($id, $groupedWork = null){
		global $configArray;
		if (isset(RecordDriverFactory::$recordDrivers[$id])){
			return RecordDriverFactory::$recordDrivers[$id];
		}
		if (strpos($id, ':') !== false){
			$recordInfo = explode(':', $id, 2);
			$recordType = $recordInfo[0];
			$recordId = $recordInfo[1];
		}else{
			$recordType = 'ils';
			$recordId = $id;
		}

		disableErrorHandler();
		if ($recordType == 'overdrive'){
			require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
			$recordDriver = new OverDriveRecordDriver($recordId, $groupedWork);
		} elseif ($recordType == 'axis360') {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($recordId, $groupedWork);
		} elseif ($recordType == 'rbdigital') {
			require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
			$recordDriver = new RBdigitalRecordDriver($recordId, $groupedWork);
		}elseif ($recordType == 'rbdigital_magazine'){
			require_once ROOT_DIR . '/RecordDrivers/RBdigitalMagazineDriver.php';
			$recordDriver = new RBdigitalMagazineDriver($recordId, $groupedWork);
		}elseif ($recordType == 'cloud_library'){
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$recordDriver = new CloudLibraryRecordDriver($recordId, $groupedWork);
		}elseif ($recordType == 'external_econtent'){
			require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';
			$recordDriver = new ExternalEContentDriver($recordId, $groupedWork);
		}elseif ($recordType == 'hoopla'){
			require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
			$recordDriver = new HooplaRecordDriver($recordId, $groupedWork);
			if (!$recordDriver->isValid()){
				global $logger;
				$logger->log("Unable to load record driver for hoopla record $recordId", Logger::LOG_WARNING);
				$recordDriver = null;
			}
		}elseif ($recordType == 'open_archives'){
			require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
			$recordDriver = new OpenArchivesRecordDriver($recordId);
		}else{
			global $indexingProfiles;
			global $sideLoadSettings;

			if (array_key_exists($recordType, $indexingProfiles)) {
				$indexingProfile = $indexingProfiles[$recordType];
				$driverName = $indexingProfile->recordDriver;
				$driverPath = ROOT_DIR . "/RecordDrivers/{$driverName}.php";
				require_once $driverPath;
				$recordDriver = new $driverName($id, $groupedWork);
			}else if (array_key_exists($recordType, $sideLoadSettings)){
				$indexingProfile = $sideLoadSettings[$recordType];
				$driverName = $indexingProfile->recordDriver;
				$driverPath = ROOT_DIR . "/RecordDrivers/{$driverName}.php";
				require_once $driverPath;
				$recordDriver = new $driverName($id, $groupedWork);
			}else{
				//Check to see if this is an object from the archive
				$driverNameParts = explode('_', $recordType);
				$normalizedRecordType = '';
				foreach ($driverNameParts as $driverPart){
					$normalizedRecordType .= (ucfirst($driverPart));
				}
				$driver = $normalizedRecordType . 'Driver' ;
				$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";

				// If we can't load the driver, fall back to the default, index-based one:
				if (!is_readable($path)) {
					global $logger;
					$logger->log("Unknown record type " . $recordType, Logger::LOG_ERROR);
					$recordDriver = null;
				}else{
					require_once $path;
					if (class_exists($driver)) {
						disableErrorHandler();
						$obj = new $driver($id);
						if (($obj instanceof AspenError)){
							global $logger;
							$logger->log("Error loading record driver", Logger::LOG_DEBUG);
						}
						enableErrorHandler();
						return $obj;
					}
				}


			}
		}
		enableErrorHandler();
		RecordDriverFactory::$recordDrivers[$id] = $recordDriver;
		return $recordDriver;
	}

	/**
	 * @param AbstractFedoraObject $record
	 * @return AspenError|RecordInterface
	 */
	public static function initIslandoraDriverFromObject($record)
	{
		global $configArray;
		global $timer;

		if ($record == null){
			return null;
		}

		require_once ROOT_DIR . '/sys/Islandora/IslandoraObjectCache.php';
		$islandoraObjectCache = new IslandoraObjectCache();
		$islandoraObjectCache->pid = $record->id;
		if ($islandoraObjectCache->find(true) && !isset($_REQUEST['reload'])) {
			$driver = $islandoraObjectCache->driverName;
			$path = $islandoraObjectCache->driverPath;
		} else {
			$models = $record->models;
			$timer->logTime("Loaded models for object");
			foreach ($models as $model) {
				$recordType = $model;
				//Get rid of islandora namespace information
				$recordType = str_replace(array(
						'info:fedora/islandora:', 'sp_', 'sp-', '_cmodel', 'CModel', 'islandora:',
				), '', $recordType);

				$driverNameParts = explode('_', $recordType);
				$normalizedRecordType = '';
				foreach ($driverNameParts as $driverPart) {
					$normalizedRecordType .= (ucfirst($driverPart));
				}

				if ($normalizedRecordType == 'Compound') {
					$genre = isset($record['mods_genre_s']) ? $record['mods_genre_s'] : null;
					if ($genre != null) {
						$normalizedRecordType = ucfirst($genre);
						$driver = $normalizedRecordType . 'Driver';
						$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
						if (!is_readable($path)) {
							//print_r($record);
							$normalizedRecordType = 'Compound';
						}
					}
				}
				$driver = $normalizedRecordType . 'Driver';
				$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";

				// If we can't load the driver, fall back to the default, index-based one:
				if (!is_readable($path)) {
					//print_r($record);
					AspenError::raiseError('Unable to load Driver for ' . $recordType . " ($normalizedRecordType)");
				} else {
					$islandoraObjectCache = new IslandoraObjectCache();
					$islandoraObjectCache->pid = $record->id;
					$islandoraObjectCache->driverName = $driver;
					$islandoraObjectCache->driverPath = $path;
					$islandoraObjectCache->title = $record->label;
					$islandoraObjectCache->insert();
					break;
				}
			}
			$timer->logTime('Found Driver for archive object ' . $driver);

		}
		return self::initAndReturnDriver($record, $driver, $path);
	}

	/**
	 * @param string $record
	 * @return AspenError|RecordInterface
	 */
	public static function initIslandoraDriverFromPid($record)
	{
		require_once ROOT_DIR . '/sys/Islandora/IslandoraObjectCache.php';
		$islandoraObjectCache = new IslandoraObjectCache();
		$islandoraObjectCache->pid = $record;
		if ($islandoraObjectCache->find(true) && !isset($_REQUEST['reload'])) {
			$driver = $islandoraObjectCache->driverName;
			$path = $islandoraObjectCache->driverPath;
			return self::initAndReturnDriver($record, $driver, $path);
		} else {
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$fedoraUtils = FedoraUtils::getInstance();
			$islandoraObject = $fedoraUtils->getObject($record);
			return self::initIslandoraDriverFromObject($islandoraObject);
		}
	}

	/**
	 * @param $record
	 * @param $path
	 * @param $driver
	 * @return AspenError|RecordInterface
	 */
	public static function initAndReturnDriver($record, $driver, $path)
	{
		global $timer;
		global $logger;
		global $memoryWatcher;

		// Build the object:
		if ($path) {
			require_once $path;
			if (class_exists($driver)) {
				$timer->logTime("Error loading record driver");
				disableErrorHandler();
				/** @var RecordInterface $obj */
				$obj = new $driver($record);
				$timer->logTime("Initialized Driver");
				if (($obj instanceof AspenError)) {
					$logger->log("Error loading record driver", Logger::LOG_DEBUG);
				}
				enableErrorHandler();
				$timer->logTime('Loaded record driver for ' . $obj->getUniqueID());

				$memoryWatcher->logMemory("Created record driver for {$obj->getUniqueID()}");
				return $obj;
			}
		}

		// If we got here, something went very wrong:
		return new AspenError("Problem loading record driver: {$driver}");
	}


}