<?php

namespace CirrusSearch\Extra\Filter;

use Elastica\Filter\AbstractFilter;

/**
 * Source regex filter for trigram accelerated regex matching.
 *
 * @link https://github.com/wikimedia/search-extra/blob/master/docs/source_regex.md
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

class SourceRegex extends AbstractFilter {
    /**
     * @param null|string $regex optional regex to match against field
     * @param null|string $field optional field who's source to check with the regex
     * @param null|string $ngramField optional field that is indexed with ngrams to
     * accelerate regex matching
     */
    public function __construct( $regex = null, $field = null, $ngramField = null ) {
        if ( $regex ) {
            $this->setRegex( $regex );
        }
        if ( $field ) {
            $this->setField( $field );
        }
        if ( $ngramField ) {
            $this->setNgramField( $ngramField );
        }
    }

    /**
     * @param string $regex regex to match against field
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setRegex( $regex ) {
        return $this->setParam( 'regex', $regex );
    }

    /**
     * @param string $field field who's source to check with the regex
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setField( $field ) {
        return $this->setParam( 'field', $field );
    }

    /**
     * @param string $ngramField field that is indexed with ngrams to
     * accelerate regex matching
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setNGramField( $ngramField ) {
        return $this->setParam( 'ngram_field', $ngramField );
    }

    /**
     * @param int $gramSize size of the ngrams extracted for acccelerating
     * the regex.  Defaults to 3 if not set.  That gram size must have been
     * produced by analyzing the ngramField.
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setGramSize( $gramSize ) {
        return $this->setParam( 'gram_size', $gramSize );
    }

    /**
     * @param int $maxExpand maximum range before outgoing automaton arcs are
     * ignored. Roughly corresponds to the maximum number of characters in a
     * character class ([abcd]) before it is treated as . for purposes of
     * acceleration. Defaults to 4.
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setMaxExpand( $maxExpand ) {
        return $this->setParam( 'max_expand', $maxExpand );
    }

    /**
     * @param int $maxStatesTraced maximum number of automaton states that can
     * be traced before the algorithm gives up and assumes the regex is too
     * complex and throws an error back to the user. Defaults to 10000 which
     * handily covers all regexes I cared to test.
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setMaxStatesTraced( $maxStatesTraced ) {
        return $this->setParam( 'max_states_traced', $maxStatesTraced );
    }

    /**
     * @param int $maxInspect maximum number of source field to run the regex
     * against before giving up and just declaring all remaining fields not
     * matching by fiat. Defaults to MAX_INT. Set this to 10000 or something
     * nice and low to prevent regular expressions that cannot be sped up from
     * taking up too many resources.
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setMaxInspect( $maxInspect ) {
        return $this->setParam( 'max_inspect', $maxInspect );
    }

    /**
     * @param bool $caseSensitive is the regex case insensitive?  Defaults to
     * case insensitive if not set.
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setCaseSensitive( $caseSensitive ) {
        return $this->setParam( 'case_sensitive', $caseSensitive );
    }

    /**
     * @param bool $locale locale used for case conversions.  Its imporant that
     * this matches the locale used for lowercasing in the ngram index.
     * @return \CirrusSearch\Extra\Filter\SourceRegex this for chaining
     */
    public function setLocale( $locale ) {
        return $this->setParam( 'locale', $locale );
    }
}
