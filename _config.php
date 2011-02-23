<?php
/**
 * @package silverstripe-siteexporter
 */

if (!extension_loaded('zip')) {
	throw new Exception('The Site Exporter module requires the PHP Zip extension.');
}

Object::add_extension('CMSMain', 'SiteExportExtension');