> ## **IMPORTANT**

> This module is no longer actively maintained, however, if you're interested in adopting it, please let us know!

# SilverStripe Site Exporter Module

The Site Exporter module allows you to download a copy of your site as a zip
archive. The difference between this and the static publisher code is that it
attempts to include and rewrite asset files and links to the site can be
viewed on a local computer without installing a web server or requesting any
external assets.

## Installation Instructions
* Download the module and extract if to your site root. The directory should be
  called "siteexporter".
* Visit any page on your site with ?flush=1 set to rebuild the manifest.
* Now when you visit the admin the root site config item, as well as any pages
  with children should have an "Export" tab.

## Maintainer Contacts
* Marcus Nyeholt (<marcus@silverstripe.com.au>)

## Requirements
* The PHP Zip extension.
* SilverStripe 2.4+
* If you want to run the export in queued stages (useful for larger sites), the
  queued jobs module is required.

## Project Links
* [GitHub Project Page](https://github.com/ajshort/silverstripe-siteexporter)
* [Issue Tracker](https://github.com/ajshort/silverstripe-siteexporter/issues)
