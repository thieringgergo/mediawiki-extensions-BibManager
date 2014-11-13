<?php
/*
  (c) Tobias Weichart, Robert Vogel, Hallo Welt! Medienwerkstatt GmbH, 2011 GPL

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License along
  with this program; if not, write to the Free Software Foundation, Inc.,
  59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
  http://www.gnu.org/copyleft/gpl.html
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo 'To install BibManager, put the following line in LocalSettings.php: '
	.'include_once( "$IP/extensions/BibManager/BibManager.php" );' . "\n";
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array (
	'path'           => __FILE__,
	'name'           => 'BibManager',
	'author'         => array (
		'Hornemann Institut', 'Hallo Welt! Medienwerkstatt GmbH',
		'Tobias Weichart', 'Robert Vogel'
	),
	'url'            => 'http://www.mediawiki.org/wiki/Extension:BibManager',
	'version'        => '1.23.0',
	'descriptionmsg' => 'bibmanager-desc',
);

//Register classes
$wgAutoloadClasses['Structures_BibTex'] = __DIR__ . '/includes/libs/Structures_BibTex/BibTex.php'; //External library for parsing

$wgAutoloadClasses['BibManagerHooks']               = __DIR__ . '/includes/BibManagerHooks.php';
$wgAutoloadClasses['BibManagerFieldsList']          = __DIR__ . '/includes/BibManagerFieldsList.php';
$wgAutoloadClasses['BibManagerLocalMWDatabaseRepo'] = __DIR__ . '/includes/BibManagerLocalMWDatabaseRepo.php';
$wgAutoloadClasses['BibManagerPagerList']           = __DIR__ . '/includes/BibManagerPagerList.php';
$wgAutoloadClasses['BibManagerPagerListAuthors']    = __DIR__ . '/includes/BibManagerPagerListAuthors.php';
$wgAutoloadClasses['BibManagerRepository']          = __DIR__ . '/includes/BibManagerRepository.php';
$wgAutoloadClasses['BibManagerValidator']           = __DIR__ . '/includes/BibManagerValidator.php';
$wgAutoloadClasses['BibManagerLocalMWDatabaseRepo'] = __DIR__ . '/includes/BibManagerLocalMWDatabaseRepo.php';
$wgAutoloadClasses['BibManagerRepository']          = __DIR__ . '/includes/BibManagerRepository.php';

//SpecialPages
$wgAutoloadClasses['SpecialBibManagerList']        = __DIR__ . '/includes/specials/SpecialBibManagerList.php';
$wgAutoloadClasses['SpecialBibManagerListAuthors'] = __DIR__ . '/includes/specials/SpecialBibManagerListAuthors.php';
$wgAutoloadClasses['SpecialBibManagerImport']      = __DIR__ . '/includes/specials/SpecialBibManagerImport.php';
$wgAutoloadClasses['SpecialBibManagerExport']      = __DIR__ . '/includes/specials/SpecialBibManagerExport.php';
$wgAutoloadClasses['SpecialBibManagerCreate']      = __DIR__ . '/includes/specials/SpecialBibManagerCreate.php';
$wgAutoloadClasses['SpecialBibManagerDelete']      = __DIR__ . '/includes/specials/SpecialBibManagerDelete.php';
$wgAutoloadClasses['SpecialBibManagerEdit']        = __DIR__ . '/includes/specials/SpecialBibManagerEdit.php';

//Add I18N
$wgMessagesDirs['BibManager'] = __DIR__ . '//i18n';
$wgExtensionMessagesFiles['BibManager'] = __DIR__ . '/BibManager.i18n.php';
$wgExtensionMessagesFiles['BibManagerAlias']  = __DIR__ . '/BibManager.alias.php';

//Add SpecialPages
$wgSpecialPages['BibManagerList']        = 'SpecialBibManagerList';
$wgSpecialPages['BibManagerListAuthors'] = 'SpecialBibManagerListAuthors';
$wgSpecialPages['BibManagerImport']      = 'SpecialBibManagerImport';
$wgSpecialPages['BibManagerExport']      = 'SpecialBibManagerExport';
$wgSpecialPages['BibManagerDelete']      = 'SpecialBibManagerDelete';
$wgSpecialPages['BibManagerCreate']      = 'SpecialBibManagerCreate';
$wgSpecialPages['BibManagerEdit']        = 'SpecialBibManagerEdit';

//Add SpecialPages to group
$wgSpecialPageGroups['BibManagerList']        = 'bibmanager';
$wgSpecialPageGroups['BibManagerListAuthors'] = 'bibmanager';
$wgSpecialPageGroups['BibManagerImport']      = 'bibmanager';
$wgSpecialPageGroups['BibManagerExport']      = 'bibmanager';
$wgSpecialPageGroups['BibManagerDelete']      = 'bibmanager';
$wgSpecialPageGroups['BibManagerCreate']      = 'bibmanager';
$wgSpecialPageGroups['BibManagerEdit']        = 'bibmanager';

//Add Hookhandler
$wgHooks['ParserFirstCallInit'][] = 'BibManagerHooks::onParserFirstCallInit';
$wgHooks['BeforePageDisplay'][]   = 'BibManagerHooks::onBeforePageDisplay';
//$wgHooks['SkinAfterContent'][]    = 'BibManagerHooks::onSkinAfterContent';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'BibManagerHooks::onLoadExtensionSchemaUpdates';

$resourceModuleTemplate = array(
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'BibManager/resources'
);

$wgResourceModules['ext.bibManager.styles'] = array(
	'styles' => 'ext.bibManager.css',
) + $resourceModuleTemplate;

$wgResourceModules['ext.bibManager.List'] = array(
	'scripts' => 'ext.bibManager.List.js',
	'dependencies' => 'ext.bibManager.styles'
) + $resourceModuleTemplate;

$wgResourceModules['ext.bibManager.Edit'] = array(
	'scripts' => 'ext.bibManager.Edit.js',
	'dependencies' => 'ext.bibManager.styles'
) + $resourceModuleTemplate;

unset( $resourceModuleTemplate );

//Config Variables
$wgBibManagerRepoClass = 'BibManagerLocalMWDatabaseRepo';
$wgBibManagerCitationFormats = array (
    '-'             => '%author%: (%year%) <small><em>%title%</em></small>',
    'article'       => '%author%: <em>%journal%</em>, <b>%volume%</b>, %pages% (%year%). <small><em>%title%</em></small>',
    'book'          => '%author%: (%edition%). <em>%publisher%</em>, %address%, %pages% (%year%). <small><em>%title%</em></small>',
    'booklet'       => '%title%',
    'conference'    => '%author%: %booktitle% (%year%). <small><em>%title%</em></small>',
    'inbook'        => '%author%: (%edition%). <em>%publisher%</em>, %address%, %pages%, %editor%, %chapter% (%year%). <small><em>%title%</em></small>',
    'incollection'  => '%author%: %booktitle% (%year%). <small><em>%title%</em></small>',
    'inproceedings' => '%author%: <em>%publisher%</em>, %booktitle% (%year%). <small><em>%title%</em></small>',
    'manual'        => '%title%',
    'mastersthesis' => '%author%: %school% (%year%). <small><em>%title%</em></small>',
    'misc'          => '%author%: (%year%). <small><em>%title%</em></small>',
    'phdthesis'     => '%author%: %school% (%year%). <small><em>%title%</em></small>',
    'proceedings'   => '(%year%). <small><em>%title%</em></small>',
    'techreport'    => '%author%: %institution%. (%year%). <small><em>%title%</em></small>',
    'unpublished'   => '%author%: %note%. <small><em>%title%</em></small>'
);

//$wgBibManagerSkinAfterContentBibListConfig = array( 'hr' => true, 'hideonemptylist' => true ); //For future use.
$wgBibManagerCitationArticleNamespace = NS_MAIN;
$wgBibManagerScholarLink = 'http://scholar.google.com/scholar?q=%title%';

// Basic permissions
$wgGroupPermissions['sysop']['bibmanageredit']   = true;
$wgGroupPermissions['sysop']['bibmanagerdelete'] = true;
$wgGroupPermissions['sysop']['bibmanagercreate'] = true;
