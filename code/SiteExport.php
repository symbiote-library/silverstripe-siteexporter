<?php
/**
 * @package silverstripe-siteexporter
 */
class SiteExport extends DataObject {

	public static $db = array(
		'Theme'       => 'Varchar',
		'BaseUrlType' => 'Enum("Fixed, Rewrite")',
		'BaseUrl'     => 'Varchar(255)',
		'ParentClass' => 'Varchar',
		'ParentID'    => 'Int'
	);

	public static $has_one = array(
		'Archive'  => 'File'
	);

	public static $default_sort = '"ID" DESC';

	public static $summary_fields = array(
		'Created'      => 'Created',
		'Theme'        => 'Theme',
		'BaseUrlType'  => 'Base URL Type',
		'BaseUrl'      => 'Base URL',
		'Archive.Name' => 'File'
	);

	/**
	 * @return DataObject
	 */
	public function getParent() {
		return DataObject::get_by_id($this->ParentClass, $this->ParentID);
	}

	protected function onBeforeWrite() {
		if ($this->isChanged('ParentClass')) {
			$this->ParentClass = ClassInfo::baseDataClass($this->ParentClass);
		}

		parent::onBeforeWrite();
	}

	protected function onBeforeDelete() {
		if ($this->ArchiveID && $archive = $this->Archive()) {
			$archive->delete();
		}

		parent::onBeforeDelete();
	}

}