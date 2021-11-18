@mod @mod_mooduell @core_completion @core_question @javascript
Feature: See user stats
  In order to allow see the progress
  As a teacher
  I need to be able to see the stats

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
      | teacher  | Teacher   | 3        |
      | manager  | Manager   | 4        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | teacher  | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name                | intro              | course | idnumber    |
      | mooduell   | Mooduell Test       | Mooduell Test      | C1     | mooduell1   |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name                              | questiontext         |
      | Test questions   | multichoice | Test question to be deleted | 1+1=                 |
      | Test questions   | multichoice | Test question to be deleted | 2+2=                 |
      | Test questions   | multichoice | Test question to be deleted | 3+3=                 |
      | Test questions   | multichoice | Test question to be deleted | 4+4=                 |
      | Test questions   | multichoice | Test question to be deleted | 5+5=                 |
      | Test questions   | multichoice | Test question to be deleted | 6+6=                 |
      | Test questions   | multichoice | Test question to be deleted | 7+7=                 |
      | Test questions   | multichoice | Test question to be deleted | 8+8=                 |
      | Test questions   | multichoice | Test question to be deleted | 9+9=                 |

  @javascript
  Scenario: Opening the activity will show the tabs "Statistics"
    Given I log in as "teacher"
    When I am on "Course 1" course homepage
    And I follow "Mooduell Test"
    And I navigate to "Edit settings" in current page administration
    And I set the field "id_categoriesgroup0_category" to "-> Test questions (9 Questions)"
    And I press "Save and return to course"
    And I start games in "Mooduell Test" against "user1"
    And I start games in "Mooduell Test" against "user2"
    And I follow "Mooduell Test"
    And I follow "Finished games"
    And I follow "Questions"
    And I follow "Open games"
    And I follow "Finished games"
    And I follow "Questions"
    And I follow "Open games"
    And I follow "Statistics"
    Then I should see "Download table data as"
    