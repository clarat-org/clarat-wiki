<?php

namespace CirrusSearch\Sanity;
use \Title;
use \WikiPage;

/**
 * Remediation actions for insanity in the search index.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

interface Remediator {
	/**
	 * There is a redirect in the index.
	 * @param WikiPage $page the page in the index
	 */
	public function redirectInIndex( $page );
	/**
	 * A page isn't in the index.
	 * @param WikiPage $page not in the index
	 */
	public function pageNotInIndex( $page );
	/**
	 * A non-existent page is in the index.  Odds are good it was deleted.
	 * @param int $pageId id of the deleted page
	 * @param Title $title title of the page read from the ghost
	 */
	public function ghostPageInIndex( $pageId, $title );
	/**
	 * An existent page is in more then one index.
	 * @param WikiPage $page page in too many indexes
	 * @param string $indexType index type that the page is in but shouldn't be in
	 */
	public function pageInWrongIndex( $page, $indexType );
}

/**
 * Remediator that takes no actions.
 */
class NoopRemediator implements Remediator {
	public function redirectInIndex( $page ) {}
	public function pageNotInIndex( $page ) {}
	public function ghostPageInIndex( $pageId, $title ) {}
	public function pageInWrongIndex( $page, $indexType ) {}
}

/**
 * Decorating Remediator that logs the prints the errors.
 */
class PrintingRemediator implements Remediator {
	private $next;

	/**
	 * Build the remediator.
	 * @param Remediator $next the rememediator that this one decorates
	 */
	public function __construct( $next ) {
		$this->next = $next;
	}

	public function redirectInIndex( $page ) {
		$this->log( $page->getId(), $page->getTitle(), 'Redirect in index' );
		$this->next->redirectInIndex( $page );
	}
	public function pageNotInIndex( $page ) {
		$this->log( $page->getId(), $page->getTitle(), 'Page not in index' );
		$this->next->pageNotInIndex( $page );
	}
	public function ghostPageInIndex( $pageId, $title ) {
		$this->log( $pageId, $title, 'Deleted page in index' );
		$this->next->ghostPageInIndex( $pageId, $title );
	}
	public function pageInWrongIndex( $page, $indexType ) {
		$this->log( $page->getId(), $page->getTitle(), "Page in wrong index: $indexType" );
		$this->next->pageInWrongIndex( $page, $indexType );
	}
	private function log( $pageId, $title, $message ) {
		printf("%30s %10d %s\n", $message, $pageId, $title );
	}
}
