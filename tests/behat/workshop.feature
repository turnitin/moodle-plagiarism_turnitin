@plugin @plagiarism @plagiarism_turnitin @plagiarism_turnitin_smoke @plagiarism_turnitin_workshop
Feature: Plagiarism plugin works with a Moodle Workshop
  In order to allow students to send workshop submissions to Turnitin
  As a user
  I need to create a workshop with the plugin enabled.

  Background: Set up the users, course and workshop with plugin enabled
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following users will be created if they do not already exist:
      | username    | firstname   | lastname    | email                                   |
      | instructor1 | instructor1 | instructor1 | instructor1_$account_tiibehattesting@example.com |
      | student1    | student1    | student1    | student1_$account_tiibehattesting@example.com    |
    And the following "course enrolments" exist:
      | user        | course | role    |
      | student1    | C1     | student |
      | instructor1 | C1     | editingteacher |
    When I log in as "admin"
    And I navigate to "Advanced features" in site administration
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Plugins > Plagiarism > Turnitin" in site administration
    And I set the following fields to these values:
      | Enable Turnitin              | 1 |
      | Enable Turnitin for Workshop | 1 |
    And I configure Turnitin URL
    And I configure Turnitin credentials
    And I set the following fields to these values:
      | Enable Diagnostic Mode | Yes |
    And I press "Save changes"
    Then the following should exist in the "plugins-control-panel" table:
      | Plugin name         |
      | plagiarism_turnitin |
    # Create Workshop.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Workshop" to section "1" and I fill the form with:
      | Workshop name                     | Test workshop |
      | use_turnitin                      | 1             |
      | plagiarism_compare_student_papers | 1             |
      | plagiarism_show_student_report    | 1             |
    And I am on "Course 1" course homepage
    And I edit assessment form in workshop "Test workshop" as:"
      | id_description__idx_0_editor | Aspect1 |
      | id_description__idx_1_editor | Aspect2 |
      | id_description__idx_2_editor |         |

  @javascript
  Scenario: A submission can be queued and sent to Turnitin for a workshop
    Given I change phase in workshop "Test workshop" to "Submission phase"
    And I log out
    # Student makes submission to workshop.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I press "Start preparing your submission"
    And I accept the Turnitin EULA if necessary
    And I wait until the page is ready
    Then I should see "Test workshop"
    And I set the following fields to these values:
      | Title              | Submission1                                                                                                                                            |
      | Submission content | This is a workshop submission that will be submitted to Turnitin. It will be sent to Turnitin for Originality Checking and matched against any sources |
      | Attachment         | plagiarism/turnitin/tests/fixtures/testfile.txt                                                                                                        |
    And I press "Save changes"
    Then I should see "My submission"
    And I should see "Queued" in the "div.turnitin_status" "css_element"
    And I log out
    # Trigger cron as admin for workshop and check results.
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitin\task\send_submissions"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I follow "Submission1"
    Then I should see "Turnitin ID:" in the "div.turnitin_status" "css_element"
    And I log out
    # Student can see submission has been sent to Turnitin.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I follow "Submission1"
    Then I should see "Turnitin ID:" in the "div.turnitin_status" "css_element"
    # Admin runs scheduled task to request an originality report.
    And I log out
    And I log in as "admin"
    And I obtain an originality report for "student1 student1" on "workshop" "Test workshop" on course "Course 1"
    # Login as student and a score should be visible.
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I follow "Submission1"
    Then I should see "%" in the "div.origreport_score" "css_element"
    # Instructor opens viewer
    And I log out
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I follow "Submission1"
    And I wait until "div.pp_origreport_open" "css_element" exists
    And I click on "div.pp_origreport_open" "css_element"
    And I switch to "turnitin_viewer" window
    And I wait until the page is ready
    And I accept the Turnitin EULA from the EV if necessary
    And I wait until the page is ready
    Then I should see "onlinetext_"