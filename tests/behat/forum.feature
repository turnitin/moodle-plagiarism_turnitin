@plugin @plagiarism @plagiarism_turnitin @plagiarism_turnitin_smoke @plagiarism_turnitin_forum
Feature: Plagiarism plugin works with a Moodle forum
  In order to allow students to send forum posts to Turnitin
  As a user
  I need to create a forum and discussion with the plugin enabled.

  Background: Set up the users, course, forum and discussion with plugin enabled
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And I create a unique user with username "student1"
    And I create a unique user with username "instructor1"
    And the following "course enrolments" exist:
      | user        | course | role    |
      | student1    | C1     | student |
      | instructor1 | C1     | editingteacher |
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Turnitin" node in "Site administration > Plugins > Plagiarism"
    And I set the following fields to these values:
      | Enable Turnitin            | 1 |
      | Enable Turnitin for Forum  | 1 |
    And I configure Turnitin URL
    And I configure Turnitin credentials
    And I set the following fields to these values:
      | Enable Diagnostic Mode | Yes |
    And I press "Save changes"
    Then the following should exist in the "plugins-control-panel" table:
      | Plugin name         |
      | plagiarism_turnitin |
    # Create Forum.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name                        | Test forum                     |
      | Forum type                        | Standard forum for general use |
      | Description                       | Test forum                     |
      | groupmode                         | 0                              |
      | use_turnitin                      | 1                              |
      | plagiarism_compare_student_papers | 1                              |
      | plagiarism_show_student_report    | 1                              |
    And I follow "Test forum"
    And I click on "Add a new discussion topic" "button"
    And I set the following fields to these values:
      | Subject | Forum post 1                                                                                                                |
      | Message | This is the body of the forum post that will be submitted to Turnitin. It will be sent to Turnitin for Originality Checking |
    And I press "Post to forum"

  @javascript
  Scenario: Add a post to a discussion with a file attached and retrieve the originality score
    Given I log out
    # Student creates a forum discussion and replies to original post.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I click on "Add a new discussion topic" "button"
    And I click on ".pp_turnitin_eula_link" "css_element"
    And I wait until ".cboxIframe" "css_element" exists
    And I switch to iframe with locator ".cboxIframe"
    And I wait until the page is ready
    And I click on ".agree-button" "css_element"
    And I wait until the page is ready
    Then I should see "Test forum"
    And I set the following fields to these values:
      | Subject | Forum post 2                                                                                                                |
      | Message | This is the body of the forum post that will be submitted to Turnitin. It will be sent to Turnitin for Originality Checking |
    And I press "Post to forum"
    And I reply "Forum post 1" post from "Test forum" forum with:
      | Subject    | Reply with attachment                                                                                                       |
      | Message    | This is the body of the forum reply that will be submitted to Turnitin. It will be sent to Turnitin for Originality Checking |
      | Attachment | plagiarism/turnitin/tests/fixtures/testfile.txt                                                                                |
    Then I should see "Reply with attachment"
    And I should see "testfile.txt"
    And I should see "Queued" in the "div.turnitin_status" "css_element"
    And I log out
    # Trigger cron as admin for forum and check results.
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitin\task\send_submissions"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "Forum post 1"
    Then I should see "Turnitin ID:" in the "div.turnitin_status" "css_element"
    And I log out
    # Student can see post has been sent to Turnitin.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "Forum post 1"
    Then I should see "Turnitin ID:" in the "div.turnitin_status" "css_element"
    And I log out
    # Trigger cron as admin for report
    And I log in as "admin"
    And I wait "20" seconds
    And I run the scheduled task "\plagiarism_turnitin\task\update_reports"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "Forum post 1"
    Then I should see "%" in the "div.origreport_score" "css_element"
    And I log out
    # Login as student and a score should be visible.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "Forum post 1"
    Then I should see "%" in the "div.origreport_score" "css_element"
    # Instructor opens viewer
    And I log out
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "Forum post 1"
    And I wait until "div.pp_origreport_open" "css_element" exists
    And I click on "div.pp_origreport_open" "css_element"
    And I switch to "turnitin_viewer" window
    And I wait until the page is ready
    And I click on ".agree-button" "css_element"
    And I wait until the page is ready
    Then I should see "forumpost_"