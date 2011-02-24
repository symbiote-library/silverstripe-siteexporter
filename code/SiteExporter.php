<?php
/**
 * The site exporter class is quite similar to the filesystem static publisher,
 * but performs a few extra operations:
 *
 *   - Includes any referenced asset files in the export.
 *   - Includes any non-template theme files in the export.
 *   - Can attempt to rewrite URLs to make them relative.
 *
 * @package silverstripe-siteexporter
 */
class SiteExporter extends FilesystemPublisher {

	public $root;
	public $theme;
	public $baseUrl;
	public $makeRelative;

	/**
	 * @ignore
	 */
	public function __construct() {
		parent::__construct(null, 'html');
	}

	public function exportTo($directory) {
		$directory = rtrim($directory, '/');
		$links     = $this->urlsToPaths($this->getLinks());
		$files     = array();

		increase_time_limit_to();
		increase_memory_limit_to();

		// Make the output directory if it doesn't exist.
		if (!is_dir($directory)) {
			mkdir($directory, Filesystem::$folder_create_mask, true);
		}

		if ($this->theme) {
			SSViewer::set_theme($this->theme);
		} else {
			SSViewer::set_theme(SSViewer::current_custom_theme());
		}

		if ($this->baseUrl && !$this->makeRelative) {
			$originalBaseUrl = Director::baseURL();
			Director::setBaseURL($this->baseUrl);
		}

		// Loop through each link that we're publishing, and create a static
		// html file for each.
		foreach ($links as $link => $path) {
			Requirements::clear();
			singleton('DataObject')->flushCache();

			$response = Director::test($link);
			$target   = $directory . '/' . $path;

			if (is_object($response)) {
				if ($response->getStatusCode() == '301' || $response->getStatusCode() == '302') {
					$absoluteURL = Director::absoluteURL($response->getHeader('Location'));
					$content = "<meta http-equiv=\"refresh\" content=\"2; URL=$absoluteURL\">";
				} else {
					$content = $response->getBody();
				}
			} else {
				$content = (string) $response;
			}

			// Find any external content references inside the response, and add
			// them to the copy array.
			$externals = $this->externalReferencesFor($content);
			if ($externals) foreach ($externals as $external) {
				if (!Director::is_site_url($external)) {
					continue;
				}

				$external = strtok($external, '?');
				$external = Director::makeRelative($external);

				if (file_exists(BASE_PATH . '/' . $external)) {
					$files["$directory/$external"] = array('copy', BASE_PATH . '/' . $external);
				}
			}

			// Append any anchor links which point to a relative site link
			// with a .html extension.
			$base    = preg_quote(Director::baseURL());
			$content = preg_replace('~<a(.+?)href="(' . $base . '[^"]*?)/?"~i', '<a$1href="$2.html"', $content);
			$content = str_replace('/.html', '/index.html', $content);

			// If we want to rewrite links to relative, then determine how many
			// levels deep we are and rewrite the relevant attributes globally.
			// Also, string the base tag.
			if ($this->makeRelative) {
				$content = preg_replace('~(src|href)="' . Director::absoluteBaseURL() . '~i', '$1="/', $content);

				if (($trimmed = trim($link, '/')) && strpos($trimmed, '/')) {
					$prepend = str_repeat('../', substr_count($trimmed, '/'));
				} else {
					$prepend = './';
				}

				$base    = preg_quote(Director::baseURL());
				$content = preg_replace('~(href|src)="' . $base . '~i', '$1="' . $prepend, $content);

				$content = preg_replace('~<base href="([^"]+)" />~', '', $content);
				$content = preg_replace('~<base href="([^"]+)"><!--[if lte IE 6]></base><![endif]-->~', '', $content);
			}

			$files[$target] = array('create', $content);
		}

		// If we currently have a theme active, then copy all the theme
		// assets across to the site.
		if ($theme = SSViewer::current_theme()) {
			$stack = array(THEMES_PATH . '/' . $theme);

			// Build up a list of every file present in the current theme
			// which is not a .ss template, and add it to the files array
			while ($path = array_pop($stack)) foreach (scandir($path) as $file) {
				if ($file[0] == '.' || $file[0] == '_') continue;

				if (is_dir("$path/$file")) {
					$stack[] = "$path/$file";
				} else {
					if (substr($file, -3) != '.ss') {
						$loc = "$path/$file";
						$to  = $directory . '/' . substr($loc, strlen(BASE_PATH) + 1);

						$files[$to] = array('copy', $loc);
					}
				}
			}
		}

		// If theres a favicon.ico file in the site root, copy it across
		if (file_exists(BASE_PATH . '/favicon.ico')) {
			$files["$directory/favicon.ico"] = array('copy', BASE_PATH . '/favicon.ico');
		}

		// Copy across or create all the files that have been generated.
		foreach ($files as $to => $from) {
			list($mode, $content) = $from;

			if (!is_dir(dirname($to))) {
				mkdir(dirname($to), Filesystem::$folder_create_mask, true);
			}

			if ($mode == 'create') {
				file_put_contents($to, $content);
			} else {
				copy($content, $to);
			}
		}

		if ($this->baseUrl && !$this->makeRelative) {
			Director::setBaseURL($originalBaseUrl);
		}
	}

	/**
	 * @return array
	 */
	public function getLinks() {
		if ($this->root instanceof SiteTree) {
			$links = array();
			$stack = array($this->root);

			while ($item = array_pop($stack)) {
				if ($children = $item->liveChildren(true)) {
					foreach ($children as $child) {
						$stack[] = $child;
						$links[] = $child->Link();
					}
				}
			}

			return $links;
		} else {
			return DataObject::get('SiteTree')->map('ID', 'Link');
		}
	}

}