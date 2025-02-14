<?php

// Abstract Base Class for Actions
require_once ROOT_DIR . '/sys/Breadcrumb.php';
abstract class Action
{
	private $isStandalonePage;
	function __construct($isStandalonePage = false) {
		$this->isStandalonePage = $isStandalonePage;
		global $interface;
		if ($interface) {
			$interface->assign('isStandalonePage', $isStandalonePage);
		}
	}

	abstract function launch();

	/**
	 * @param string $mainContentTemplate Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle What to display is the html title tag
	 * @param string $sidebarTemplate Sets the sidebar template, set to false or empty string for no sidebar
	 * @param boolean $translateTitle
	 */
	function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Search/home-sidebar.tpl', $translateTitle = true) {
		global $interface;
		if (!empty($sidebarTemplate)) $interface->assign('sidebar', $sidebarTemplate);
		$interface->assign('breadcrumbs', $this->getBreadcrumbs());
		$interface->setTemplate($mainContentTemplate);
		$interface->setPageTitle($pageTitle, $translateTitle);
		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
		global $isAJAX;
		if (!$isAJAX && UserAccount::isLoggedIn()){
			try {
				$messages = UserAccount::getActiveUserObj()->getMessages();
				$interface->assign('messages', $messages);
			}catch (Exception $e){
				//Messages table doesn't exist, ignore
			}
		}
		if ($this->isStandalonePage){
			$interface->display('standalone-layout.tpl');
		}else {
			$interface->display('layout.tpl');
		}
	}

	function setShowCovers() {
		global $interface;
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$showCovers = true;
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			if (isset($_SESSION)) $_SESSION['showCovers'] = $showCovers;
		} elseif (isset($_SESSION['showCovers'])) {
			$showCovers = $_SESSION['showCovers'];
		}
		$interface->assign('showCovers', $showCovers);
	}

	protected function forbidAPIAccess()
	{
		global $aspenUsage;
		$aspenUsage->blockedApiRequests++;
		$aspenUsage->update();
		global $usageByIPAddress;
		try{
			$usageByIPAddress->numBlockedApiRequests++;
			$usageByIPAddress->update();
		} catch (Exception $e) {
			//Table does not exist yet
		}

		http_response_code(403);
		$clientIP = IPAddress::getClientIP();
		echo("<h1>Forbidden</h1><p><strong>API requests from {$clientIP} are forbidden.</strong></p>");
		die();
	}

	abstract function getBreadcrumbs() : array;
}