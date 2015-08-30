<?php

namespace CirrusSearch;
use Elastica;
use \Maintenance;

/**
 * Returns zero status if a Cirrus index needs to be built for this wiki.  If
 * Elasticsearch doesn't look to be up it'll wait a minute for it to come up.
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

$IP = getenv( 'MW_INSTALL_PATH' );
if( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once( "$IP/maintenance/Maintenance.php" );

class CirrusIsSetup extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Update the configuration or contents of all search indecies." );
	}

	public function execute() {
		$end = microtime( true ) + 60;
		while ( true ) {
			try {
				$health = new \Elastica\Cluster\Health ( Connection::getClient() );
				$status = $health->getStatus();
				$this->output( "Elasticsearch status:  $status\n" );
				if ( $status === 'green' ) {
					break;
				}
			} catch ( \Elastica\Exception\Connection\HttpException $e ) {
				if ( $e->getError() === CURLE_COULDNT_CONNECT ) {
					$this->output( "Elasticsearch not up.\n" );
					Connection::destroySingleton();
				} else {
					// The two exit code here makes puppet fail with an error.
					$this->error( 'Connection error:  ' . $e->getMessage(), 2 );
				}
			}
			if ( $end < microtime( true ) ) {
				$this->error( 'Elasticsearch was not ready in time.', 1 );
			}
			sleep( 1 );
		}

		foreach ( Connection::getAllIndexTypes() as $indexType ) {
			try {
				$count = Connection::getPageType( wfWikiId(), $indexType )->count();
			} catch ( \Elastica\Exception\ResponseException $e ) {
				$this->output( "$indexType doesn't exist.\n" );
				$this->error( "true" );
				exit( 0 );
			}
			if ( $indexType === 'content' && $count === 0 ) {
				$this->output( "No pages in the content index.  Indexes were probably wiped.\n" );
				exit( 0 );
			}
			$this->output( "Page count in $indexType:  $count\n" );
		}
		// The 1 exit code here makes puppet decide that it needs to run whatever is gated by this.
		exit( 1 );
	}
}

$maintClass = "CirrusSearch\CirrusIsSetup";
require_once RUN_MAINTENANCE_IF_MAIN;
