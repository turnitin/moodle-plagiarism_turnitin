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
 * Remote Logging class for plagiarism_turnitin component.
 *
 * @package   plagiarism_turnitin
 * @copyright 2020 Turnitin
 * @author    Grijesh Saini <gsaini@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

/**
 * Class models request info sent to be logged.
 */
class turnitin_remote_logging_request {

    /**
     * @var string the url where we log requests.
     */
    public $url;

    /**
     * @var array The log object.
     */
    public $loggingrequest;

    /**
     * turnitin_remote_logging_request constructor.
     */
    public function __construct() {
        global $remotelogurl;

        $tiiapiurl = get_config('plagiarism_turnitin', 'plagiarism_turnitin_apiurl');
        $this->url = (!empty($remotelogurl[$tiiapiurl])) ? $remotelogurl[$tiiapiurl]. PLAGIARISM_TURNITIN_REMOTE_LOGGING_ENDPOINT : '' ;

        $this->set_basic_details();
    }

    public function send_remote_logs($errorstr) {
        global $CFG;

        // If we have no remote logging URL then return.
        if (empty($this->url)) {
            return;
        }

        $this->set_basic_details();
        $this->loggingrequest["message"] = $errorstr;
        $this->headers[] = 'Content-Type: application/json';

        // Make request to remote logging endpoint.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->loggingrequest));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        // Use Moodle's SSL certificate.
        if (is_readable("$CFG->dataroot/moodleorgca.crt")) {
            $sslcertificate = realpath("$CFG->dataroot/moodleorgca.crt");
            curl_setopt($ch, CURLOPT_CAINFO, $sslcertificate);
        }

        // Use Moodle's Proxy details if required.
        if (isset($CFG->proxyhost) AND !empty($CFG->proxyhost)) {
            curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost . ':' . $CFG->proxyport);
        }
        if (isset($CFG->proxyuser) AND !empty($CFG->proxyuser)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, sprintf('%s:%s', $CFG->proxyuser, $CFG->proxypassword));
        }

        $result = curl_exec($ch);

        // Get httpstatus.
        $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpstatus !== 200) {
            plagiarism_turnitin_activitylog("Error posting remote logs: " . $httpstatus, "API_ERROR");
        }
    }

    /**
     * Populate basic and mandatory details.
     */
    private function set_basic_details() {
        global $CFG;

        $this->loggingrequest["integration_type"] = "Moodle";
        $this->loggingrequest["integration_version"] = get_config('plagiarism_turnitin', 'version');
        $this->loggingrequest["lms_version"] = $CFG->branch;
        $this->loggingrequest["log_level"] = "ERROR";
        $this->loggingrequest["date"] = date("Y-m-d H:i:s");

        // Send tenant as TFS:ENVIRONMENT:ACCOUNTID e.g. TFS:UK:1234
        $apienv = $this->get_api_environment();
        $this->loggingrequest["tenant"] = "TFS:".$apienv.":".get_config('plagiarism_turnitin', 'plagiarism_turnitin_accountid');
    }

    private function get_api_environment() {
        $tiiapiurl = get_config('plagiarism_turnitin', 'plagiarism_turnitin_apiurl');

        switch ($tiiapiurl) {
            case PLAGIARISM_TURNITIN_URL_GLOBAL:
                return 'GLOBAL';
                break;
            case PLAGIARISM_TURNITIN_URL_UK:
                return 'UK';
                break;
            case PLAGIARISM_TURNITIN_URL_SANDBOX:
                return 'SANDBOX';
                break;
        }

        // This would indicate we are testing against a non production environment.
        return "OTHER";
    }

}