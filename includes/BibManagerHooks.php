<?php

class BibManagerHooks {

	/**
	 * Default MW-Importer (for MW <=1.16 and MW >= 1.17)
	 * @param Updater $updater
	 * @return boolean true if alright
	 */
	public static function onLoadExtensionSchemaUpdates ( $updater = null ) {
		$updater->addExtensionUpdate(
			array (
				'addTable',
				'bibmanager',
				dirname( __DIR__ ) . '/maintenance/bibmanager.sql',
				true
			)
		);
		return true;
	}

	/**
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return bool Always true to keep hook running
	 */
	public static function onBeforePageDisplay ( &$out, &$skin ) {
		if ( $out->getTitle()->equals( SpecialPage::getTitleFor( 'BibManagerEdit' ) )
			|| $out->getTitle()->equals( SpecialPage::getTitleFor( 'BibManagerCreate' ) ) ) {
			$out->addModules( 'ext.bibManager.Edit');
		}

		if ( $out->getTitle()->equals( SpecialPage::getTitleFor( 'BibManagerList' ) ) ) {
			$out->addModules( 'ext.bibManager.List');
		}
		return true;
	}

	/**
	 * Init-method for the BibManager-Hooks
	 * @param Parser $parser
	 * @return bool Always true to keep hooks running
	 */
	public static function onParserFirstCallInit ( &$parser ) {
		$parser->setHook( 'bib', 'BibManagerHooks::onBibTag' );
		$parser->setHook( 'biblist', 'BibManagerHooks::onBiblistTag' );
		$parser->setHook( 'bibprint', 'BibManagerHooks::onBibprintTag' );
		return true;
	}

	/**
	 * Method for the BibManager-Tag <bib id='citation' />
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return String the link to the Bib-Cit entered by id
	 */
	public static function onBibTag ( $input, $args, $parser, $frame ) {
		global $wgUser;
		global $wgBibManagerCitationArticleNamespace;
		global $wgBibManagerID;
		$parser->disableCache();
		if ( !isset( $args['id'] ) )
			return '[' . wfMsg( 'bm_missing-id' ) . ']';

		$entry = BibManagerRepository::singleton()
		    ->getBibEntryByCitation( $args['id'] );

		$sTooltip = '';
		$sLink = '';
		
		$oCitationTitle = Title::newFromText( $args['id'], $wgBibManagerCitationArticleNamespace );
		if ( empty($wgBibManagerID['' . $oCitationTitle]))
			$wgBibManagerID['' . $oCitationTitle]=count($wgBibManagerID)+1;
		if ( empty( $entry ) ) {
			$spTitle = SpecialPage::getTitleFor( 'BibManagerCreate' );
			$sLink = Linker::link(
			    $spTitle , '[' . $args['id'] . ']', array ( 'class' => 'new' ), array ( 'bm_bibtexCitation' => $args['id'] ), array ( 'broken' => true )
			);
			$sTooltip = '<span>' . wfMsg('bm_error_not-existing');
			if ($wgUser->isAllowed('bibmanagercreate')){
				$sLinkToEdit = SpecialPage::getTitleFor( 'BibManagerCreate' )->getLocalURL( array ( 'bm_bibtexCitation' => $args['id'] ));
				$sTooltip .= XML::element("a", array("href" => $sLinkToEdit), wfMsg( 'bm_tag_click_to_create' ));
			}
			$sTooltip .= '</span>';
		} else if ( empty( $entry['bm_url'] ) ) {
			$sLink = '[' . $wgBibManagerID['' . $oCitationTitle] . ']';
			$sTooltip = self::getTooltip( $entry, $args );
		} else {
			$oCitationUrl = Title::newFromText( $entry['bm_url'], $wgBibManagerCitationArticleNamespace );
			$sLink = Linker::makeExternalLink($oCitationUrl, '[' . $wgBibManagerID['' . $oCitationTitle] . ']', false);
			$sTooltip = self::getTooltip( $entry, $args );
		}
		return '<span class="bibmanager-citation">' . $sLink . $sTooltip . '</span>';
	}

