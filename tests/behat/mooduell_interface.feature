@mod @mod_mooduell @core_completion @core_question @javascript @_file_upload
Feature: See user stats
  In order to allow see the progress
  As a teacher
  I need to be able to see the stats

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | profile_field_mooduell_alias |
      | user1    | Username  | 1        | Duell user1                  |
      | user2    | Username  | 2        | Duell user2                  |
      | teacher  | Teacher   | 3        | Duell teacher                |
      | manager  | Manager   | 4        | Duell manager                |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | teacher  | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name          | intro         | course | idnumber  | usefullnames |
      | mooduell | Mooduell Test | Mooduell Test | C1     | mooduell1 | 1            |
    And the following "question categories" exist:
      | contextlevel | reference | name             |
      | Course       | C1        | Test questions   |
      | Course       | C1        | Import questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name                        | questiontext         | answer 1 | answer 2 |
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
  Scenario: Load questions and select category in mooduell
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Settings"
    And I press "Save and return to course"
    And I start games in "Mooduell Test" against "user1"
    And I start games in "Mooduell Test" against "user2"
    And I follow "Mooduell Test"
    And I wait until the page is ready
    And I follow "Statistics"
    And I follow "Open games"
    And I should see "Username 1"
    And I should see "Username 2"
    And I click on "td.columnclass.action a" "css_element"
    And I should see "No image"
    And I click on "div.modal-footer button" "css_element"
    And I follow "Finished games"
    And I follow "Highscores"

  @javascript
  Scenario: Opening the activity will show the tabs Statistics
    Given I log in as "teacher"
    When I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Settings"
    And I set the field "id_categoriesgroup0_category" to "Test questions (9)"
    And I press "Save and return to course"
    And I start games in "Mooduell Test" against "user1"
    And I start games in "Mooduell Test" against "user2"
    And I follow "Mooduell Test"
    And I follow "Open games"
    And I follow "Statistics"
    Then I should see "Active users"

  @javascript
  Scenario: Edit question and check version
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Settings"
    And I press "Save and return to course"
    And I start games in "Mooduell Test" against "user1"
    And I start games in "Mooduell Test" against "user2"
    When I follow "Mooduell Test"
    And I follow "Questions"
    And I should see "No image"
    And I should see "OK"
    And I click on "td.columnclass.id a" "css_element"
    And I wait until the page is ready
    And I press "Cancel"
    And I click on "td.columnclass.id a" "css_element"
    And I wait until the page is ready
    Then I should see "Version 1"
    When I press "Save changes and continue editing"
    And I wait until the page is ready
    ##And I wait "6" seconds
    Then I should see "Version 2"
    When I press "submitbutton"
    And I click on "td.columnclass.id a" "css_element"
    And I wait until the page is ready
    Then I should see "Version 1"

  @javascript
  Scenario: Use full names
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Settings"
    And I set the field "usefullnames" to "0"
    And I press "Save and return to course"
    And I start games in "Mooduell Test" against "user1"
    When I follow "Mooduell Test"
    And I follow "Open games"
    Then I should see "Duell user1"

  @javascript
  Scenario: Button check easiest and hardest question
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I follow "Settings"
    And I press "Save and return to course"
    And I start games in "Mooduell Test" against "user1"
    And I follow "Mooduell Test"
    Then I click on "div.bg-info a" "css_element"
    Then I click on "div.bg-danger a" "css_element"
