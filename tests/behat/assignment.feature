@plugin @plagiarism @plagiarism_turnitin @plagiarism_turnitin_sanity @plagairism_turnitin_smoke @plagiarism_turnitin_assignment
Feature: Plagiarism plugin works with a Moodle Assignment
  In order to allow students to send assignment submissions to Turnitin
  As a user
  I need to create an assignment with the plugin enabled and the assignment to launch successfully.

  Background: Set up the plugin
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And I create a unique user with username "student1"
    And I create a unique user with username "instructor1"
    And the following "course enrolments" exist:
      | user        | course | role    |
      | student1    | C1     | student |
      | instructor1 | C1     | teacher |
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Turnitin" node in "Site administration > Plugins > Plagiarism"
    And I set the following fields to these values:
      | Enable Turnitin            | 1 |
      | Enable Turnitin for assign | 1 |
    And I press "Save changes"
    And I navigate to "Turnitin Assignment 2" node in "Site administration > Plugins > Activity modules"
    And I configure Turnitin URL
    And I configure Turnitin credentials
    And I set the following fields to these values:
      | Enable Diagnostic Mode | Standard |
    And I press "Save changes"
    Then the following should exist in the "plugins-control-panel" table:
      | Plugin name         |
      | plagiarism_turnitin |
    # Create Assignment.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name                   | Test assignment name |
      | use_turnitin                      | 1                    |
      | plagiarism_compare_student_papers | 1                    |
    Then I should see "Test assignment name"

  @javascript
  Scenario: Student accepts eula, submits and instructor opens the viewer
    Given I log out
    # Student accepts eula.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I click on ".pp_turnitin_eula_link" "css_element"
    And I wait until ".cboxIframe" "css_element" exists
    And I switch to iframe with locator ".cboxIframe"
    And I wait until the page is ready
    And I click on ".agree-button" "css_element"
    And I wait until the page is ready
    Then I should see "Test assignment name"
    # Student submits.
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I upload "plagiarism/turnitin/tests/fixtures/testfile.txt" file to "File submissions" filemanager
    And I press "Save changes"
    Then I should see "Submitted for grading"
    And I should see "Queued"
    # Trigger cron as admin for submission
    And I log out
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitin\task\send_submissions"
    # Instructor opens viewer
    And I log out
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I should see "View all submissions"
    When I navigate to "View all submissions" in current page administration
    Then I should see "Turnitin ID:"
    # Trigger cron as admin for report
    And I log out
    And I log in as "admin"
    And I run the scheduled task "\plagiarism_turnitin\task\update_reports"
    # Instructor opens viewer
    And I log out
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I should see "View all submissions"
    When I navigate to "View all submissions" in current page administration
    And I wait until "[alt='GradeMark']" "css_element" exists
    And I click on "[alt='GradeMark']" "css_element"
    And I switch to "turnitin_viewer" window
    And I wait until the page is ready
    And I click on ".agree-button" "css_element"
    And I wait until the page is ready
    Then I should see "testfile.txt"
