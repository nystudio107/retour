# Retour Changelog

## 1.0.19 -- 2017.02.10

* [Added] Added a referrer column in the Stats table
* [Added] Added additional logging in `devMode`
* [Improved] No more default value for `redirectSrcUrl` column (could cause SQL exceptions in newer versions of MySQL)
* [Improved] Updated CHANGELOG.md

## 1.0.18 -- 2017.01.20

* [Fixed] The `addTrailingSlashesToUrls` is now respected for dynamic entry redirects
* [Improved] Merged pull request 'Fix retourMatch hook'
* [Added] Added a `statsDisplayLimit` setting to `config.php` to control how many stats should be displayed in the AdminCP
* [Improved] Merged pull request 'Limit returned results to template'
* [Improved] Merged pull request 'Allow handling of 404s from { exit } tags encountered while rendering templates'
* [Added] Added a config.php setting `createUriChangeRedirects` so that the URI-change redirects can be disabled on a per-environment basis
* [Improved] Don't redirect to the welcome page if we're being installed via Console command
* [Improved] Moved the changelog to CHANGELOG.md

## 1.0.17 -- 2016.08.31

* [Improved] Query strings are now stripped from the incoming URI before redirect detection is done
* [Improved] Updated the README.md

## 1.0.16 -- 2016.08.30

* [Fixed] FieldTypes in multi-locale setups that are not translatable are now handled properly
* [Fixed] Fixed missing locale prefix for localized entries in the FieldType
* [Fixed] Fixed an issue where FieldType redirects had an errant / prepended to them
* [Improved] Better importing of `.htaccess` files
* [Improved] Better error handling when importing malformed `.htaccess` files
* [Fixed] Trailing /'s are no longer stripped from URLs added via the `+` icon from the Statistics page
* [Fixed] Fixed an issue that would prevent RegEx's from matching as they should in FieldTypes
* [Improved] Updated the README.md

## 1.0.15 -- 2016.07.12

* [Added] Added the ability to import the redirects from a `.htaccess` file into Retour
* [Fixed] Fixed a statics db error with empty referrers
* [Improved] Updated the README.md

## 1.0.14 -- 2016.07.10

* [Added] The Statistics and Redirects tables are now dynamically searchable and sortable
* [Fixed] Fixed an issue that caused redirects created via the `+` from Statistics page to not save
* [Improved] Updated the README.md

## 1.0.13 -- 2016.07.06

* [Added] Adds support for locales in the automatic redirect that is created when a slug is changed for an entry
* [Improved] Retour will no longer let you save a static redirect with an empty destinationURL
* [Fixed] Fixed a typo in the Retour_StatsModel
* [Improved] Added a rant about `.htaccess` to the docs
* [Improved] Updated the README.md

## 1.0.12 -- 2016.07.04

* [Added] If you hover over a 404 File Not Found URL on the Statistics page, you'll now see the last referrer for the 404 URL
* [Added] Added a + button on the Statistics page that lets you quickly add a 404'd URL as a redirect
* [Improved] We now store the destination for redirects in the FieldType as a URI rather than a URL, so that it's more portable across environments
* [Added] Structure entries that have Retour FieldTypes in them now have the destinationURL updated when the structure elements are moved
* [Improved] The widget now handles very long URLs more gracefully
* [Improved] Updated the README.md

## 1.0.11 -- 2016.06.21

* [Fixed] Fixed an issue with URLs that have umlauts in them
* [Fixed] Fixed an issue with URLs that are longer than 255 characters for the redirect statistics
* [Improved] Statistics are now limited to the top 1,000 hits
* [Improved] Updated the README.md

## 1.0.10 -- 2016.06.15

* [Added] Retour will attempt to prevent redirect loops when saving a new redirect by deleting any existing redirects that have the destUrl as their srcUrl
* [Added] Added a 410 - Gone redirect http code for permanently removed resources
* [Improved] Updated the README.md

## 1.0.9 -- 2016.06.04

* [Added] Retour will now automatically create a static redirect for you if you rename an entry's slug
* [Improved] Retour checks to ensure that no two redirects have the same redirectSrcUrl
* [Improved] The Statistics page handles really long URLs better now
* [Improved] If you save a redirect, either static or dynamic, with an empty Legacy URL Pattern, retour now deletes it
* [Improved] Updated the README.md

## 1.0.8 -- 2016.05.30

* [Improved] Revamped Retour to key off of the ElementID rather than the EntryID
* [Fixed] Fixed an issue with Retour and MySQL running in strict mode (which is the default in 5.7+)
* [Fixed] Retour will no longer try to save a record with a null id (caused a CDbCommand exception)
* [Fixed] A '/' isn't prepended to empty src URLs anymore
* [Improved] Updated the README.md

## 1.0.7 -- 2016.05.07

* [Improved] getRequestUri() is now explicitly used, and we immediately terminate the request upon redirect
* [Improved] We now pass in 0 instead of null for the cache duration
* [Improved] We now explicitly check for CHttpException
* [Improved] Updated the README.md

## 1.0.6 -- 2016.04.29

* [Fixed] Fixed a Javascript error with the FieldType Javascript
* [Fixed] Fixed a visual display glitch with the tabs on Craft 2.4.x
* [Improved] Updated the README.md

## 1.0.5 -- 2016.04.28

* [Added] Added a 'Clear Statistics' button to the Statistics page
* [Fixed] Fixed a bug when using RegEx for static redirects that would cause them to not work
* [Fixed] Fixed an issue with Craft 2.4.x
* [Improved] Updated the README.md

## 1.0.4 -- 2016.04.28

* [Added] The tables in the Statistics and Redirects pages are now sortable by any column
* [Improved] Fixed up the localization support for the FieldType
* [Improved] Minor changes/fixes to the plugin
* [Improved] Updated the README.md

## 1.0.3 -- 2016.04.27

* [Added] Added a Retour Stats widget
* [Added] Added information on the Statistics tab as to whether Retour handled the 404 or not
* [Improved] Updated the README.md

## 1.0.2 -- 2016.04.26

* [Fixed] Fixed faulty indexes that could cause Retour Redirect FieldTypes to not work properly
* [Improved] Spiffy new icon
* [Improved] Changing the display name of the plugin is now more globally applied
* [Improved] Updated the README.md

## 1.0.1 -- 2016.04.26

* [Added] Implemented a caching layer so that once a redirect has been determined, subsequent redirects are cached and immediately returned
* [Added] Added the ability to delete static redirects
* [Added] Added Composer support
* [Improved] Updated the README.md

## 1.0.0 -- 2016.04.25

* Initial release

Brought to you by [nystudio107](http://nystudio107.com)