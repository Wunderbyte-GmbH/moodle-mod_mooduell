@mod @mod_mooduell @javascript @_switch_iframe
Feature: Render MooDuell launch preview via shortcode
  In order to embed the MooDuell app launch preview in arbitrary content
  As a teacher
  I need the [mooduell] shortcode to render correctly when the configured security token is used

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher  | Teacher   | 1        |
      | student1 | Student   | 1        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
    And the "shortcodes" filter is "on"
    And the following config values are set as admin:
      | shortcodetoken | TESTTOKN | mooduell |
      | appstoreurl    | https://apps.apple.com/at/app/mooduell/id1598911543 | mooduell |
    And the following "activities" exist:
      | activity | name            | intro                              | introformat | course | idnumber   |
      | label    | Mooduell Embed  | [mooduell securitytoken=TESTTOKN] | 1           | C1     | shortcode1 |

  Scenario: Shortcode renders webapp iframe with valid security token
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then ".mooduell-launch-preview" "css_element" should exist
    And ".mooduell-phone-iframe" "css_element" should exist
    When I switch to "mooduell-phone-iframe" class iframe
    Then "ion-app" "css_element" should exist
    And I switch to the main frame
