<?php


namespace CirrusSearch\Api;
use \ApiBase;
use \CirrusSearch\Connection;

/**
 * Dumps CirrusSearch mappings for easy viewing.
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
class SettingsDump extends ApiBase {
	public function execute() {
		foreach( Connection::getAllIndexTypes() as $index ) {
			$this->getResult()->addValue( array( $index, 'page' ), 'index',
				Connection::getIndex( wfWikiId(), $index )->getSettings()->get() );
		}
	}

	public function getAllowedParams() {
		return array();
	}

	public function getParamDescription() {
		return array();
	}

	public function getDescription() {
		return 'Dump of CirrusSearch mapping for this wiki.';
	}
}
