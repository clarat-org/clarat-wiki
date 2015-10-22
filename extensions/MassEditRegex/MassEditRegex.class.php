<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
/**
 * Allow users in the Bot group to edit many articles in one go by applying
 * regular expressions to a list of pages.
 *
 * @file
 * @ingroup SpecialPage
 *
 * @link http://www.mediawiki.org/wiki/Extension:MassEditRegex Documentation
 *
 * @author Adam Nielsen <malvineous@shikadi.net>
 * @copyright Copyright © 2009,2013 Adam Nielsen
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/// Maximum number of pages/diffs to display when previewing the changes
define('MER_MAX_PREVIEW_DIFFS', 20);

/// Maximum number of pages to edit.
define('MER_MAX_EXECUTE_PAGES', 1000);

/** Main class that define a new special page*/
class MassEditRegex extends SpecialPage {
	private $aPageList;       ///< Array of string - user-supplied page titles
	private $strPageListType; ///< Type of titles (categories, backlinks, etc.)
	private $strMatch;        ///< Match regex from form
	private $strReplace;      ///< Substitution regex from form
	private $aMatch;          ///< $strMatch exploded into array
	private $aReplace;        ///< $strReplace exploded into array
	private $strSummary;      ///< Edit summary
	private $diff;            ///< Access to diff engine

	function __construct() {
		parent::__construct( 'MassEditRegex', 'masseditregex' );
	}

