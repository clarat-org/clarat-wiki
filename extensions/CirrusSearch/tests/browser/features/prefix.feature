@clean @phantomjs @prefix_filter
Feature: Searches with a prefix filter
  Background:
    Given I am at a random page

  Scenario: You can add the prefix to the url
    When I am at the search results page with the search prefix and the prefix prefix
    Then Prefix Test is the first search result
      But Foo Prefix Test is not in the search results

  Scenario: The prefix: filter filters results to those with titles prefixed by value
    When I search for prefix prefix:prefix
    Then Prefix Test is the first search result
      But Foo Prefix Test is not in the search results
      And there is no link to create a new page from the search result

  Scenario: The prefix: filter interprets spaces literally
    When I search for prefix prefix:prefix tes
    Then Prefix Test is the first search result

  Scenario: The prefix: filter interprets underscores as spaces
    When I search for prefix prefix:prefix_tes
    Then Prefix Test is the first search result

  Scenario: It is ok to start the query with the prefix filter
    When I search for prefix:prefix tes
    Then Prefix Test is the first search result

  Scenario: It is ok to specify an empty prefix filter
    When I search for prefix test prefix:
    Then Prefix Test is the first search result

  Scenario: The prefix: filter can be used to apply a namespace and a title prefix
    When I search for prefix:talk:prefix tes
    Then Talk:Prefix Test is the first search result
    But Prefix Test is not in the search results

  Scenario: The prefix: filter can be used to apply a namespace without a title prefix
    When I search for prefix test prefix:talk:
    Then Talk:Prefix Test is the first search result
      But Prefix Test is not in the search results

  Scenario: The prefix: filter can apply a namespace containing a space
    When I search for prefix test prefix:user talk:
    Then User talk:Prefix Test is the first search result
      But Prefix Test is not in the search results

  Scenario: The prefix: filter can apply a namespace containing an underscore
    When I search for prefix test prefix:user_talk:
    Then User talk:Prefix Test is the first search result
      But Prefix Test is not in the search results

  Scenario: The prefix: filter can be used to filter to subpages
    When I search for prefix test aaaa prefix:Prefix Test/
    Then Prefix Test/AAAA is the first search result
      But Prefix Test AAAA is not in the search results

  Scenario: The prefix: filter can be used to filter to subpages starting with some title
    When I search for prefix test aaaa prefix:Prefix Test/aa
    Then Prefix Test/AAAA is the first search result
      But Prefix Test AAAA is not in the search results

  Scenario: The prefix: filter can be quoted
    When I search for prefix test prefix:"user_talk:"
    Then User talk:Prefix Test is the first search result
      But Prefix Test is not in the search results

  Scenario: The prefix: filter doesn't find redirects
    When I search for prefix:Prefix Test Redirec
    Then there are no search results