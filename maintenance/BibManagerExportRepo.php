<?php

//HINT: http://www.mediawiki.org/wiki/Manual:Writing_maintenance_scripts
require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/maintenance/Maintenance.php' );

class BibManagerExportRepo extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addOption('filename', 'The name of the file', true, true);
	}

	public function execute() {
		$sFilename     = $this->getOption( 'filename', 'new_export' );
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
		    'bibmanager', 'bm_bibtexCitation'
		);
		$aReturn = array ( );
		while ( $row = $dbr->fetchRow( $res ) ) {
			$aReturn [] = $row;
		}
		
		$sOutput = "";
		foreach ( $aReturn as $sCitation) {
			$entry = BibManagerRepository::singleton()->getBibEntryByCitation( $sCitation );
			if ( empty( $entry ) ) continue;
			$entryType = $entry['bm_bibtexEntryType'];
			$typeDefs = BibManagerFieldsList::getTypeDefinitions();
			$entryFields = array_merge( // TODO RBV (17.12.11 15:01): encapsulte in BibManagerFieldsList
			    $typeDefs[$entryType]['required'], $typeDefs[$entryType]['optional']
			);
			$lines = array ( );
			$lines[] = "\t" . $entry['bm_bibtexCitation'];
			foreach ( $entryFields as $fieldName ) {
				$value = $entry['bm_' . $fieldName];
				if ( empty( $value ) )
					continue;
				$lines[] = "\t" . $fieldName . ' = {' . $value . '}';
			}
			$sOutput .= '@' . $entryType . "{\n" . implode( ",\n", $lines ) . "\n}\n\n";
		}
		
		file_put_contents($sFilename, $sOutput);
		if (file_exists($sFilename))
			echo ("Export successfull!");
		else
			echo ("Failed exporting.");
	}
}

$maintClass = 'BibManagerExportRepo';
if (defined('RUN_MAINTENANCE_IF_MAIN')) {
	require_once( RUN_MAINTENANCE_IF_MAIN );
} else {
	require_once( DO_MAINTENANCE ); # Make this work on versions before 1.17
}