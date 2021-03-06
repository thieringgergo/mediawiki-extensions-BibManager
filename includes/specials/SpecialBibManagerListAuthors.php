<?php

class SpecialBibManagerListAuthors extends SpecialPage {

	function __construct () {
		parent::__construct( 'BibManagerListAuthors' );
	}

	/**
	 * Main method of SpecialPage. Called by Framwork.
	 * @global WebRequest $wgRequest Current MediaWiki WebRequest object
	 * @global OutputPage $wgOut Current MediaWiki OutputPage object
	 * @global User $wgUser Current MediaWiki User object
	 * @param mixed $par string or false, provided by Framework
	 */
	function execute ( $par ) {
		global $wgOut;
		$this->setHeaders();
		$wgOut->setPageTitle( wfMsg( 'heading_list_authors' ) );

		$pager = new BibManagerPagerListAuthors();
		$sDataBody = $pager->getBody();
		if ( !empty( $sDataBody ) ) {
			$wgOut->addHTML( $pager->getNavigationBar() );
			$table = array ( );
			$table[] = '	<table class="wikitable" style="width:100%;">';
			$table[] = '		<tr>';
			$table[] = '			<th >' . wfMsg( 'bm_list_author_table_heading-author' ) . '</th>';
			$table[] = '			<th style="width: 100px;">' . wfMsg( 'bm_list_author_table_heading-amount' ) . '</th>';
			$table[] = '		</tr>';
			$table[] = $sDataBody;
			$table[] = '	</table>';

			$wgOut->addHTML( implode( "\n", $table ) );
			$wgOut->addHTML( $pager->getNavigationBar() );
		} else {
			$wgOut->addHtml( wfMsg( 'bm_error_no-data-found' ) );
		}
	}

	public function submitForm ( $aFormData ) {
		return false;
	}

}