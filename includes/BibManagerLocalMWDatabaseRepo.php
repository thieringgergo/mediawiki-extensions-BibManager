<?php

class BibManagerLocalMWDatabaseRepo extends BibManagerRepository {

	public function getCitationsLike ( $sCitation ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'bibmanager',
			'bm_bibtexCitation',
			array (
				'bm_bibtexCitation '
				. $dbr->buildLike( $sCitation, $dbr->anyString() )
			)
		);

		if ( $dbr->numRows( $res ) > 0 ) {
			$aExistingCitations = array();
			foreach ( $res as $row ) {
				$aExistingCitations[] = $row->bm_bibtexCitation;
			}
			return wfMessage(
				'bm_error_citation_exists',
				implode( ',', $aExistingCitations ),
				$sCitation . 'X'
			)->escaped();
		}
		return true;
	}
	public function getDoisLike ( $doi , $sCitation) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'bibmanager',
			'bm_bibtexCitation',
			array (
				'bm_doi'
				. $dbr->buildLike( $doi, $dbr->anyString() )
			)
		);
		if ( $dbr->numRows( $res ) > 0 ) {
			$aExistingDois = array();
			$doiOccurence = 0;
			foreach ( $res as $row ) {
				$aExistingCitation = $row->bm_bibtexCitation;
				//skip self. If the bm_bibtexCitation->bm_doi is same as our submitted submittedCitation->
				//return $row->bm_doi . 'X' . $row->bm_bibtexCitation;
				if ($sCitation != $aExistingCitation) {
					$aExistingCitations[] = $aExistingCitation;
				}	
			}
			//return  count($aExistingCitations);
			if ( count($aExistingCitations) == 1 ) {
				return wfMessage(
					'bm_error_doi_exists',
					$aExistingCitation, $doi, $sCitation
				)->escaped();
			} else if ( count($aExistingCitations) != 0 ) {
				return wfMessage(
					'bm_error_doi_toomuch',
					implode( ',', $aExistingCitations )
				)->escaped();
			}
		}
		return true;
	}


	/**
	 *
	 * @param mixed $mOptions
	 * @return mixed
	 */
	public function getBibEntries ( $mOptions ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
		    'bibmanager', '*', $mOptions
		);
		$aReturn = array ( );
		while ( $row = $dbr->fetchRow( $res ) ) {
			$aReturn [] = $row;
		}
		if ( !empty( $aReturn ) )
			return $aReturn;
		else
			return false;
	}

	public function getBibEntryByCitation ( $sCitation ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->selectRow(
			'bibmanager',
			'*',
			array (
				'bm_bibtexCitation' => $sCitation
			)
		);
		if ( $res === false ) {
			return array();
		}
		return (array) $res;
	}

	public function saveBibEntry ( $sCitation, $sEntryType, $aFields ) {
		$dbw = wfGetDB( DB_MASTER );
		return $dbw->insert(
			'bibmanager',
			$aFields + array (
				'bm_bibtexEntryType' => $sEntryType,
				'bm_bibtexCitation' => $sCitation
			)
		);
	}

	public function updateBibEntry ( $sCitation, $sEntryType, $aFields ) {
		$dbw = wfGetDB( DB_MASTER );

		return $dbw->update(
			'bibmanager',
			$aFields + array (
				'bm_bibtexEntryType' => $sEntryType
			),
			array(
				'bm_bibtexCitation' => $sCitation
			)
		);
	}

	public function deleteBibEntry ( $sCitation ) {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->delete(
			'bibmanager',
			array (
				'bm_bibtexCitation' => $sCitation
			)
		);

		return $res;
	}
}
