@plugin @plagiarism @plagiarism_turnitin @plagiarism_turnitin_assignment
Feature: Plagiarism plugin works with a Moodle Assignment
  In order to allow students to send assignment submissions to Turnitin
  As a user
  I need to create an assignment with the plugin enabled and the assignment to launch successfully.

  Background: Set up the plugin
    # Enable and configure plugin.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Turnitin Assignment 2" node in "Site administration > Plugins > Activity modules"
    And I follow "Edit password"
    And I set the following fields to these values:
      | Turnitin Shared Key    | testing1                     |
      | Turnitin Account ID    | 66849                        |
      | Turnitin API URL       | https://sandbox.turnitin.com |
      | Enable Diagnostic Mode | Standard                     |
    And I press "Save changes"
    Then the following should exist in the "plugins-control-panel" table:
      |Plugin name|
      |plagiarism_turnitin|