	public static function getTooltip ( $entry, $args ) {
		$typeDefs = BibManagerFieldsList::getTypeDefinitions();
		$entryTypeFields = array_merge(
			$typeDefs[$entry['bm_bibtexEntryType']]['required'], $typeDefs[$entry['bm_bibtexEntryType']]['optional']
		);

		$tooltip = array ( );
		wfRunHooks( 'BibManagerBibTagBeforeTooltip', array ( &$entry ) );
		foreach ( $entry as $key => $value ) {
			$unprefixedKey = substr( $key, 3 );
			if ( empty( $value ) || !in_array( $unprefixedKey, $entryTypeFields ) )
				continue; //Filter unnecessary fields
			if ( $unprefixedKey == 'author' || $unprefixedKey == 'editor' ) {
				$value = self::parseLatexString($value);
				$authors = explode( ' and ', $value );
				foreach ( $authors as $authors_key => $authors_value ) {
					//reverse the name order!
					$name = explode( ',', $authors_value );	
					foreach ( $name as $name_key => $name_value ) {
						$frags = explode( ' ', $name_value );
						if ($name_key == 1){
							foreach ( $frags as $frags_key => $frags_value ) {
								if ($frags_value != '')
									$frags[$frags_key] = mb_substr($frags[$frags_key], 0, 1, 'utf-8') . '.';
							}
						}
						$name[$name_key] = implode(' ' , $frags);
					}
					$authors[$authors_key] = implode( ' ' , array_reverse($name));
				}
				$value = implode( ', ' , $authors);
			}
			if ( $unprefixedKey == 'title' ) {
				$parts = explode( '$', $value );	
				foreach ( $parts as $parts_key => $parts_value ) {
					if ($parts_key&1){
						//insert here a math latex interpreter
						$parts[$parts_key] = '' ;
					} else {
						$parts[$parts_key] = self::parseLatexString($parts[$parts_key]);
					}
				} 
				$value = implode( '' , $parts);
			}
			if ( $unprefixedKey == 'editor' )
				$value .= wfMsg( 'bm_editor_addition' );
			
			if ( $unprefixedKey != 'url' ) {
				if (strlen($value) > 250)
					
					$value = substr ( $value , 1 , 250 ) . '... more';
				$tooltip[] = XML::element( 'strong', null, wfMsg( $key ) . ': ' ) . ' '
			    		. XML::element( 'em', null, $value )
			    		."<br/>";//. XML::element( 'br', null, null ); //This is just a little exercise
			}
		}
		$tooltip[] = self::getIcons( $entry );
		$tooltipString = implode( "", $tooltip );
		$tooltipString = '<span>' . $tooltipString . '</span>';

		//if ( isset( $args['mode'] ) && $args['mode'] == 'full' ) {
		//	$format = self::formatEntry( $entry );
		//	$tooltipString = ' ' . $format . ' ' . $tooltipString;
		//}
		return $tooltipString;
	}

