@plugin @plagiarism @plagiarism_turnitin @plagairism_turnitin_smoke @plagiarism_turnitin_installed
Feature:  Installation succeeds
  In order to use this plugin
  As a user
  I need the installation to work and plagiarism plugins to be enabled

  Background: Set up the plugin
    When I log in as "admin"
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

  @javascript
  Scenario: Test the plugin connectivity
    Given I navigate to "Turnitin Assignment 2" node in "Site administration > Plugins > Activity modules"
    And I configure Turnitin URL
    And I click on "#test_link" "css_element"
    And I wait until "#test_result" "css_element" exists
    Then I should see "Moodle has successfully connected to Turnitin."