	/// Run the regexes.
	function execute( $par ) {
		global $wgUser;

		$wgOut = $this->getOutput();

		$this->setHeaders();

		// Check permissions
		if ( !$wgUser->isAllowed( 'masseditregex' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		// If user is blocked, s/he doesn't need to access this page
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		$this->outputHeader();

		$wgRequest = $this->getRequest();
		$strPageList = $wgRequest->getText( 'wpPageList', 'Sandbox' );
		$this->aPageList = explode("\n", trim($strPageList));
		$this->strPageListType = $wgRequest->getText( 'wpPageListType', 'pagenames' );

		$this->iNamespace = $wgRequest->getInt( 'namespace', NS_MAIN );

		$this->strMatch = $wgRequest->getText( 'wpMatch', '/hello (.*)\n/' );
		$this->aMatch = explode("\n", trim($this->strMatch));

		$this->strReplace = $wgRequest->getText( 'wpReplace', 'goodbye $1' );
		$this->aReplace = explode("\n", $this->strReplace);

		$this->strSummary = $wgRequest->getText( 'wpSummary', '' );

		// Replace \n in the match with an actual newline (since a newline can't
		// be typed in, it'll act as the splitter for the next regex)
		foreach ( $this->aReplace as &$str ) {
			// Convert \n into a newline, \\n into \n, \\\n into \<newline>, etc.
			$str = preg_replace(
				array(
					'/(^|[^\\\\])((\\\\)*)(\2)\\\\n/',
					'/(^|[^\\\\])((\\\\)*)(\2)n/'
				), array(
					"\\1\\2\n",
					"\\1\\2n"
				), $str);
		}

		if ( $wgRequest->wasPosted() ) {
			$this->perform( !$wgRequest->getCheck('wpSave') );
		} else {
			$this->showForm();
			$this->showHints();
		}

	}

	/// Display the form requesting the regexes from the user.
	function showForm() {
		$wgOut = $this->getOutput();

		$wgOut->addWikiMsg( 'masseditregextext' );
		$titleObj = SpecialPage::getTitleFor( 'MassEditRegex' );

		$wgOut->addHTML(
			Xml::openElement('form', array(
				'id' => 'masseditregex',
				'method' => 'post',
				'action' => $titleObj->getLocalURL('action=submit')
			)) .
			Xml::element('p',
				null, wfMsg( 'masseditregex-pagelisttxt' )
			) .
			Xml::textarea(
				'wpPageList',
				join( "\n", $this->aPageList )
			) .
			Xml::element('span',
				null, wfMsg( 'masseditregex-listtype-intro' )
			) .
			Xml::openElement('ul', array(
				'style' => 'list-style: none' // don't want any bullets for radio btns
			))
		);

		// Generate HTML for the radio buttons (one for each list type)
		foreach (array('pagenames', 'pagename-prefixes', 'categories', 'backlinks')
			as $strValue)
		{
			// Have to use openElement because putting an Xml::xxx return value
			// inside an Xml::element causes the HTML code to be escaped and appear
			// on the page.
			$wgOut->addHTML(
				Xml::openElement('li') .
				// Give grep a chance to find the usages:
				// masseditregex-listtype-pagenames, masseditregex-listtype-pagename-prefixes,
				// masseditregex-listtype-categories, masseditregex-listtype-backlinks
				Xml::radioLabel(
					wfMsg( 'masseditregex-listtype-' . $strValue ),
					'wpPageListType',
					$strValue,
					'masseditregex-radio-' . $strValue,
					$strValue == $this->strPageListType
				) .
				Xml::closeElement('li')
			);
		}
		$wgOut->addHTML(
			Xml::closeElement('ul') .

			// Display the textareas for the regex and replacement to go into

			// Can't use Xml::buildTable because we need to put code into the table
			Xml::openElement('table', array(
				'style' => 'width: 100%'
			)) .
				Xml::openElement('tr') .
					Xml::openElement('td') .
						Xml::element('p', null, wfMsg( 'masseditregex-matchtxt' )) .
						Xml::textarea(
							'wpMatch',
							$this->strMatch  // use original value
						) .
					Xml::closeElement('td') .
					Xml::openElement('td') .
						Xml::element('p', null, wfMsg( 'masseditregex-replacetxt' )) .
						Xml::textarea(
							'wpReplace',
							$this->strReplace  // use original value
						) .
					Xml::closeElement('td') .
					Xml::closeElement('tr') .
			Xml::closeElement('table') .

			Xml::openElement( 'div', array( 'class' => 'editOptions' ) ) .

			// Display the edit summary and preview

			Xml::tags( 'span',
				array(
					'class' => 'mw-summary',
					'id' => 'wpSummaryLabel'
				),
				Xml::tags( 'label', array(
					'for' => 'wpSummary'
				), wfMsg( 'summary' ) )
			) . ' ' .

			Xml::input( 'wpSummary',
				60,
				$this->strSummary,
				array(
					'id' => 'wpSummary',
					'maxlength' => '200',
					'tabindex' => '1'
				)
			) .

			Xml::tags( 'div',
				array( 'class' => 'mw-summary-preview' ),
				wfMsgExt( 'summary-preview', 'parseinline' ) .
					Linker::commentBlock( $this->strSummary )
			) .
			Xml::closeElement( 'div' ) . // class=editOptions

			// Display the preview + execute buttons

			Xml::element('input', array(
				'id'        => 'wpSave',
				'name'      => 'wpSave',
				'type'      => 'submit',
				'value'     => wfMsg( 'masseditregex-executebtn' ),
				'accesskey' => wfMsg( 'accesskey-save' ),
				'title'     => wfMsg( 'masseditregex-tooltip-execute' ).' ['.wfMsg( 'accesskey-save' ).']',
			)) .

			Xml::element('input', array(
				'id'        => 'wpPreview',
				'name'      => 'wpPreview',
				'type'      => 'submit',
				'value'     => wfMsg('showpreview'),
				'accesskey' => wfMsg('accesskey-preview'),
				'title'     => wfMsg( 'tooltip-preview' ).' ['.wfMsg( 'accesskey-preview' ).']',
			))

		);

		$wgOut->addHTML( Xml::closeElement('form') );
	}

	/// Show a short table of regex examples.
	function showHints() {
		global $wgOut;

		$wgOut->addHTML(
			Xml::element( 'p', null, wfMsg( 'masseditregex-hint-intro' ) )
		);
		$wgOut->addHTML(Xml::buildTable(

			// Table rows (the hints)
			array(
				array(
					'/$/',
					'abc',
					wfMsg( 'masseditregex-hint-toappend' )
				),
				array(
					'/$/',
					'\\n[[Category:New]]',
					// Since we can't pass "rowspan=2" to the hint text above, we'll
					// have to display it again
					wfMsg( 'masseditregex-hint-toappend' )
				),
				array(
					'/{{OldTemplate}}/',
					'',
					wfMsg( 'masseditregex-hint-remove' )
				),
				array(
					'/\\[\\[Category:[^]]+\]\]/',
					'',
					wfMsg( 'masseditregex-hint-removecat' )
				),
				array(
					'/(\\[\\[[^]]*\\|[^]]*)AAA(.*\\]\\])/',
					'$1BBB$2',
					wfMsg( 'masseditregex-hint-renamelink' )
				),
			),

			// Table attributes
			array(
				'class' => 'wikitable'
			),

			// Table headings
			array(
				wfMsg( 'masseditregex-hint-headmatch' ), // really needs width 12em
				wfMsg( 'masseditregex-hint-headreplace' ), // really needs width 12em
				wfMsg( 'masseditregex-hint-headeffect' )
			)

		)); // Xml::buildTable

	}

	/// Apply all the regexes to a single page.
	/**
	 * @param Title $title
	 *   Page to alter (or preview.)
	 *
	 * @param bool $isPreview
	 *   true to generate a diff, false to alter the page content.
	 *
	 * @param string $htmlDiff
	 *   On return, contains HTML for the diff, if $isPreview was true.
	 *
	 * @return true on success, false if the page could not be found.
	 *
	 * @throw UsageException if the regex was invalid.
	 */
	function editPage( $title, $isPreview, &$htmlDiff ) {
		global $wgOut, $wgLang, $wgUser;

		$article = new Article($title);
		$rev = Revision::newFromTitle($title, 0, Revision::READ_LATEST);
		if (!$rev) return false;
		$content = $rev->getContent(Revision::FOR_THIS_USER, $wgUser);
		if (!$content) return false;
		$curText = $content->getNativeData();

		$iCount = 0;
		$newText = $curText;
		foreach ( $this->aMatch as $i => $strMatch ) {
			$strNextReplace = $this->aReplace[$i];
			$result = @preg_replace_callback( $strMatch,
				function ( $aMatches ) use($strNextReplace){
					$strFind = array();
					$strReplace = array();
					foreach ($aMatches as $i => $strMatch) {
						$aFind[] = '$' . $i;
						$aReplace[] = $strMatch;
					}
					return str_replace($aFind, $aReplace, $strNextReplace);
				}, $newText, -1, $iCount );
			if ($result !== null) {
				$newText = $result;
			} else {
				throw new UsageException( wfMsg( 'masseditregex-badregex' ) . ' <b>'
					. htmlspecialchars( $strMatch ) . '</b>', 'masseditregex-badregex' );
			}
		}

		if ( $isPreview ) {
			// In preview mode, display the first few diffs
			$this->diff->setText( $curText, $newText );
			$htmlDiff .= $this->diff->getDiff( '<b>'
				. htmlspecialchars( $title->getPrefixedText() ) . ' - '
				. wfMsg('masseditregex-before') . '</b>',
				'<b>' . wfMsg('masseditregex-after') . '</b>' );
		} else {
			// Not in preview mode, make the edits
			$wgOut->addHTML( '<li>' . wfMsg( 'masseditregex-num-changes',
					htmlspecialchars( $title->getPrefixedText() ), $iCount ) . '</li>' );

			if ( strcmp( $curText, $newText ) != 0 ) {
				$newContent = new WikitextContent( $newText );
				$article->doEditContent( $newContent, $this->strSummary,
					EDIT_UPDATE | EDIT_FORCE_BOT | EDIT_DEFER_UPDATES,
					$rev->getId() );
			}
		}
		return true;
	}

	/// Perform the regex process.
	/**
	 * @param bool $isPreview
	 *   true to generate diffs, false to perform page edits.
	 */
	function perform( $isPreview ) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		$pageCountLimit = $isPreview ? MER_MAX_PREVIEW_DIFFS : MER_MAX_EXECUTE_PAGES;
		$errors = array();

		if ( $isPreview ) {
			$this->diff = new DifferenceEngine();
			$this->diff->showDiffStyle(); // send CSS link to the browser for diff colours
			$htmlDiff = '';
		} else {
			$wgOut->addHTML( '<ul>' );
		}

		$iArticleCount = 0;
		try {
			foreach ( $this->aPageList as $pageTitle ) {
				$titleArray = array();
				switch ($this->strPageListType) {
					case 'pagenames': // Can do this in one hit
						$t = Title::newFromText( $pageTitle );
						if ( !$t || !$this->editPage( $t, $isPreview, $htmlDiff ) ) {
							$errors[] = wfMsg( 'masseditregex-page-not-exists',
								htmlspecialchars( $pageTitle ) );
						}
						$iArticleCount++;
						break;

					case 'pagename-prefixes':
						$titles = PrefixSearch::titleSearch( $pageTitle,
							$pageCountLimit - $iArticleCount );
						if ( empty( $titles ) ) {
							$errors[] = wfMsg( 'masseditregex-exprnomatch',
								htmlspecialchars( $pageTitle ) );
							$iArticleCount++;
							continue;
						}

						foreach ( $titles as $title ) {
							$t = Title::newFromText( $title );
							if ( !$t ) {
								$errors[] = wfMsg( 'masseditregex-page-not-exists', $title );
							} else {
								$titleArray[] = $t;
							}
						}
						break;

					case 'categories':
						$cat = Category::newFromName($pageTitle);
						if ( $cat === false ) {
							$errors[] = wfMsg( 'masseditregex-page-not-exists',
								htmlspecialchars( $pageTitle ) );
							break;
						}
						$titleArray = $cat->getMembers($pageCountLimit - $iArticleCount);
						break;

					case 'backlinks':
						$t = Title::newFromText($pageTitle);
						if ( !$t ) {
							if ( $isPreview ) {
								$errors[] = wfMsg( 'masseditregex-page-not-exists',
									htmlspecialchars( $pageTitle ) );
							}
							continue;
						}
						$blc = $t->getBacklinkCache();
						if ( $t->getNamespace() == NS_TEMPLATE ) {
							// Backlinks for Template pages are in a different table
							$table = 'templatelinks';
						} else {
							$table = 'pagelinks';
						}
						$titleArray = $blc->getLinks($table, false, false,
							$pageCountLimit - $iArticleCount);
						break;
				}

				// If the above switch produced an array of pages, run through them now
				foreach ( $titleArray as $target ) {
					if ( !$this->editPage( $target, $isPreview, $htmlDiff ) ) {
						$errors[] = wfMsg( 'masseditregex-page-not-exists',
							htmlspecialchars( $target->getPrefixedText() ) );
					}
					$iArticleCount++;
					if ( $iArticleCount >= $pageCountLimit ) {
						$htmlDiff .= Xml::element('p', null,
							wfMsg( 'masseditregex-max-preview-diffs',
								$wgLang->formatNum( $pageCountLimit )
							)
						);
						break;
					}
				}

			}
		} catch (UsageException $e) {
			$errors[] = $e;

			// Force a preview if there was a bad regex
			if ( !$isPreview ) {
				$wgOut->addHTML( '</ul>' );
			}
			$isPreview = true;
		}

		if ( !$isPreview ) {
			$wgOut->addHTML( '</ul>' );
		}

		if ( ( $iArticleCount == 0 ) && empty( $errors ) ) {
			$errors[] = wfMsg( 'masseditregex-err-nopages' );
			// Force a preview if there was nothing to do
			$isPreview = true;
		}

		if ( !empty($errors ) ) {
			$wgOut->addHTML( '<div class="errorbox">' );
			$wgOut->addHTML( wfMsg( 'masseditregex-editfailed' ) );

			$wgOut->addHTML( '<ul><li>' );
			$wgOut->addHTML( join( '</li><li> ', $errors) );
			$wgOut->addHTML( '</li></ul></div>' );
		}

		if ( $isPreview ) {
			// Show the form again ready for further editing if we're just previewing
			$this->showForm();

			// Show the diffs now (after any errors)
			$wgOut->addHTML( $htmlDiff );
		} else {
			$wgOut->addWikiMsg( 'masseditregex-num-articles-changed', $iArticleCount );
			$wgOut->addHTML(
				Linker::makeKnownLinkObj(
					SpecialPage::getSafeTitleFor( 'Contributions', $wgUser->getName() ),
					wfMsgHtml( 'masseditregex-view-full-summary' )
				)
			);
		}
	}

	public static function efSkinTemplateNavigationUniversal( &$sktemplate, &$links )
	{
		$title = $sktemplate->getTitle();
		$ns = $title->getNamespace();
		if ( $ns == NS_CATEGORY ) {
			$url = SpecialPage::getTitleFor( 'MassEditRegex' )->getLocalURL(
				array(
					'wpPageList' => $title->getText(),
					'wpPageListType' => 'categories',
				)
			);
		} elseif (
			( $ns == NS_SPECIAL )
			&& ( $title->isSpecial( 'Whatlinkshere' ) )
		) {
			$titleParts = SpecialPageFactory::resolveAlias($title->getText());

			$url = SpecialPage::getTitleFor( 'MassEditRegex' )->getLocalURL(
				array(
					'wpPageList' => $titleParts[1],
					'wpPageListType' => 'backlinks',
				)
			);
		} else {
			// No tab
			return true;
		}

		$links['views']['masseditregex'] = array(
			'class' => false,
			'text' => wfMessage('masseditregex-editall')->text(),
			'href' => $url,
			'context' => 'main',
		);
		return true;
	}

	public static function efBaseTemplateToolbox( &$tpl, &$toolbox ) {
		if ( !$tpl->getTitle()->isSpecial( 'MassEditRegex' ) ) return true;

		// Hide the 'printable version' link as the shortcut key conflicts with
		// the preview button.
		unset($toolbox['print']);
		return true;
	}

}
