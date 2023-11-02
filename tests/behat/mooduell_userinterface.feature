@mod @mod_mooduell @core_completion @core_question @javascript @_file_upload
Feature: Check user interface
  In order to ...
  As a user
  I need to be able to ...

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
      | teacher  | Teacher   | 3        |
      | teacher2 | Teacher2  | 4        |
      | manager  | Manager   | 5        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | teacher  | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name          | intro         | course | idnumber  | usefullnames |
      | mooduell   | Mooduell Test | Mooduell Test | C1     | mooduell1 | 1            |
    And the following "question categories" exist:
      | contextlevel | reference | name             |
      | Course       | C1        | Test questions   |
      | Course       | C1        | Import questions |
    And the following "questions" exist:
      | questioncategory | qtype | name                              | questiontext         | answer 1 | answer 2 |
      | Test questions   | multichoice | Test question to be deleted | 1+1=                 | 2 | 7 |
      | Test questions   | multichoice | Test question to be deleted | 2+2=                 | 4 | 7 |
      | Test questions   | multichoice | Test question to be deleted | 3+3=                 | 6 | 7 |
      | Test questions   | multichoice | Test question to be deleted | 4+4=                 | 8 | 7 |
      | Test questions   | multichoice | Test question to be deleted | 5+5=                 | 10 | 7 |
      | Test questions   | multichoice | Test question to be deleted | 6+6=                 | 12 | 7 |
      | Test questions   | multichoice | Test question to be deleted | 7+7=                 | 14 | 7 |
      | Test questions   | multichoice | Test question to be deleted | 8+8=                 | 16 | 7 |
      | Test questions   | multichoice | Test question to be deleted | 9+9=                 | 18 | 7 |
    And the following "mod_mooduell > questions" exist:
      | course | questioncategory | filepath                                      | filename          |
      | C1     | Import questions | mod/mooduell/tests/fixtures/testquestions.xml | testquestions.xml |

  @javascript
  Scenario: Mooduell: User check tabs
    Given I am on the "mooduell1" Activity page logged in as user1
    When I follow "Statistics"
    Then I should see "Opponents"
    And I should see "Open games"
    And I should see "Finished games"
    And I should see "Games won"
    And I should see "Correct answers"
    And I should see "Correctly answered"
    And I follow "Open games"
    And I should see "No records found"
    And I follow "Finished games"
    And I should see "No records found"
    And I follow "Highscores"
    And I should see "No records found"

  @javascript
  Scenario: Mooduell: User show QR Code
    Given I am on the "mooduell1" Activity page logged in as user1
    When I press "Show QR Code"
    And I wait until the page is ready
    Then I should see "QR Code Login" in the ".modal-header" "css_element"

  @javascript
  Scenario: Mooduel: Players listed in open games when admin starts the games
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Settings"
    And I press "Save and return to course"
    And I start games in "Mooduell Test" against "user1"
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Open games"
    Then I should see "10 of 10 records found"
    And I should see "Admin User"
    And I should see "Username 1"

  @javascript
  Scenario: Mooduel: Players listed in open games when user starts the games
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Settings"
    And I press "Save and return to course"
    When I log in as "user2"
    ## Above does nothing - no actual login happens in behat - $USER still contain "admin"
    And I am on "Course 1" course homepage
    And I start games in "Mooduell Test" against "user2"
    And I follow "Mooduell Test"
    And I follow "Open games"
    Then I should see "Admin User"
    And I should see "Username 2"

  @javascript
  Scenario: Mooduel: Finish games and list them in finished games
    Given the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | teacher  | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | admin    | C1     | editingteacher |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Settings"
    And I press "Save and return to course"
    ## Start 10 games as admin against "user1" and answer 3 questions immendiately
    When I start games in "Mooduell Test" against "user1"
    And I follow "Mooduell Test"
    ## Play all questions in butches "3+3"
    And I play all open questions as "user1"
    And I play all open questions as "admin"
    And I play all open questions as "user1"
    And I follow "Open games"
    And I should see "No records found"
    And I follow "Finished games"
    Then I should see "10 of 10 records found"
    And I should see "Admin User"
    And I should see "Username 1"
