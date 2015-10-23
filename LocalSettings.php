<?php
# This file was automatically generated by the MediaWiki 1.24.1
# installer. If you make manual changes, please keep track in case you
# need to recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# https://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename = "Clarat-Wiki";
$wgMetaNamespace = "Clarat-wiki";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "";
$wgScriptExtension = ".php";

## The protocol and server name to use in fully-qualified URLs
$wgServer = "http://clarat-wiki.herokuapp.com";

## The relative URL path to the skins directory
$wgStylePath = "$wgScriptPath/skins";

## The relative URL path to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogo = "$wgScriptPath/images/claratwiki.png";

## UPO means: this is also a user preference option

$wgEnableEmail = false;
$wgEnableUserEmail = true; # UPO

$wgEmergencyContact = "apache@clarat-wiki.herokuapp.com";
$wgPasswordSender = "apache@clarat-wiki.herokuapp.com";

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = false; # UPO
$wgEmailAuthentication = true;

## Database settings
// $wgDBtype = "mysql";
// $wgDBserver = "us-cdbr-iron-east-**.cleardb.net";
// $wgDBname = "heroku_***************";
// $wgDBuser = "**************";
// $wgDBpassword = "********";
$_wgDBConnectionString = getenv('CLEARDB_DATABASE_URL');
if (preg_match('%(.*?)://([^:]+):([^@]+)@([^/]+)/([^?]+)?(.*)%', $_wgDBConnectionString, $regs, PREG_OFFSET_CAPTURE)) {
$wgDBtype = $regs[1][0];
$wgDBuser = $regs[2][0];
$wgDBpassword = $regs[3][0];
$wgDBserver = $regs[4][0];
$wgDBname = $regs[5][0];
} else {
die("Failed to parse DB connection string");
}

# MySQL specific settings
$wgDBprefix = "";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=utf8";

# Experimental charset support for MySQL 5.0.
$wgDBmysql5 = true;

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = array();

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = false;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

# InstantCommons allows wiki to use images from http://commons.wikimedia.org
$wgUseInstantCommons = false;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "en_US.utf8";

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
#$wgHashedUploadDirectory = false;

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publically accessible from the web.
#$wgCacheDirectory = "$IP/cache";

# Site language code, should be one of the list in ./languages/Names.php
$wgLanguageCode = "de";

$wgSecretKey = "41e0476291c58230cf2aaed4e1f730af39696080045a51b0b486babf4af62bf0";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = "b9c7b37ec4541a49";

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "https://creativecommons.org/publicdomain/zero/1.0/";
$wgRightsText = "''Creative Commons'' „Zero“ (Gemeinfreiheit)";
$wgRightsIcon = "{$wgResourceBasePath}/resources/assets/licenses/cc-0.png";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

# The following permissions were set based on your choice in the installer
$wgGroupPermissions['*'    ]['createaccount']   = false;
$wgGroupPermissions['*'    ]['edit']            = false;
$wgGroupPermissions['*'    ]['read']            = true;
$wgGroupPermissions['sysop']['createaccount']   = true;
$wgGroupPermissions['user' ]['edit']            = true;
$wgGroupPermissions['user' ]['read']            = true;
$wgGroupPermissions['user' ]['delete']          = true;
$wgGroupPermissions['user' ]['move']            = true;

#Approved Revs Grouppermissions
#$wgGroupPermissions['*']['viewlinktolatest'] = false;
#$wgGroupPermissions['sysop']['viewlinktolatest'] = true;
#$wgGroupPermissions['sysop']['approverevisions'] = true;
#$wgGroupPermissions['user']['viewlinktolatest'] = true;
#$wgGroupPermissions['user']['approverevisions'] = true;

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'vector', 'monobook':
$wgDefaultSkin = "vector";

# Enabled skins.
# The following skins were automatically enabled:
require_once "$IP/skins/CologneBlue/CologneBlue.php";
require_once "$IP/skins/Modern/Modern.php";
require_once "$IP/skins/MonoBook/MonoBook.php";
require_once "$IP/skins/Vector/Vector.php";


# End of automatically generated settings.
# Add more configuration options below.

# Enable NewestPages extension
require_once( 'extensions/NewestPages/NewestPages.php' );

#activate Approved Revs
#require_once( "$IP/extensions/ApprovedRevs/ApprovedRevs.php" );


#$egApprovedRevsBlankIfUnapproved = true;
#$egApprovedRevsShowApproveLatest = true;
#$egApprovedRevsAutomaticApprovals = false;




#Cite Extension aktivieren
require_once("$IP/extensions/Cite/Cite.php");

#Enable MassEditRegex

#require_once "$IP/extensions/MassEditRegex/MassEditRegex.php";
#$wgGroupPermissions['sysop']['masseditregex'] = true;



#activate 'Category Tree' extension
#$wgUseAjax = true;
#require_once "$IP/extensions/CategoryTree/CategoryTree.php";

#activate 'select Category' extension
require_once( 'extensions/SelectCategory/SelectCategory.php' );

#Open links in new Tab
$wgExternalLinkTarget = '_blank';


#Allow Fileupload
$wgEnableUploads = true;

#WikiEditor aktivieren
require_once "$IP/extensions/WikiEditor/WikiEditor.php";
$wgDefaultUserOptions['usebetatoolbar'] = 1;
$wgDefaultUserOptions['usebetatoolbar-cgd'] = 1;
$wgDefaultUserOptions['wikieditor-preview'] = 1;

#Enable Replace-Text Extension
#require_once( "$IP/extensions/ReplaceText/ReplaceText.php" );


#Enable Cirrus-Search
#require_once "$IP/extensions/CirrusSearch/CirrusSearch.php";
#require_once "$IP/extensions/Elastica/Elastica.php";

