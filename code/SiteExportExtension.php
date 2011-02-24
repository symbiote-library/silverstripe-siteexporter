<?php
/**
 * Adds the ability to export the site to the main CMS panel.
 *
 * @package silverstripe-siteexport
 */
class SiteExportExtension extends Extension {

	const EXPORTS_DIR = 'SiteExports';

	public static $allowed_actions = array(
		'doExport'
	);

	public function updateEditForm(Form $form) {
		if (($record = $form->getRecord()) instanceof SiteTree) {
			if (!$record->numChildren()) return;
		}

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('siteexporter/javascript/SiteExportAdmin.js');

		$action = new FormAction('doExport');
		$action->useButtonTag = true;
		$action->setButtonContent('Export');

		$exports = new TableListField('SiteExports', 'SiteExport', null, sprintf(
			'"ParentClass" = \'%s\' AND "ParentID" =  %d',
			ClassInfo::baseDataClass($form->getRecord()->class),
			$form->getRecord()->ID
		));
		$exports->setFieldFormatting(array(
			'Archive.Name' => '<a href=\"{$Archive()->Link()}\">{$Archive()->Name}</a>'
		));
		$exports->setForm($form);

		$fields = array(
			new HeaderField(
				'ExportSiteHeader',
				'Export Site As Zip'),
			new OptionSetField(
				'ExportSiteBaseUrlType',
				'Base URL type',
				array(
					'fixed'   => 'Set the site base URL to a fixed value',
					'rewrite' => 'Attempt to rewrite URLs to be relative'
				),
				'fixed'),
			new TextField(
				'ExportSiteBaseUrl',
				'Base URL',
				Director::absoluteBaseURL()),
			new DropdownField(
				'ExportSiteTheme',
				'Theme',
				SiteConfig::current_site_config()->getAvailableThemes(),
				null, null,
				'(Use default theme)'),
			$action,
			new HeaderField(
				'SiteExportsHeader',
				'Site Exports'),
			$exports
		);
		$form->Fields()->addFieldsToTab('Root.Export', $fields);

		if (class_exists('QueuedJobService')) {
			$form->Fields()->addFieldToTab(
				'Root.Export',
				new LiteralField('ExporSiteQueuedNote', '<p>The site export'
					. ' will not be performed immediately, but will be '
					. ' processed in the background as a queued job. You can'
					. ' enter an email address below to send a notification'
					. ' email to when the job is complete.</p>'),
				'action_doExport'
			);

			$email = new EmailField('ExportSiteCompleteEmail', 'Email address'
				. ' to send a notification to when the export is finished');
			$email->setForm($form);
			$form->Fields()->addFieldToTab(
				'Root.Export', $email, 'action_doExport'
			);
		}
	}

	public function doExport($data, $form) {
		$data      = $form->getData();
		$links     = array();
		$siteTitle = SiteConfig::current_site_config()->Title;

		// If the queued jobs module is installed, then queue up an export
		// job rather than performing the export.
		if (class_exists('QueuedJobService')) {
			$job = new SiteExportJob($form->getRecord());
			$job->theme       = $data['ExportSiteTheme'];
			$job->baseUrl     = $data['ExportSiteBaseUrl'];
			$job->baseUrlType = $data['ExportSiteBaseUrlType'];
			$job->email       = $data['ExportSiteCompleteEmail'];
			singleton('QueuedJobService')->queueJob($job);

			return new SS_HTTPResponse(
				$form->dataFieldByName('SiteExports')->FieldHolder(),
				200,
				'The site export job has been queued.');
		}

		// First generate a temp directory to store the export content in.
		$temp  = TEMP_FOLDER;
		$temp .= sprintf('/siteexport_%s', date('Y-m-d-His'));
		mkdir($temp);

		$exporter = new SiteExporter();
		$exporter->root         = $form->getRecord();
		$exporter->theme        = $data['ExportSiteTheme'];
		$exporter->baseUrl      = $data['ExportSiteBaseUrl'];
		$exporter->makeRelative = $data['ExportSiteBaseUrlType'] == 'rewrite';
		$exporter->exportTo($temp);

		// Then place the exported content into an archive, stored in the assets
		// root, and create a site export for it.
		$filename = preg_replace('/[^a-zA-Z0-9-.+]/', '-', sprintf(
			'%s-%s.zip', $siteTitle, date('c')
		));

		$dir      = Folder::findOrMake(self::EXPORTS_DIR);
		$dirname  = ASSETS_PATH . '/' . self::EXPORTS_DIR;
		$pathname = "$dirname/$filename";

		SiteExportUtils::zip_directory($temp, "$dirname/$filename");

		$file = new File();
		$file->ParentID = $dir->ID;
		$file->Title    = $siteTitle . ' ' . date('c');
		$file->Filename = $dir->Filename . $filename;
		$file->write();

		$export = new SiteExport();
		$export->ParentClass = $form->getRecord()->class;
		$export->ParentID    = $form->getRecord()->ID;
		$export->Theme       = SSViewer::current_theme();
		$export->BaseUrlType = ucfirst($data['ExportSiteBaseUrlType']);
		$export->BaseUrl     = $data['ExportSiteBaseUrl'];
		$export->ArchiveID   = $file->ID;
		$export->write();

		return new SS_HTTPResponse(
			$form->dataFieldByName('SiteExports')->FieldHolder(),
			200,
			'The site export has been generated.');
	}

}