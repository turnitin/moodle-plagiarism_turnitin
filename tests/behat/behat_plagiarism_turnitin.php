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
            var select = document.querySelector('#admin-apiurl select');
            option.appendChild(apiurl);
            select.appendChild(option);
        ";
        $this->getSession()->executeScript($javascript);
        $this->getSession()->getPage()->find("css", "#admin-apiurl select")->selectOption($apiurl);
    }

    /**
     * @Given I configure Turnitin credentials
     */
    public function i_configure_turnitin_credentials() {
        $account = getenv('TII_ACCOUNT');
        $secret = getenv('TII_SECRET');

        $this->getSession()->getPage()->find("css", "#admin-accountid input")->setValue($account);

        $this->getSession()->getPage()->find('css', '[title="Edit password"]')->click();
        $this->getSession()->getPage()->find("css", "#admin-secretkey input")->setValue($secret);
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
}
