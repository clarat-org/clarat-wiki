<?php

namespace CirrusSearch\Maintenance;

use \CirrusSearch\Connection;
use \Elastica\Document;
use \Elastica\Query\MatchAll;

/**
 * Index all namespaces for quick lookup.
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
require_once( __DIR__ . '/../includes/Maintenance/Maintenance.php' );

class IndexNamespaces extends Maintenance {
	public function execute() {
		global $wgContLang;

		$type = Connection::getNamespaceType( wfWikiId() );

		$this->outputIndented( "Deleting namespaces..." );
		$type->deleteByQuery( new MatchAll() );
		$this->output( "done\n" );

		$this->outputIndented( "Indexing namespaces..." );
		$namesById = array();
		foreach ( $wgContLang->getNamespaceIds() as $name => $id ) {
			if ( $name ) {
				$namesById[ $id ][] = $name;
			}
		}
		$documents = array();
		foreach ( $namesById as $id => $names ) {
			$documents[] = new Document( $id, array( 'name' => $names ) );
		}
		$type->addDocuments( $documents );
		$this->output( "done\n" );
	}
}

$maintClass = "CirrusSearch\Maintenance\IndexNamespaces";
require_once RUN_MAINTENANCE_IF_MAIN;
