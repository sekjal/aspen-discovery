<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . "/sys/MaterialsRequest.php";

/**
 * MaterialsRequest Home Page, displays an existing Materials Request.
 */
class MaterialsRequest_NewRequest extends Action
{

	function launch()
	{
		global $configArray;
		global $interface;
		global $library;
		global $locationSingleton;

		if (!UserAccount::isLoggedIn()) {
			header('Location: /MyAccount/Home?followupModule=MaterialsRequest&followupAction=NewRequest');
			exit;
		} else {
			// Hold Pick-up Locations
			$user = UserAccount::getActiveUserObj();
			$locations = $locationSingleton->getPickupBranches($user);

			$pickupLocations = array();
			foreach ($locations as $curLocation) {
				if (is_object($curLocation)) {
					$pickupLocations[] = array(
						'id' => $curLocation->locationId,
						'displayName' => $curLocation->displayName,
						'selected' => is_object($curLocation) ? ($curLocation->locationId == $user->pickupLocationId ? 'selected' : '') : '',
					);
				}
			}
			$interface->assign('pickupLocations', $pickupLocations);

			//Get a list of formats to show
			$availableFormats = MaterialsRequest::getFormats();
			$interface->assign('availableFormats', $availableFormats);

			//Setup a default title based on the search term
			$interface->assign('new', true);
			$request                         = new MaterialsRequest();
			$request->placeHoldWhenAvailable = true; // set the place hold option on by default
			$request->illItem                = true; // set the place hold option on by default
			if (isset($_REQUEST['lookfor']) && strlen($_REQUEST['lookfor']) > 0) {
				$searchType = isset($_REQUEST['searchIndex']) ? $_REQUEST['searchIndex'] : 'Keyword';
				if (strcasecmp($searchType, 'author') == 0) {
					$request->author = $_REQUEST['lookfor'];
				} else {
					$request->title = $_REQUEST['lookfor'];
				}
			}

			$user = UserAccount::getActiveUserObj();
			if ($user) {
				$request->phone = str_replace(array('### TEXT ONLY ', '### TEXT ONLY'), '', $user->phone);
				if ($user->email != 'notice@salidalibrary.org') {
					$request->email = $user->email;
				}
			}

			$interface->assign('materialsRequest', $request);

			$interface->assign('showEbookFormatField', $configArray['MaterialsRequest']['showEbookFormatField']);
//			$interface->assign('showEaudioFormatField', $configArray['MaterialsRequest']['showEaudioFormatField']);
			$interface->assign('requireAboutField', $configArray['MaterialsRequest']['requireAboutField']);

			$useWorldCat = false;
			if (isset($configArray['WorldCat']) && isset($configArray['WorldCat']['apiKey'])) {
				$useWorldCat = strlen($configArray['WorldCat']['apiKey']) > 0;
			}
			$interface->assign('useWorldCat', $useWorldCat);

			// Get the Fields to Display for the form
			$requestFormFields = $request->getRequestFormFields($library->libraryId);
			$interface->assign('requestFormFields', $requestFormFields);

			// Add bookmobile Stop to the pickup locations if that form field is being used.
			foreach ($requestFormFields as $category) {
				/** @var MaterialsRequestFormFields $formField */
				foreach ($category as $formField) {
					if ($formField->fieldType == 'bookmobileStop') {
						$pickupLocations[] = array(
							'id' => 'bookmobile',
							'displayName' => $formField->fieldLabel,
							'selected' => false,
						);
						$interface->assign('pickupLocations', $pickupLocations);
						break 2;
					}
				}
			}

			// Get Author Labels for all Formats and Formats that use Special Fields
			list($formatAuthorLabels, $specialFieldFormats) = $request->getAuthorLabelsAndSpecialFields($library->libraryId);

			$interface->assign('formatAuthorLabelsJSON', json_encode($formatAuthorLabels));
			$interface->assign('specialFieldFormatsJSON', json_encode($specialFieldFormats));

			// Set up for User Log in
			$interface->assign('newMaterialsRequestSummary', $library->newMaterialsRequestSummary);

			$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
			$interface->assign('selfRegistrationUrl', $library->selfRegistrationUrl);
			$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
			$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');

			$this->display('new.tpl', 'Materials Request');
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'New Materials Request');
		return $breadcrumbs;
	}
}