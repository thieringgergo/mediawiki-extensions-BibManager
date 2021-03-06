<?php

class SpecialBibManagerImport extends SpecialPage {

	public function __construct () {
		parent::__construct( 'BibManagerImport' , 'bibmanageredit');
	}

	/**
	 * Main method of SpecialPage. Called by Framework.
	 * @global WebRequest $wgRequest Current MediaWiki WebRequest object
	 * @global OutputPage $wgOut Current MediaWiki OutputPage object
	 * @param mixed $par string or false, provided by Framework
	 */
	public function execute ( $par ) {
		global $wgUser, $wgOut;
		if (!$wgUser->isAllowed('bibmanageredit')){
			$wgOut->showErrorPage('badaccess','badaccess-group0');
			return true;
		}

		global $wgRequest;
		$this->setHeaders();
		$wgOut->setPageTitle( wfMsg( 'heading_import' ) );

		if ( $wgRequest->getVal( 'bm_bibtex', '' ) == '' ) {
			$wgOut->addHtml( wfMsg( 'bm_import_welcome' ) );
		}

		$formDescriptor['bm_bibtex'] = array (
		    'class' => 'HTMLTextAreaField',
		    'rows' => 25
		);

		$htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'bm_edit' );
		$htmlForm->setSubmitText( wfMsg( 'bm_edit_submit' ) );
		$htmlForm->setSubmitCallback( array ( $this, 'submitForm' ) );

		$wgOut->addHTML( '<div id="bm_form">' );
		$htmlForm->show();
		$wgOut->addHTML( '</div>' );
	}

	/**
	 * Submit callback for import form
	 * @global OutputPage $wgOut
	 * @param array $formData
	 * @return mixed true on success, array of error messages on failure
	 */
	public function submitForm ( $formData ) {
		global $wgOut;

		$bibtex = new Structures_BibTex();
		$bibtex->setOption("extractAuthors", false);
		$bibtex->content = $formData['bm_bibtex'];
		$bibtex->parse();

		$errors = array ( );
		$repo = BibManagerRepository::singleton();
		$cleanedEntries = array ( );
		foreach ( $bibtex->data as $entry ) { // TODO RBV (18.12.11 15:05): Optimize this
			if ( empty( $entry ) )
				continue;

			$citation = trim($entry['cite']);
			$entryType = $entry['entryType']; // TODO RBV (18.12.11 15:14): This is very similar to BibManagerEdit specialpage. --> encapsulate.
			$typeDefs = BibManagerFieldsList::getTypeDefinitions();
			$entryFields = array_merge(
			    $typeDefs[$entryType]['required'], $typeDefs[$entryType]['optional']
			);

			$submittedFields = array ( );

			foreach ( $entry as $key => $value ) {
				if ( in_array( $key, $entryFields ) ) {
					$submittedFields['bm_' . $key] = $value;
				}
			}
			$existingEntry = $repo->getBibEntryByCitation( $citation );
			$result = BibManagerValidator::validateCitation( $citation, $submittedFields );
			if ( $result !== true ) {
				$errors[] = $result;
				//$errors[] = array( 'bm_error_citation_exists', $citation, $citation.'X' );
			} else {
				// TODO RBV (18.12.11 16:02): field validation!!!
				$cleanedEntries[] = array ( $citation, $entryType, $submittedFields );
			}
			if ( !empty( $submittedFields['bm_doi'] ) )
				$submittedFields['bm_bibtexCitation'] = $citation;
				$result = BibManagerValidator::validateDoi( $submittedFields['bm_doi'], $submittedFields );
			if ( $result !== true )
				$errors[] = $result;
		}
		if ( !empty( $errors ) ) {

			return '<ul><li>' . implode( '</li><li>', $errors ) . '</li></ul>';
		}

		foreach ( $cleanedEntries as $cleanedEntry ) {
			$repo->saveBibEntry( $cleanedEntry[0], $cleanedEntry[1], $cleanedEntry[2] );
		}

		$wgOut->addHtml( wfMsg( 'bm_success_save-complete' ) );
		$wgOut->addHtml( wfMsg( 'bm_success_link-to-list', SpecialPage::getTitleFor( "BibManagerList" )->getLocalURL() ) );

		return true;
	}
}
