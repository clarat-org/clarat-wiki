@clean @phantomjs @setup_main
Feature: Searches that contain fuzzy matches
  Background:
    Given I am at a random page

  Scenario: Searching for <text>~ activates fuzzy search
    When I search for ffnonesensewor~
    Then Two Words is the first search result
      And there is no link to create a new page from the search result

  Scenario Outline: Searching for <text>~<number between 0 and 1> activates fuzzy search
    When I search for ffnonesensewor~<number>
    Then Two Words is the first search result
      And there is no link to create a new page from the search result
  Examples:
    | number |
    | .8     |
    | 0.8    |
    | 1      |

  Scenario: Searching for <text>~0 activates fuzzy search but with 0 fuzziness (finding a result if the term is corret)
    When I search for ffnonesenseword~0
    Then Two Words is the first search result

  Scenario: Searching for <text>~0 activates fuzzy search but with 0 fuzziness (finding nothing if fuzzy search is required)
    When I search for ffnonesensewor~0
    Then there are no search results

  Scenario: Fuzzy search doesn't find terms that don't match the first two characters for performance reasons
    When I search for fgnonesenseword~
    Then there are no search results
