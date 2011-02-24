<?php
/**
 * A queued job that generates a site export page by page.
 *
 * @package silverstripe-queuedjobs
 */
class SiteExportJob extends AbstractQueuedJob {

	public function __construct($root = null) {
		if ($root) {
			$this->rootClass = $root->class;
			$this->rootId    = $root->ID;
		}
	}

	public function getTitle() {
		return 'Generate Site Export Job';
	}

	/**
	 * @return DataObject
	 */
	public function getRoot() {
		return DataObject::get_by_id($this->rootClass, $this->rootId);
	}

	public function setup() {
		if (($root = $this->getRoot()) instanceof SiteTree) {
			$this->idsToProcess = $root->getDescendantIDList();
		} else {
			$this->idsToProcess = DB::query('SELECT "ID" FROM "SiteTree"')->column();
		}

		$this->tempDir = TEMP_FOLDER . '/siteexport-' . $this->getSignature();
		mkdir($this->tempDir);

		$this->totalSteps = count($this->idsToProcess);
	}

	public function process() {
		if (!$remaining = $this->idsToProcess) {
			$this->complete();
			$this->isComplete = true;

			return;
		}

		if ($page = DataObject::get_by_id('SiteTree', array_shift($remaining))) {
			$exporter = new SiteExporter();
			$exporter->customLinks  = array($page->Link());
			$exporter->root         = $this->getRoot();
			$exporter->theme        = $this->theme;
			$exporter->baseUrl      = $this->baseUrl;
			$exporter->makeRelative = $this->baseUrlType == 'rewrite';
			$exporter->exportTo($this->tempDir);
		}

		$this->currentStep++;
		$this->idsToProcess = $remaining;

		if (!$remaining) {
			$this->complete();
			$this->isComplete = true;
		}
	}

	/**
	 * Completes the job by zipping up the generated export and creating an
	 * export record for it.
	 */
	protected function complete() {
		$siteTitle = SiteConfig::current_site_config()->Title;

		$filename = preg_replace('/[^a-zA-Z0-9-.+]/', '-', sprintf(
			'%s-%s.zip', $siteTitle, date('c')
		));

		$dir      = Folder::findOrMake(SiteExportExtension::EXPORTS_DIR);
		$dirname  = ASSETS_PATH . '/' . SiteExportExtension::EXPORTS_DIR;
		$pathname = "$dirname/$filename";

		SiteExportUtils::zip_directory($this->tempDir, "$dirname/$filename");

		$file = new File();
		$file->ParentID = $dir->ID;
		$file->Title    = $siteTitle . ' ' . date('c');
		$file->Filename = $dir->Filename . $filename;
		$file->write();

		$export = new SiteExport();
		$export->ParentClass = $this->rootClass;
		$export->ParentID    = $this->rootId;
		$export->Theme       = $this->theme;
		$export->BaseUrlType = ucfirst($this->baseUrlType);
		$export->BaseUrl     = $this->baseUrl;
		$export->ArchiveID   = $file->ID;
		$export->write();
	}

}