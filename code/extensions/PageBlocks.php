<?php
/**
 * Extension that applies blocks to a page.
 * 
 * @author bummzack
 */
class PageBlocks extends DataExtension
{
	private static $has_many = array(
		'Blocks' => 'Block'
	);
	
	/**
	 * Whether or not the page should have a button to publish page & blocks with one click.
	 * Set this via config.yml
	 * @var bool
	 */
	private static $allow_publish_all = true;
	
	public function updateCMSFields(FieldList $fields) {
		$gridConfig = singleton('Block')->has_extension('Sortable') 
			? GridFieldConfig_BlockEditor::create('SortOrder')
			: GridFieldConfig_BlockEditor::create();
		
		$gridField = new GridField('Blocks', 'Block', $this->owner->Blocks(), $gridConfig);
		$gridField->setModelClass('Block');
		
		$fields->addFieldsToTab('Root.Main', array(
			$gridField
		), 'Metadata');
	}
	
	/**
	 * Add the publish all button
	 */
	public function updateCMSActions(FieldList $actions) {
		if(Config::inst()->get('PageBlocks', 'allow_publish_all') == false){
			return;
		}
		$button = FormAction::create('publishblocks', 'Publish Page & Blocks')->setAttribute('data-icon', 'accept');
		if($majorActions = $actions->fieldByName('MajorActions')){
			$majorActions->push($button);
		} else {
			$actions->push($button);
		}
	}
}


/**
 * GridFieldConfig that supplies the config needed to edit content blocks within a page
 */
class GridFieldConfig_BlockEditor extends GridFieldConfig_RelationEditor {

	/**
	 * @param string $sortField - Field to sort the blocks on. If this is set, it will also make the
	 * 	blocks sortable in the CMS (requires SortableGridField module!)
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($sortField = null, $itemsPerPage=null) {
		parent::__construct($itemsPerPage);
		
		// setup a bulk manager for block management
		$bulkManager = new GridFieldBulkManager();
		// remove the default actions
		$bulkManager->removeBulkAction('bulkedit')->removeBulkAction('delete')->removeBulkAction('unlink');
		// add the actions in desired order
		$bulkManager
			->addBulkAction('publish')
			->addBulkAction('unpublish')
			->addBulkAction('bulkedit', 'Edit', 'GridFieldBulkActionEditHandler')
			->addBulkAction('versioneddelete', 'Delete', 'GridFieldBulkActionVersionedDeleteHandler');
		
		if($sortField && class_exists('GridFieldSortableRows')){
			$this->addComponent(new GridFieldSortableRows('SortOrder'));
		}
		
		// remove the delete action, since unlinking is not required
		$this->removeComponent($this->getComponentByType('GridFieldDeleteAction'));
		
		$this->addComponent(new GridFieldAddNewMultiClass(), 'GridFieldToolbarHeader');
		$this->addComponent($bulkManager);
		$this->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
			'Title' => 'Title',
			'ClassName' => 'Type',
			'PublishedStatus' => 'Status'
		));
	}
}