	public static function getIcons ( $entry ) {
		global $wgScriptPath, $wgUser;
		global $wgBibManagerScholarLink;
		$icons = array ( );

		if ( !empty( $entry['bm_bibtexCitation'] ) && $wgUser->isAllowed('bibmanageredit') ) {
			$icons['edit'] = array (
				'src' => $wgScriptPath . '/extensions/BibManager/resources/images/pencil.png',
				'title' => 'bm_tooltip_edit',
				'href' => SpecialPage::getTitleFor( 'BibManagerEdit' )
				->getLocalURL( array ( 'bm_bibtexCitation' => $entry['bm_bibtexCitation'] , 'bm_edit_mode'=> 'yes') )
			);
			$icons['delete'] = array (
				'src' => $wgScriptPath . '/extensions/BibManager/resources/images/cross.png',
				'title' => 'bm_tooltip_delete',
				'href' => SpecialPage::getTitleFor( 'BibManagerDelete' )
				->getLocalURL( array ( 'bm_bibtexCitation' => $entry['bm_bibtexCitation'] ) )
			);
		}
		$scholarLink = str_replace( '%title%', $entry['bm_title'], $wgBibManagerScholarLink );
		$icons['scholar'] = array (
			'src' => $wgScriptPath . '/extensions/BibManager/resources/images/scholar.png',
			'title' => 'bm_tooltip_scholar',
			'href' => $scholarLink
		);
		if ( !empty( $entry['bm_url'] ) )
		{
			$icons['url'] = array (
				'src' => $wgScriptPath . '/extensions/BibManager/resources/images/book.png',
				'title' => 'bm_tooltip_url',
				'href' => $entry['bm_url']
			);
		}
		$icons['exportBib'] = array (
			'src' => $wgScriptPath . '/extensions/BibManager/resources/images/disk.png',
			'title' => 'bm_tooltip_exportBib',
			'href' => SpecialPage::getTitleFor( 'BibManagerExport' )
				->getLocalURL( array ( 'cit' => $entry['bm_bibtexCitation'] ) )
		);
		wfRunHooks( 'BibManagerGetIcons', array ( $entry, &$icons ) );

		$out = array ( );
		foreach ( $icons as $key => $iconDesc ) {
			$text = wfMessage( $iconDesc['title'] )->escaped();
			$iconEl = XML::element(
				'img', array (
				'src' => $iconDesc['src'],
				'alt' => $text,
				'title' => $text
				)
			);
			$anchorEl = XML::tags(
				'a', array (
				'href' => $iconDesc['href'],
				'title' => $text,
				'target' => '_blank',
				), $iconEl
			);
			$out[] = XML::wrapClass( $anchorEl, 'bm_icon_link' );
		}
		return '<td style="width:' . 24*count($icons) . 'px">' . implode( '', $out ) . '</td>';
	}

	/**
	 * Method for the BibManager-Tag <biblist />
	 * @param String $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string List of used <bib />-tags
	 */
	public static function onBiblistTag ( $input, $args, $parser, $frame ) {
		global $wgBibManagerID;
		$parser->disableCache();

		$article = new Article( $parser->getTitle() );
		$content = $article->fetchContent();
		$parser->getOutput()->addModuleStyles( 'ext.bibManager.styles' );

		$out = array();
		$out[] = XML::element( 'hr', null, null );
		$out[] = wfMessage( 'bm_tag_tag-used' )->escaped();

		$bibTags = array ( );
		preg_match_all( '<bib.*?id=[\'"\ ]*(.*?)[\'"\ ].*?>', $content, $bibTags ); // TODO RBV (10.11.11 13:31): It might be better to have a db table for wikipage <-> citation relationship. This table could be updated in bib-Tag callback.
		if ( empty( $bibTags[0][0] ) ) {
			return wfMessage( 'bm_tag_no-tags-used' )->escaped(); //No Tags found
		}
		$entries = array ( );
		$repo = BibManagerRepository::singleton();

		//natsort( $bibTags[1] ); // TODO RBV (23.12.11 13:27): No sorting here!

		foreach ( $bibTags[1] as $citation ) {
			// TODO RBV (10.11.11 13:14): This is not good. If a lot of citations every citation will cause db query.
			$entries[$citation] = $repo->getBibEntryByCitation( $citation );
			//return $citation;
		}
		
		//$out[] = XML::openElement( 'table', array ( 'class' => 'bm_list_table' ) );

		// TODO RBV (23.12.11 13:28): Remove filtering
		if ( isset( $args['filter'] ) ) {
			$filterValues = explode( ',', $args['filter'] );
			foreach ( $filterValues as $val ) {
				$temp = explode( ':', trim( $val ) );
				$filter [$temp[0]] = $temp[1];
			}
		}
		//array_multisort($wgBibManagerID,$entries);
		$out = self::getTable($entries);
		//$out = '';
		return $out;

		//HINT: Maybe better way to find not existing entries after a _single_ db call.
		/*
		  $aMissingCits = array_diff(self::$aBibTagUsed, $aFoundCits);
		  foreach ($aMissingCits as $sCit){
		  $aOut [$sCit] = "<li><a href='".SpecialPage::getTitleFor("BibManagerCreate")->getLocalURL()."' >[".$sCit."]</a> (".wfMessage('bm_error_not-existing')->escaped().")</li>";
		  }
		 */
	}

