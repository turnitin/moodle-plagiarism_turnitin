<?php

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

class behat_plagiarism_turnitin extends behat_base {

    /**
     * @Given I switch to iframe with locator :locator
     * @param String $locator
     * @throws \Behat\Mink\Exception\DriverException
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public function i_switch_to_iframe_with_locator($locator)
    {
        $iframe = $this->getSession()->getPage()->find("css", $locator);
        $iframe_name = $iframe->getAttribute("name");
        if ($iframe_name == "") {
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
            $iframe_name = $iframe->getAttribute("name");
            echo "\n\niFrame has new name:  " . $iframe_name . "\n\n";
        } else {
            echo "\n\niFrame already has a name: " . $iframe_name . "\n\n";
        }

        $this->getSession()->getDriver()->switchToIFrame($iframe_name);
    }

    /**
     * @Given I configure Turnitin URL
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function i_configure_turnitin_url()
    {
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
    public function i_configure_turnitin_credentials()
    {
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
    public function i_create_a_unique_user($username)
    {
        $generator = testing_util::get_data_generator();
        $generator->create_user(array(
            'email' => uniqid($username, true) . '@example.com',
            'username' => $username,
            'password' => $username,
        ));
    }

}
