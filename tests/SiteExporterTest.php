<?php
/**
 * @package    silverstripe-siteexporter
 * @subpackage tests
 */
class SiteExporterTest extends SapphireTest {

	public static $fixture_file = 'siteexporter/tests/SiteExporterTest.yml';

	public function testSiteExporterGeneratesHtmlAndAssets() {
		$suffix = 0;

		while (is_dir($path = TEMP_FOLDER . '/' . __CLASS__ . $suffix)) {
			$suffix++;
		}

		$expect = array(
			'index.html',
			'about.html',
			'about/staff.html',
			'about/history.html',
			'contact.html',
		);

		$exporter = new SiteExporter();
		$exporter->exportTo($path);

		foreach ($expect as $file) {
			$this->assertFileExists("$path/$file");
		}

		Filesystem::removeFolder($path);
	}

}