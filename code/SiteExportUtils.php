<?php
/**
 * @package silverstripe-siteexporter
 */
class SiteExportUtils {

	/**
	 * Creates a zip directory from a directory.
	 *
	 * @param string $directory
	 * @param string $zipPath
	 */
	public static function zip_directory($directory, $zipPath) {
		$zip = new ZipArchive();

		if (!is_dir($directory)) {
			throw new InvalidArgumentException('Not a valid directory.');
		}

		if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
			throw new Exception('Could not open zip archive.');
		}

		$iterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $item) {
			$path = substr($item->getPathname(), strlen($directory) + 1);

			if ($item->isDir()) {
				$zip->addEmptyDir($path);
			} else {
				$zip->addFile($item->getRealPath(), $path);
			}
		}
	}

}