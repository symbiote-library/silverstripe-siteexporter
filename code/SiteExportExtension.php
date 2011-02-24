<?php
/**
 * Adds the ability to export the site to the main CMS panel.
 *
 * @package silverstripe-siteexport
 */
class SiteExportExtension extends Extension {

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
			$action
		);
		$form->Fields()->addFieldsToTab('Root.Export', $fields);
	}

	public function doExport($data, $form) {
		$data  = $form->getData();
		$links = array();

		// First generate a temp directory to store the export content in.
		$temp  = TEMP_FOLDER;
		$temp .= sprintf('/siteexport_%s', date('Y-m-d_H-i-s'));
		mkdir($temp);

		$exporter = new SiteExporter();
		$exporter->root         = $form->getRecord();
		$exporter->theme        = $data['ExportSiteTheme'];
		$exporter->baseUrl      = $data['ExportSiteBaseUrl'];
		$exporter->makeRelative = $data['ExportSiteBaseUrlType'] == 'rewrite';
		$exporter->exportTo($temp);

		// Then place the exported content into an archive.
		SiteExportUtils::zip_directory($temp, $zip = "$temp.zip");

		$filename = preg_replace('/[^a-zA-Z0-9-.]/', '-', sprintf(
			'%s-%s.zip', SiteConfig::current_site_config()->Title, date('Y-m-d H:i:s')
		));

		return SS_HTTPRequest::send_file(
			file_get_contents($zip),
			$filename,
			'application/zip');
	}

}