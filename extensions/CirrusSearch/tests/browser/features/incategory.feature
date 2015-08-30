@clean @filters @incategory @phantomjs
Feature: Searches with the incategory filter
  Background:
    Given I am at a random page

  Scenario: incategory: only includes pages with the category
    When I search for incategory:weaponry
    Then Catapult is in the search results
      And Amazing Catapult is in the search results
      But Two Words is not in the search results
      And there is no link to create a new page from the search result

  Scenario: incategory: can be combined with other text
    When I search for incategory:weaponry amazing
    Then Amazing Catapult is the first search result
      And there is no link to create a new page from the search result

  Scenario: -incategory: excludes pages with the category
    When I search for -incategory:weaponry incategory:twowords
    Then Two Words is the first search result
      And there is no link to create a new page from the search result

  Scenario: incategory: works on categories from templates
    When I search for incategory:templatetagged incategory:twowords
    Then Two Words is the first search result

  Scenario: incategory works with multi word categories
    When I search for incategory:"Categorywith Twowords"
    Then Two Words is the first search result

  Scenario: incategory can find categories containing quotes if the quote is escaped
    When I search for incategory:"Categorywith \" Quote"
    Then Two Words is the first search result

  Scenario: incategory can be repeated
    When I search for incategory:"Categorywith \" Quote" incategory:"Categorywith Twowords"
    Then Two Words is the first search result

  Scenario: incategory works with can find two word categories with spaces
    When I search for incategory:Categorywith_Twowords
    Then Two Words is the first search result

  Scenario: incategory: when passed a quoted category that doesn't exist finds nothing even though there is a category that matches one of the words
    When I search for incategory:"Dontfindme Weaponry"
    Then there are no search results

  Scenario: incategory when passed a single word category doesn't find a two word category that contains that word
    When I search for incategory:ASpace
    Then there are no search results

  Scenario: incategory: finds a multiword category when it is surrounded by quotes
    When I search for incategory:"CategoryWith ASpace"
    Then IHaveATwoWordCategory is the first search result

  Scenario: incategory: can handle a space after the :
    When I search for incategory: weaponry
    Then Catapult is in the search results
      And Amazing Catapult is in the search results
      But Two Words is not in the search results
      And there is no link to create a new page from the search result
