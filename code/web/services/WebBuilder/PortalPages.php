<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';

class WebBuilder_PortalPages extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'PortalPage';
	}

	function getToolName() : string
	{
		return 'PortalPages';
	}

	function getModule() : string
	{
		return 'WebBuilder';
	}

	function getPageTitle() : string
	{
		return 'WebBuilder Custom Pages';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new PortalPage();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer All Custom Pages')){
			$userHasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryPortalPage', 'portalPageId');
		}
		$objectList = array();
		if ($userHasExistingObjects) {
			$object->find();
			while ($object->fetch()) {
				$objectList[$object->id] = clone $object;
			}
		}
		return $objectList;
	}

	function getDefaultSort() : string
	{
		return 'title asc';
	}

	function getObjectStructure() : array
	{
		return PortalPage::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function getAdditionalObjectActions($existingObject) : array
	{
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof PortalPage && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/PortalPage?id='.$existingObject->id: $existingObject->urlAlias,
			];
		}
		return $objectActions;
	}

	function getInstructions() : string
	{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/PortalPages', 'Custom Pages');
		return $breadcrumbs;
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Custom Pages', 'Administer Library Custom Pages']);
	}

	function getActiveAdminSection() : string
	{
		return 'web_builder';
	}
}