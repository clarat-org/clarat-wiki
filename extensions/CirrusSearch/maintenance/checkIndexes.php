<?php

namespace CirrusSearch;
use \Maintenance;

/**
 * Check that all Cirrus indexes report OK.
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

class CheckIndexes extends Maintenance {
	private $errors = array();
	private $path;
	private $clusterState;
	private $cirrusInfo;

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Check that all Cirrus indexes report OK.";
		$this->addOption( 'nagios', 'Output in nagios format' );
	}

	public function execute() {
		if ( $this->hasOption( 'nagios' ) ) {
			// Force silent running mode so we can match Nagio's expected output.
			$this->mQuiet = true;
		}
		$this->ensureClusterStateFetched();
		$this->ensureCirrusInfoFetched();
		$this->checkIndex( 'mw_cirrus_versions', 1 );
		$aliases = array();
		foreach ( $this->clusterState[ 'metadata' ][ 'indices' ] as $indexName => $data ) {
			foreach ( $data[ 'aliases' ] as $alias ) {
				$aliases[ $alias ][] = $indexName;
			}
		}
		foreach ( $this->cirrusInfo as $alias => $data ) {
			foreach ( $aliases[ $alias ] as $indexName ) {
				$this->checkIndex( $indexName, $data[ 'shard_count'] );
			}
		}
		$indexCount = count( $this->cirrusInfo );
		$errCount = count( $this->errors );
		if ( $this->hasOption( 'nagios' ) ) {
			// Exit silent running mode so we can log Nagios style output
			$this->mQuiet = false;
			if ( $errCount > 0 ) {
				$this->output( "CIRRUSSEARCH CRITICAL - $indexCount indexes report $errCount errors\n" );
			} else {
				$this->output( "CIRRUSSEARCH OK - $indexCount indexes report 0 errors\n" );
			}
		}
		$this->printErrorRecursive( '', $this->errors );
		// If there are error use the nagios error codes to signal them
		if ( $errCount > 0 ) {
			die( 2 );
		}
	}

	private function checkIndex( $indexName, $expectedShardCount ) {
		$this->path = array();
		$metdata = $this->getIndexMetadata( $indexName );
		$this->in( $indexName );
		if ( $metdata === null ) {
			$this->err( "does not exist" );
			return;
		}
		$this->check( 'state', 'open', $metdata[ 'state' ] );
		// TODO check aliases

		$routingTable = $this->getIndexRoutingTable( $indexName );
		$this->check( 'shard count', $expectedShardCount, count( $routingTable[ 'shards' ] ) );
		foreach ( $routingTable[ 'shards' ] as $shardIndex => $shardRoutingTable ) {
			$this->in( "shard $shardIndex" );
			foreach ( $shardRoutingTable as $replicaIndex => $replica ) {
				$this->in( "replica $replicaIndex" );
				$this->check( 'state', array( 'STARTED', 'RELOCATING' ), $replica[ 'state' ] );
				$this->out();
			}
			$this->out();
		}
		$this->out();
	}

	private function in( $header ) {
		$this->path[] = $header;
		$this->output( str_repeat( "\t", count( $this->path ) - 1 ) );
		$this->output( "$header...\n" );
	}
	private function out() {
		array_pop( $this->path );
	}
	private function check( $name, $expected, $actual ) {
		$this->output( str_repeat( "\t", count( $this->path ) ) );
		$this->output( "$name...");
		if ( is_array( $expected ) ) {
			if ( in_array( $actual, $expected ) ) {
				$this->output( "ok\n" );
			} else {
				$expectedStr = implode( ', ', $expected );
				$this->output( "$actual not in [$expectedStr]\n" );
				$this->err( "expected $name to be in [$expectedStr] but was $actual" );
			}
		} else {
			if ( $expected === $actual ) {
				$this->output( "ok\n" );
			} else {
				$this->output( "$expected != $actual\n" );
				$this->err( "expected $name to be '$expected' but was '$actual'" );
			}
		}
	}
	private function err( $explanation ) {
		$err = $this->path;
		$err[] = $explanation;
		$e = &$this->errors;
		foreach ( $this->path as $element ) {
			$e = &$e[ $element ];
		}
		$e[] = $explanation;
	}
	private function printErrorRecursive( $indent, $array ) {
		foreach ( $array as $key => $value ) {
			$line = $indent;
			if ( !is_numeric( $key ) ) {
				$line .= "$key...";
			}
			if ( is_array( $value ) ) {
				$this->error( $line );
				$this->printErrorRecursive( "$indent\t", $value );
			} else {
				$line .= $value;
				if ( $this->hasOption( 'nagios' ) ) {
					$this->output( "$line\n" );
				} else {
					$this->error( $line );
				}
			}
		}
	}

	private function getIndexMetadata( $indexName ) {
		if ( isset( $this->clusterState[ 'metadata' ][ 'indices' ][ $indexName ] ) ) {
			return $this->clusterState[ 'metadata' ][ 'indices' ][ $indexName ];
		}
		return null;
	}

	private function getIndexRoutingTable( $indexName ) {
		return $this->clusterState[ 'routing_table' ][ 'indices' ][ $indexName ];
	}

	private function ensureClusterStateFetched() {
		if ( $this->clusterState === null ) {
			$this->clusterState = Connection::getClient()->request( '_cluster/state' )->getData();
		}
	}
	private function ensureCirrusInfoFetched() {
		if ( $this->cirrusInfo === null ) {
			$query = new \Elastica\Query();
			$query->setSize( 5000 );
			$res = Connection::getIndex( 'mw_cirrus_versions' )->getType( 'version' )
				->getIndex()->search( $query );
			$this->cirrusInfo = array();
			foreach( $res as $r ) {
				$data = $r->getData();
				$this->cirrusInfo[ $r->getId() ] = array(
					'shard_count' => $data[ 'shard_count' ],
				);
			}
		}
	}
}

$maintClass = "CirrusSearch\CheckIndexes";
require_once RUN_MAINTENANCE_IF_MAIN;