	/**
	 * Method for the BibManager-Tag <bibprint />
	 * @global object $wgScript
	 * @param String $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function onBibprintTag ( $input, $args, $parser, $frame ) {
		global $wgUser;
		global $wgBibManagerCitationArticleNamespace;
		$parser->disableCache();

		if ( !isset( $args['filter'] ) && !isset($args['citation'] ))
			return '[' . wfMsg( 'bm_missing-filter' ) . ']';
		$repo = BibManagerRepository::singleton();
		if (isset($args['citation'])){
			$res [] = $repo->getBibEntryByCitation($args['citation']);
		}
		else {
			$filters = explode( ',', trim( $args['filter'] ) );
			$fieldsDefs = BibManagerFieldsList::getFieldDefinitions();
			$validFieldNames = array_keys( $fieldsDefs );
			$conds = array ( );
			foreach ( $filters as $val ) {
				$keyValuePairs = explode( ':', trim( $val ), 2 );
				if ( count( $keyValuePairs ) == 1 )
					continue; //No ':' included, so we skip it.

				$key = $keyValuePairs[0];
				if ( !in_array( $key, $validFieldNames ) )
					continue; //No valid DB field, so skip it.

				$values = explode( '|', $keyValuePairs[1] );
				$tmpCond = array ( );
				foreach ( $values as $value ) {
					$tmpCondPart = 'bm_' . $key . ' ';
					if ( strpos( $value, '%' ) !== false ) { //Truncating? We need a "LIKE"
						$tmpCondPart .= 'LIKE "' . $value . '"';
					} else {
						$tmpCondPart .= '= "' . $value . '"';
					}
					$tmpCond[] = $tmpCondPart;
				}

				$conds[] = implode( ' OR ', $tmpCond );
			}
			if ( empty( $conds ) )
				return '[' . wfMsg( 'bm_invalid-filter' ) . ']';
			
			$res = $repo->getBibEntries( $conds );
			if (isset($args['sort']) ){
				if (in_array( $args['sort'] , $validFieldNames )) {
					foreach ( $res as $key=>$val ) {
						$sorter[$key]=$res[$key]['bm_' . $args['sort']];
					}
					array_multisort($sorter,$res);
				} else {
					return 'Invalid sort field! Valid sorting values:<br>' . implode('<br>',$validFieldNames);
				}
			}
		}
		//return $args['sort'];
		
		$out = self::getTable($res);
		return $out;
	}

	public static function onSkinAfterContent ( &$data ) {
		$data .= self::onBiblistTag( null, null, null, null );
		return true;
	}
	public static function parseLatexString ( $input ) {
		$out = $input;
	
		$out = str_replace( '\{aa}',     'å', $out );
		$out = str_replace( '\\H{o}',    'ő', $out );
		$out = str_replace( '\\H{u}',  'ű', $out );
		$out = str_replace( '\{AA}',     'Å', $out );
		$out = str_replace( '\\H{O}',  'Ő', $out );
		$out = str_replace( '\\H{U}',  'Ü', $out );
		$out = str_replace( '\c{c}',     'ç', $out );
		$out = str_replace( '\c{C}',     'Ç', $out );

		$out = str_replace( '{',     '', $out );
		$out = str_replace( '}',     '', $out );

		
		$out = str_replace( '\\\'a',   'á', $out );
		$out = str_replace( '\\\'a',   'á', $out );
		$out = str_replace( '\\\'e',   'é', $out );
		$out = str_replace( '\\\'i',   'í', $out );
		$out = str_replace( '\\\'o',   'ó', $out );
		$out = str_replace( '\"o',     'ö', $out );
		$out = str_replace( '\\\'u',   'ú', $out );
		$out = str_replace( '\"u',     'ü', $out );
		$out = str_replace( '\^o',     'ô', $out );
		$out = str_replace( '\`o',     'ò', $out );
		$out = str_replace( '\~o',     'õ', $out );	
		$out = str_replace( '\"a',     'ä', $out );
		$out = str_replace( '\"i',     'ï', $out );
		

		$out = str_replace( '\\\'A',   'Á', $out );
		$out = str_replace( '\\\'A',   'Á', $out );
		$out = str_replace( '\\\'E',   'É', $out );
		$out = str_replace( '\\\'I',   'Í', $out );
		$out = str_replace( '\\\'O',   'Ó', $out );
		$out = str_replace( '\"O',     'Ö', $out );
		$out = str_replace( '\\\'U',   'Ú', $out );
		$out = str_replace( '\"U',     'Ű', $out );
		$out = str_replace( '\^O',     'Ô', $out );
		$out = str_replace( '\`O',     'Ò', $out );
		$out = str_replace( '\~O',     'Õ', $out );		
		$out = str_replace( '\"A',     'Ä', $out );
		$out = str_replace( '\"I',     'Ï', $out );
		
		$out = str_replace( '\\',     '', $out );

		return $out;
	}


	public static function formatEntry ( $entry, $formatOverride = '', $prefixedKeys = true ) {
		global $wgBibManagerCitationFormats;
		$format = $wgBibManagerCitationFormats['-']; //Use default
		if ( isset( $entry['bm_bibtexEntryType'] ) && !empty( $wgBibManagerCitationFormats[$entry['bm_bibtexEntryType']] ) ) {
			$format = !empty( $formatOverride ) ? $formatOverride : $wgBibManagerCitationFormats[$entry['bm_bibtexEntryType']];
		}

		foreach ( $entry as $key => $value ) { //Replace placeholders
			//Apply Latex syntax
			if ( empty( $value ) )
				continue;
			
			if ( $prefixedKeys )
				$key = substr( $key, 3 ); //'bm_title' --> 'title'
			
			if ( $key == 'author' || $key == 'editor' ) {
				$value = self::parseLatexString($value);
				$authors = explode( ' and ', $value );
				foreach ( $authors as $authors_key => $authors_value ) {
					//reverse the name order!
					$name = explode( ',', $authors_value );	
					foreach ( $name as $name_key => $name_value ) {
						$frags = explode( ' ', $name_value );
						if ($name_key == 1){
							foreach ( $frags as $frags_key => $frags_value ) {
								if ($frags_value != '')
									$frags[$frags_key] = mb_substr($frags[$frags_key], 0, 1, 'utf-8') . '.';
							}
						}
						$name[$name_key] = implode(' ' , $frags);
					}
					$authors[$authors_key] = implode( ' ' , array_reverse($name));
				}
				$value = implode( ', ' , $authors);
			}
			if ( $key == 'title' ) {
				$parts = explode( '$', $value );	
				foreach ( $parts as $parts_key => $parts_value ) {
					if ($parts_key&1){
						//insert here a math latex interpreter
						$parts[$parts_key] = '' ;
					} else {
						$parts[$parts_key] = self::parseLatexString($parts[$parts_key]);
					}
				} 
				$value = implode( '' , $parts);
			}
			if ( $key == 'editor' )
				$value .= wfMsg( 'bm_editor_addition' );

			if ( $key == 'url' ) {
				$urlKey = $prefixedKeys ? 'bm_url' : 'url';
				$value = ' '.XML::element(
					'a',
					array(
						'href'   => $entry[$urlKey],
						'target' => '_blank',
						'class'  => 'external',
						'rel'    => 'nofollow'
					),
					wfMsg( 'bm_url')
				);
			}
			if ( $key == 'url' ) {
				$urlKey = $prefixedKeys ? 'bm_url' : 'url';
				$value = ' '.XML::element(
					'a',
					array(
						'href'   => $entry[$urlKey],
						'target' => '_blank',
						'class'  => 'external',
						'rel'    => 'nofollow'
					),
					wfMsg( 'bm_url')
				);
			}
			if ( $key == 'pages' ) {
				$value = str_replace( '--',  '-', $value ); //fix for bad bibtex output from Physical Review
			}
			$format = str_replace( '%' . $key . '%', $value, $format );
		}
		//remove empty fields!
		$fieldsDefs = BibManagerFieldsList::getFieldDefinitions();
		$validFieldNames = array_keys( $fieldsDefs );
		//return implode(', ',$validFieldNames);
		foreach ( $validFieldNames as $key => $value )
			$format = str_replace( '%' . $value . '%', '-', $format );
		wfRunHooks( 'BibManagerFormatEntry', array ( $entry, $prefixedKeys, &$format ) );
		return $format;
	}

	public static function getTableEntry($citLink, $citFormat, $citIcons){
		$out = '';
		$out .= XML::openElement( 'tr' );
		$out .= '<td style="width:100px; text-align: left; vertical-align: top;">[' . $citLink . ']</td>';
		$out .= '<td>' . $citFormat . '</td>';
		$out .= '<td style="width:70px">' . $citIcons . '</td>';
		$out .= XML::closeElement( 'tr' );
		return $out;
	}

	public static function getTable($res){
		global $wgUser, $wgBibManagerCitationArticleNamespace;
		global $wgBibManagerID;
		$out = XML::openElement( 'table', array ( 'class' => 'bm_list_table' ) );
		if ( $res === false )
			return '[' . wfMsg( 'bm_no-data-found' ) . ']';
		foreach ( $res as $key=>$val ) {
			$oCitationTitle = Title::newFromText( $key, $wgBibManagerCitationArticleNamespace );
			//return $oCitationTitle;
			if (empty($val)){
				if ( empty($wgBibManagerID['' . $oCitationTitle]))
					$wgBibManagerID['' . $oCitationTitle]=count($wgBibManagerID)+1;
				$spTitle = SpecialPage::getTitleFor( 'BibManagerCreate' ); // TODO RBV (10.11.11 13:50): Dublicate code --> encapsulate
				$citLink = Linker::link(
				    $spTitle, '[' . $key . ']' , array ( 'class' => 'new' ), array ( 'bm_bibtexCitation' => $key ), array ( 'broken' => true )
				);
				$sLinkToEdit = SpecialPage::getTitleFor( 'BibManagerCreate' )->getLocalURL( array ( 'bm_bibtexCitation' => $key ));
				$citFormat = '<em>' . wfMsg('bm_error_not-existing');
				if ($wgUser->isAllowed('bibmanagercreate'))
					$citFormat .= XML::element('a', array('href' => $sLinkToEdit), wfMsg( 'bm_tag_click_to_create' ));
				$citFormat .='</em>';
				$citIcons = '';
			}
			else {
				$spTitle = Title::newFromText( $val['bm_bibtexCitation'], $wgBibManagerCitationArticleNamespace );
				if ( empty($wgBibManagerID['' . $spTitle]))
					$wgBibManagerID['' . $spTitle]=count($wgBibManagerID)+1;
				//$citLink = Linker::link(
				//    $title, $title->getText()
				//);
				//$citLink = Linker::link(
				//    $title, $wgBibManagerID['' . $val['bm_bibtexCitation']]
				//);
				if (empty($val['bm_url'])){
					$citLink = '[' . $wgBibManagerID['' . $spTitle ] . ']';
				} else {
					$oCitationUrl = Title::newFromText( $val['bm_url'], $wgBibManagerCitationArticleNamespace );
					$citLink = Linker::makeExternalLink($oCitationUrl, '[' . $wgBibManagerID['' . $spTitle] . ']', false);
				}
				//return $title;
				//return count($wgBibManagerID);
				//return $wgBibManagerID['' . $title];
				$citFormat = self::formatEntry( $val );
				$citIcons = self::getIcons( $val );
			}
			$out .= XML::openElement( 'tr' );
			$out .= '<td style="width:30px; text-align: left; vertical-align: top;">' . $citLink . '</td>';
			$out .= '<td>' . $citFormat . '</td>';
			//$out .= '<td style="width:100px">' . $citIcons . '</td>';
			$out .= $citIcons;
			$out .= XML::closeElement( 'tr' );
		}
		$out .= XML::closeElement( 'table' );
		return $out;
	}
}
