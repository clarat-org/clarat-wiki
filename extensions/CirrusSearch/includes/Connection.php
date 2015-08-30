<?php

namespace CirrusSearch;
use \ElasticaConnection;
use \MWNamespace;

/**
 * Forms and caches connection to Elasticsearch as well as client objects
 * that contain connection information like \Elastica\Index and \Elastica\Type.
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
class Connection extends ElasticaConnection {
	/**
	 * Name of the index that holds content articles.
	 * @var string
	 */
	const CONTENT_INDEX_TYPE = 'content';
	
	/**
	 * Name of the index that holds non-content articles.
	 * @var string
	 */
	const GENERAL_INDEX_TYPE = 'general';

	/**
	 * Name of the page type.
	 * @var string
	 */
	const PAGE_TYPE_NAME = 'page';

	/**
	 * Name of the namespace type.
	 * @var string
	 */
	const NAMESPACE_TYPE_NAME = 'namespace';

	/**
	 * @return array(string)
	 */
	public function getServerList() {
		global $wgCirrusSearchServers;
		return $wgCirrusSearchServers;
	}

	/**
	 * How many times can we attempt to connect per host?
	 *
	 * @return int
	 */
	public function getMaxConnectionAttempts() {
		global $wgCirrusSearchConnectionAttempts;
		return $wgCirrusSearchConnectionAttempts;
	}

	/**
	 * Fetch the Elastica Type for pages.
	 * @param mixed $name basename of index
	 * @param mixed $type type of index (content or general or false to get all)
	 * @return \Elastica\Type
	 */
	public static function getPageType( $name, $type = false ) {
		return self::getIndex( $name, $type )->getType( self::PAGE_TYPE_NAME );
	}

	/**
	 * Fetch the Elastica Type for namespaces.
	 * @param mixed $name basename of index
	 * @return \Elastica\Type
	 */
	public static function getNamespaceType( $name ) {
		$type = 'general'; // Namespaces are always stored in the 'general' index.
		return self::getIndex( $name, $type )->getType( self::NAMESPACE_TYPE_NAME );
	}
	/**
	 * Get all index types we support, content, general, plus custom ones
	 *
	 * @return array(string)
	 */
	public static function getAllIndexTypes() {
		global $wgCirrusSearchNamespaceMappings;
		return array_merge( array_values( $wgCirrusSearchNamespaceMappings ),
			array( self::CONTENT_INDEX_TYPE, self::GENERAL_INDEX_TYPE ) );
	}

	/**
	 * Get the index suffix for a given namespace
	 * @param int $namespace A namespace id
	 * @return string
	 */
	public static function getIndexSuffixForNamespace( $namespace ) {
		global $wgCirrusSearchNamespaceMappings;
		if ( isset( $wgCirrusSearchNamespaceMappings[$namespace] ) ) {
			return $wgCirrusSearchNamespaceMappings[$namespace];
		}

		return MWNamespace::isContent( $namespace ) ?
			self::CONTENT_INDEX_TYPE : self::GENERAL_INDEX_TYPE;
	}

	/**
	 * Is there more then one namespace in the provided index type?
	 * @var string $indexType an index type
	 * @return false|integer false if the number of indexes is unknown, an integer if it is known
	 */
	public static function namespacesInIndexType( $indexType ) {
		global $wgCirrusSearchNamespaceMappings,
			$wgContentNamespaces;

		if ( $indexType === self::GENERAL_INDEX_TYPE ) {
			return false;
		}

		$count = count( array_keys( $wgCirrusSearchNamespaceMappings, $indexType ) );
		if ( $indexType === self::CONTENT_INDEX_TYPE ) {
			// The content namespace includes everything set in the mappings to content (count right now)
			// Plus everything in wgContentNamespaces that isn't already in namespace mappings
			$count += count( array_diff( $wgContentNamespaces, array_keys( $wgCirrusSearchNamespaceMappings ) ) );
		}
		return $count;
	}
}
