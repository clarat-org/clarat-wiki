@clean @non_existent @phantomjs @update
Feature: Search backend updates that reference nonexistent pages
  Background:
    Given I am at a random page

  Scenario: Pages that link to nonexistent pages still get their search index updated
    Given a page named IDontExist doesn't exist
      And a page named ILinkToNonExistentPages%{epoch} exists with contents [[IDontExist]]
    When I am at a random page
    Then within 10 seconds searching for ILinkToNonExistentPages%{epoch} yields ILinkToNonExistentPages%{epoch} as the first result

  Scenario: Pages that redirect to nonexistent pages don't throw errors
    Given a page named IDontExist doesn't exist
    When a page named IRedirectToNonExistentPages%{epoch} exists with contents #REDIRECT [[IDontExist]]

  Scenario: Linking to a nonexistent page doesn't add it to the search index with an [INVALID] word count
    Given a page named ILinkToNonExistentPages%{epoch} exists with contents [[IDontExistLink%{epoch}]]
    When I am at a random page
    Then within 20 seconds searching for IDontExistLink%{epoch} yields ILinkToNonExistentPages%{epoch} as the first result
      And there are no search results with [INVALID] words in the data
    When a page named IDontExistLink%{epoch} exists
    Then within 10 seconds searching for IDontExistLink%{epoch} yields IDontExistLink%{epoch} as the first result
      And there are no search results with [INVALID] words in the data

  Scenario: Redirecting to a non-existing page doesn't add it to the search index with an [INVALID] word count
    Given a page named IRedirectToNonExistentPages%{epoch} exists with contents #REDIRECT [[IDontExistRdir%{epoch}]]
      And I am at a random page
    When wait 5 seconds for the index to get the page
      And I search for IDontExistRdir%{epoch}
      And there are no search results with [INVALID] words in the data
      And a page named IDontExistRdir%{epoch} exists
    Then within 10 seconds searching for IDontExistRdir%{epoch} yields IDontExistRdir%{epoch} as the first result
      And there are no search results with [INVALID] words in the data

  Scenario: Linking to a page that redirects to a non-existing page doesn't add it to the search index with an [INVALID] word count
    Given a page named IRedirectToNonExistentPagesLinked%{epoch} exists with contents #REDIRECT [[IDontExistRdirLinked%{epoch}]]
      And a page named ILinkIRedirectToNonExistentPages%{epoch} exists with contents [[IRedirectToNonExistentPagesLinked%{epoch}]]
      And I am at a random page
    When wait 5 seconds for the index to get the page
      And I search for IDontExistRdir%{epoch}
      And there are no search results with [INVALID] words in the data
    When a page named IDontExistRdirLinked%{epoch} exists
    Then within 10 seconds searching for IDontExistRdirLinked%{epoch} yields IDontExistRdirLinked%{epoch} as the first result
      And there are no search results with [INVALID] words in the data
