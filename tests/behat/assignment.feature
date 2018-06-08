@plugin @plagiarism @plagiarism_turnitin @plagiarism_turnitin_assignment
Feature: Plagiarism plugin works with a Moodle Assignment
  In order to allow students to send assignment submissions to Turnitin
  As a user
  I need to create an assignment with the plugin enabled and the assignment to launch successfully.

  Background: Set up the plugin
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Turnitin" node in "Site administration > Plugins > Plagiarism"
    And I set the following fields to these values:
      | Enable Turnitin            | 1 |
      | Enable Turnitin for assign | 1 |
    And I navigate to "Turnitin Assignment 2" node in "Site administration > Plugins > Activity modules"
    And I follow "Edit password"
    And I set the following fields to these values:
      | Turnitin Shared Key    | testing1                     |
      | Turnitin Account ID    | xxxxx                        |
      | Turnitin API URL       | https://sandbox.turnitin.com |
      | Enable Diagnostic Mode | Standard                     |
    And I press "Save changes"
    Then the following should exist in the "plugins-control-panel" table:
      | Plugin name         |
      | plagiarism_turnitin |
    # Create Assignment.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | turnitinenabled | 1 |
      | accessoptions[accessstudents] | 1 |
    And I follow "Test assignment name"
    Then I should see "Grading summary"

  @javascript
  Scenario: Create an assignment
    Given I log out
    # Student submits.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"