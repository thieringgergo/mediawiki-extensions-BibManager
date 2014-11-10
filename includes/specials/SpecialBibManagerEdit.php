<?php

class SpecialBibManagerEdit extends UnlistedSpecialPage {

	public function __construct () {
		parent::__construct( 'BibManagerEdit' , 'bibmanageredit');
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

		$citation = !empty( $par ) ? $par : $wgRequest->getVal( 'bm_bibtexCitation', '' );

		$entry = array();
		$entry['bm_bibtexCitation'] = $citation;
		if ( !empty( $citation ) ) {
			$e = BibManagerRepository::singleton()
					->getBibEntryByCitation( $citation );
			if ( !empty( $e ) ) {
				$entry = $e;
			}
		}

		$entryType = $wgRequest->getVal( 'bm_select_type', '' );
		if( empty( $entryType ) ) {
			$entryType = $wgRequest->getVal( 'wpbm_bibtexEntryType', '' );
		}
		if ( isset( $entry['bm_bibtexEntryType'] ) ) {
			$entryType = $entry['bm_bibtexEntryType'];
		}

		if ( empty( $entryType ) ) {
			$wgOut->addHTML( 'No Citation or EntryType provided.' ); // TODO RBV (17.12.11 16:53): I18N
			return;
		}

		// Give grep a chance to find the usages: bm_entry_type_article, bm_entry_type_book,
		// bm_entry_type_booklet, bm_entry_type_conference, bm_entry_type_inbook,
		// bm_entry_type_incollection, bm_entry_type_inproceedings, bm_entry_type_manual,
		// bm_entry_type_mastersthesis, bm_entry_type_misc, bm_entry_type_phdthesis,
		// bm_entry_type_proceedings, bm_entry_type_techreport, bm_entry_type_unpublished
		

		$typeDefs = BibManagerFieldsList::getTypeDefinitions();
		$bibTeXFields = BibManagerFieldsList::getFieldDefinitions();
	
		

		$formDescriptor = array();
		$formDescriptor['bm_bibtexCitation'] = array (
			'class' => 'HTMLTextField',
			'label' => wfMsg( 'bm_citation' ),
			'section' => 'citation',
			'required' => true,
			'validation-callback' => 'BibManagerValidator::validateCitation'
		);
		$formDescriptor['bm_edit_mode'] = array (
				'class' => 'HTMLHiddenField',
				//'class' => 'HTMLTextField',
				'section' => 'citation',
				'label' => 'editmode'
		);
		//$formDescriptor['bm_edit_mode']['default'] = isset( $entry['bm_edit_mode'] ) ? $entry['bm_edit_mode'] : '';

		if ( $wgRequest->getVal( 'bm_edit_mode', '' ) == 'yes' ) {
			//for the first time we extract the value from the URL bar!
			$formDescriptor['bm_edit_mode']['default'] = 'yes';
		}
		$wgOut->setPageTitle( wfMsg( 'heading_edit', wfMsg( 'bm_entry_type_' . $entryType ) ) );
		
		//getPageTitle();

		if ( $editMode || !empty( $entry['bm_bibtexCitation'] ) || $wgRequest->getVal( 'bm_edit_mode', '' ) == 'yes') { 
			// TODO RBV (18.12.11 14:26): What if we come from an redlink?
			$formDescriptor['bm_bibtexCitation']['readonly'] = true;
			$formDescriptor['bm_bibtexCitation']['default'] = $entry['bm_bibtexCitation'];
			$formDescriptor['bm_bibtexCitation']['help-message'] = 'bm_readonly';
		}

		foreach ( $typeDefs[$entryType]['required'] as $fieldName ) {
			$fieldDef = $bibTeXFields[$fieldName];
			$fieldDef['required'] = true;
			$fieldDef['section'] = 'required';
			$fieldDef['default'] = isset( $entry['bm_' . $fieldName] ) ? $entry['bm_' . $fieldName] : '';
			$formDescriptor['bm_' . $fieldName] = $fieldDef;
		}

		foreach ( $typeDefs[$entryType]['optional'] as $fieldName ) {
			$fieldDef = $bibTeXFields[$fieldName];
			$fieldDef['section'] = 'optional';
			$fieldDef['default'] = isset( $entry['bm_' . $fieldName] ) ? $entry['bm_' . $fieldName] : '';
			$formDescriptor['bm_' . $fieldName] = $fieldDef;
		}

		$formDescriptor['bm_bibtexEntryType'] = array (
			'class' => 'HTMLHiddenField',
			'default' => $entryType
		);
		$formDescriptor['bm_select_type'] = $formDescriptor['bm_bibtexEntryType'];

		wfRunHooks( 'BibManagerEditBeforeFormCreate', array ( $this, &$formDescriptor ) );

		$htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'bm_edit' );
		$htmlForm->setSubmitText( wfMsg( 'bm_edit_submit' ) );
		$htmlForm->setSubmitCallback( array ( $this, 'submitForm' ) );
		//TODO: Add cancel button that returns user to the place he came from. I.e. filtered overview

		$wgOut->addHTML( '<div id="bm_form">' );
		$htmlForm->show();
		$wgOut->addHTML( '</div>' );
	}

	/**
	 * Submit callback for edit form
	 * @param array $formData
	 * @return boolean
	 */
	public function submitForm ( $formData ) {
		$repo = BibManagerRepository::singleton();
		$typeDefs = BibManagerFieldsList::getTypeDefinitions();
		$entryType = $formData['bm_bibtexEntryType'];
		$entryFields = array_merge(
			$typeDefs[$entryType]['required'], $typeDefs[$entryType]['optional']
		);

		$submittedFields = array ( );
		foreach ( $formData as $key => $value ) {
			$unprefixedKey = substr( $key, 3 );
			if ( in_array( $unprefixedKey, $entryFields ) ) {
				$submittedFields[$key] = $value;
			}
		}

		//No update? No problem...
		$repo->deleteBibEntry( $formData['bm_bibtexCitation'] );
		$repo->saveBibEntry( $formData['bm_bibtexCitation'], $entryType, $submittedFields );

		$this->getOutput()->addWikiMsg( 'bm_success_save-complete' );
		$this->getOutput()->addHTML( wfMsg( 'bm_success_link-to-list', SpecialPage::getTitleFor( 'BibManagerList' )->getLocalURL() ) );

		return true;
	}
}
