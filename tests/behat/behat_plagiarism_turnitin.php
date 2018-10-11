<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions related to plagiarism_turnitin.
 *
 * @copyright 2018 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException;
use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

class behat_plagiarism_turnitin extends behat_base {

    /**
     * @Given I switch to iframe with locator :locator
     * @param String $locator
     * @throws \Behat\Mink\Exception\DriverException
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public function i_switch_to_iframe_with_locator($locator) {
        $iframe = $this->getSession()->getPage()->find("css", $locator);
        $iframename = $iframe->getAttribute("name");
        if ($iframename == "") {
            echo "\n\niFrame has no name. Let's name it.\n\n";
            $javascript = "(function(){
            var iframes = document.getElementsByTagName('iframe');
                for (var i = 0; i < iframes.length; i++) {
                    if (!iframes[i].name) {
                        iframes[i].name = 'iframe_number_' + (i + 1) ;
                    }
                }
            })()";
            $this->getSession()->executeScript($javascript);
            $iframe = $this->getSession()->getPage()->find("css", $locator);
            $iframename = $iframe->getAttribute("name");
            echo "\n\niFrame has new name:  " . $iframename . "\n\n";
        } else {
            echo "\n\niFrame already has a name: " . $iframename . "\n\n";
        }

        $this->getSession()->getDriver()->switchToIFrame($iframename);
    }

    /**
     * @Given I configure Turnitin URL
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function i_configure_turnitin_url() {
        $apiurl = getenv('TII_APIBASEURL');
        $javascript = "
            var option = document.createElement('option');
            option.setAttribute('value', '${apiurl}');
            var apiurl = document.createTextNode('${apiurl}');
            var select = document.querySelector('#id_plagiarism_turnitin_apiurl');
            option.appendChild(apiurl);
            select.appendChild(option);
        ";
        $this->getSession()->executeScript($javascript);
        $this->getSession()->getPage()->find("css", "#id_plagiarism_turnitin_apiurl")->selectOption($apiurl);
    }

    /**
     * @Given I configure Turnitin credentials
     */
    public function i_configure_turnitin_credentials() {
        $account = getenv('TII_ACCOUNT');
        $secret = getenv('TII_SECRET');

        $this->getSession()->getPage()->find("css", "#id_plagiarism_turnitin_accountid")->setValue($account);

        $this->getSession()->getPage()->find('css', '[title="Edit password"]')->click();
        $this->getSession()->getPage()->find("css", "#id_plagiarism_turnitin_secretkey")->setValue($secret);
    }

    /**
     * @Given I create a unique user with username :username
     * @param $username
     */
    public function i_create_a_unique_user($username) {
        $generator = testing_util::get_data_generator();
        $generator->create_user(array(
            'email' => uniqid($username, true) . '@example.com',
            'username' => $username,
            'password' => $username,
            'firstname' => $username,
            'lastname' => $username
        ));
    }

    /**
     * Makes sure user can see the exact number of text instances on the page.
     *
     * @Then /^I should see "(?P<textcount_number>\d+)" instances of "(?P<text_string>(?:[^"]|\\")*)" on the page$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param int $textcount
     * @param string $text
     */
    public function i_should_see_textcount_instances_of_text_on_the_page($textcount, $text) {
        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        try {
            $elements = $this->find_all('xpath', $xpath);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $text . '" text was not found in the page', $this->getSession());
        }

        if (count($elements) != $textcount) {
            throw new ExpectationException('Found '.count($elements).' instances of the text '. $text.'. Expected '.$textcount,
                $this->getSession());
        }
    }

    /**
     * Poll 12 times over 2 minutes for an originality report. This should be enough time for the vast majority of cases.
     *
     * @Given /^I obtain an originality report for "(?P<student>(?:[^"]|\\")*)" on "(?P<modtype>(?:[^"]|\\")*)" "(?P<modname>(?:[^"]|\\")*)" on course "(?P<coursename>(?:[^"]|\\")*)"$/
     * @param string $student
     * @param string $modtype
     * @param string $modname
     * @param string $coursename
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function i_obtain_an_originality_report_for_student_on_modtype_assignmentname_on_course_coursename($student, $modtype, $modname, $coursename)
    {
        $reportFound = false;
        $count = 1;
        while (!$reportFound) {
            $this->execute('behat_general::i_run_the_scheduled_task', "\plagiarism_turnitin\\task\update_reports");
            $this->execute('behat_general::i_wait_seconds', 1);
            $this->execute('behat_navigation::i_am_on_course_homepage', $coursename);
            $this->execute('behat_general::click_link', $modname);

            switch($modtype) {
                case "assignment":
                    $this->execute('behat_navigation::i_navigate_to_in_current_page_administration', "View all submissions");
                    break;
                case "forum":
                    $this->execute('behat_general::click_link', "Forum post 1");
                    break;
                case "workshop":
                    $this->execute('behat_general::click_link', "Submission1");
                    break;
            }

            try {
                switch($modtype) {
                    case "assignment":
                        $this->execute('behat_general::row_column_of_table_should_contain', array($student, "File submissions", "generaltable", "%"));
                        break;
                    case "forum":
                    case "workshop":
                        $this->execute('behat_general::assert_element_contains_text', array("%", "div.origreport_score", "css_element"));
                        break;
                }
                break;
            } catch (Exception $e) {
                if ($count >= 12) {
                    throw new ElementNotFoundException($this->getSession());
                }
                $count++;
            }
        }
    }

    /**
     * @Given I accept the Turnitin EULA if necessary
     */
    public function i_accept_the_turnitin_eula_if_necessary() {
        try {
            $this->getSession()->getPage()->find("css", ".pp_turnitin_eula_link");

            $this->execute('behat_general::i_click_on', array(".pp_turnitin_eula_link", "css_element"));
            $this->execute('behat_general::wait_until_exists', array(".cboxIframe", "css_element"));
            $this->i_switch_to_iframe_with_locator(".cboxIframe");
            $this->execute('behat_general::i_click_on', array(".agree-button", "css_element"));
        } catch (Exception $e) {
            // EULA not found - so skip it.
        }
    }

    /**
     * @Given I accept the Turnitin EULA from the EV if necessary
     */
    public function i_accept_the_turnitin_eula_from_the_ev_if_necessary() {
        try {
            $this->getSession()->getPage()->find("css", ".agree-button");

            $this->execute('behat_general::i_click_on', array(".agree-button", "css_element"));
        } catch (Exception $e) {
            // EULA not found - so skip it.
        }
    }
}
