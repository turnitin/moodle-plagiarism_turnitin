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
 * @package   plagiarism_turnitin
 * @copyright 2018 iParadigms LLC *
 */

defined('MOODLE_INTERNAL') || die();

class turnitin_user {
    public $id;
    public $tiiuserid;
    private $role;
    public $firstname;
    public $lastname;
    public $fullname;
    public $email;
    public $username;
    public $useragreementaccepted;
    private $enrol;
    private $workflowcontext;
    private $usermessages;
    private $instructorrubrics;
    private $turnitincomms;

    public function __construct($id, $role = "Learner", $enrol = true, $workflowcontext = "site", $finduser = true) {
        $this->id = $id;
        $this->set_user_role($role);
        $this->enrol = $enrol;
        $this->workflowcontext = $workflowcontext;

        $this->firstname = "";
        $this->lastname = "";
        $this->fullname = "";
        $this->email = "";
        $this->username = "";

        if ($id != 0) {
            $this->get_moodle_user($this->id);
            if ($finduser === true) {
                $this->get_tii_user_id();
            }
        }
    }

    /**
     * Returns the Moodle User Data object for the specified user
     *
     * @param var $userid The moodle userid
     * @return object A properly built Moodle User Data object with rebuilt email address
     */
    public function get_moodle_user($userid) {
        global $DB;

        $user = $DB->get_record('user', array('id' => $userid));

        if (empty($user->email) || strpos($user->email, '@') === false) {
            $split = explode('.', $user->username);
            array_pop($split);
            $user->email = join('.', $split);
        }

        $this->firstname = stripslashes(str_replace('/', '', $user->firstname));
        $this->lastname = stripslashes(str_replace('/', '', $user->lastname));
        $this->fullname = fullname($user);

        // Set a default for first and last name in the event they are empty.
        $firstname = trim($this->firstname);
        $this->firstname = (empty($firstname)) ? "Moodle" : $firstname;
        $lastname = trim($this->lastname);
        $this->lastname = (empty($lastname)) ? "User ".$this->id : $lastname;

        $this->email = trim(html_entity_decode($user->email));
        $this->username = $user->username;

        $turnitintooltwouser = $DB->get_record('turnitintooltwo_users', array('userid' => $this->id));

        $this->instructorrubrics = array();
        if (!empty($turnitintooltwouser->instructor_rubrics)) {
            $this->instructorrubrics = (array)json_decode($turnitintooltwouser->instructor_rubrics);
        }

        return $user;
    }

    /**
     * Get's the domain to use for creating a pseudo email address
     *
     * @return string The pseudo domain
     */
    public static function get_pseudo_domain() {
        $config = turnitintooltwo_admin_config();
        $domain = empty($config->pseudoemaildomain) ? TURNITINTOOLTWO_DEFAULT_PSEUDO_DOMAIN : $config->pseudoemaildomain;

        return $domain;
    }

    /**
     * Convert a regular firstname into the pseudo equivelant for student data privacy purpose
     *
     * @return string A pseudo firstname address
     */
    private function get_pseudo_firstname() {
        $config = turnitintooltwo_admin_config();

        return !empty( $config->pseudofirstname ) ? $config->pseudofirstname : TURNITINTOOLTWO_DEFAULT_PSEUDO_FIRSTNAME;
    }

    /**
     * Convert a regular lastname into the pseudo equivelant for student data privacy purpose
     *
     * @param string $email The users email address
     * @return string A pseudo lastname address
     */
    private function get_pseudo_lastname() {
        global $DB;
        $config = turnitintooltwo_admin_config();
        $userinfo = $DB->get_record('user_info_data', array('userid' => $this->id, 'fieldid' => $config->pseudolastname));

        if ((!isset($userinfo->data) || empty($userinfo->data)) && $config->pseudolastname != 0 && $config->lastnamegen == 1) {
            $uniqueid = strtoupper(strrev(uniqid()));
            $userinfo = new stdClass();
            $userinfo->userid = $this->id;
            $userinfo->fieldid = $config->pseudolastname;
            $userinfo->data = $uniqueid;
            if (isset($userinfo->data)) {
                $userinfo->id = $userinfo->id;
                $DB->update_record('user_info_data', $userinfo);
            } else {
                $DB->insert_record('user_info_data', $userinfo);
            }
        } else if ($config->pseudolastname != 0) {
            $uniqueid = $userinfo->data;
        } else {
            $uniqueid = get_string('user');
        }
        return $uniqueid;
    }

    /**
     * A function to return a Turnitin User ID if one exists in turnitintooltwo_users
     * or if none found, it will try and find user in Turnitin. If not found it
     * will create them in Turnitin if necessary
     *
     * @param object $user A data object for the user
     * @return var A Turnitin User ID or null
     */
    private function get_tii_user_id() {
        global $DB;
        $tiiuser = $DB->get_record("turnitintooltwo_users", array("userid" => $this->id), "turnitin_uid, user_agreement_accepted");
        if (!$tiiuser) {
            $this->tiiuserid = 0;
            $this->useragreementaccepted = 0;
        } else {
            $this->tiiuserid = (isset($tiiuser->turnitin_uid) && $tiiuser->turnitin_uid > 0 ) ? $tiiuser->turnitin_uid : 0;
            $this->useragreementaccepted = $tiiuser->user_agreement_accepted;
        }

        if (empty($this->tiiuserid)) {
            $this->tiiuserid = $this->find_tii_user_id();
            if (empty($this->tiiuserid) && $this->enrol) {
                $this->tiiuserid = $this->create_tii_user();
            }
            if (!empty($this->tiiuserid)) {
                $this->save_tii_user();
            }
        }
    }


    /**
     * Check Turnitin to see if this user has previously registered
     *
     * @param object $user_details A data object for the user
     * @return var Turnitin user id if found otherwise null
     */
    private function find_tii_user_id() {
        $config = turnitintooltwo_admin_config();
        $tiiuserid = null;

        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        if (!empty($config->enablepseudo) && $this->role == "Learner") {
            $user = new TiiPseudoUser($this->get_pseudo_domain());
            $user->setPseudoSalt($config->pseudosalt);
        } else {
            $user = new TiiUser();
        }
        $user->setEmail($this->email);

        try {
            $response = $turnitincall->findUser($user);
            $finduser = $response->getUser();
            $tiiuserid = $finduser->getUserId();
            if (empty($tiiuserid)) {
                $tiiuserid = null;
            }
            return $tiiuserid;

        } catch (Exception $e) {
            $toscreen = ($this->workflowcontext == "cron") ? false : true;
            $turnitincomms->handle_exceptions($e, 'userfinderror', $toscreen);
        }
    }


    /**
     * Create the user on Turnitin
     *
     * @param object $user_details A data object for the user
     * @param var $role user role to create
     * @return var Turnitin user id
     */
    private function create_tii_user() {
        $config = turnitintooltwo_admin_config();
        $tiiuserid = null;

        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        // Convert the email, firstname and lastname to pseudos for students if the option is set in config
        // Unless the user is already logged as a tutor then use real details.
        if (!empty($config->enablepseudo) && $this->role == "Learner") {
            $user = new TiiPseudoUser($this->get_pseudo_domain());
            $user->setPseudoSalt($config->pseudosalt);
            $user->setFirstName($this->get_pseudo_firstname());
            $user->setLastName($this->get_pseudo_lastname());
        } else {
            $user = new TiiUser();
            $user->setFirstName($this->firstname);
            $user->setLastName($this->lastname);
        }

        $user->setEmail($this->email);
        $user->setDefaultRole($this->role);

        try {
            $response = $turnitincall->createUser($user);
            $newuser = $response->getUser();
            $tiiuserid = $newuser->getUserId();

            turnitintooltwo_activitylog("Turnitin User created: ".$this->id." (".$tiiuserid.")", "REQUEST");

            return $tiiuserid;

        } catch (Exception $e) {
            $toscreen = ($this->workflowcontext == "cron") ? false : true;
            $turnitincomms->handle_exceptions($e, 'usercreationerror', $toscreen);
        }
    }


    /**
     * Edit the user's details on Turnitin (only name can be updated)
     *
     * @param object $user_details A data object for the user
     * @param var $role user role to create
     */
    public function edit_tii_user() {

        $config = turnitintooltwo_admin_config();
        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        // Only update if pseudo is not enabled.
        if (empty($config->enablepseudo)) {
            $user = new TiiUser();
            $user->setFirstName($this->firstname);
            $user->setLastName($this->lastname);

            $user->setUserId($this->tiiuserid);
            $user->setDefaultRole($this->role);

            try {
                $turnitincall->updateUser($user);
            } catch (Exception $e) {
                $toscreen = ($this->workflowcontext == "cron") ? false : true;
                $turnitincomms->handle_exceptions($e, 'userupdateerror', $toscreen);
            }
        }
    }


    /**
     * Remove Link between moodle user and Turnitin from database
     *
     * @global type $DB
     * @param int $tiidbid The Turnitin database id
     * @return void
     */
    public function unlink_user($tiidbid) {
        global $DB;
        $tiiuser = new stdClass();
        $tiiuser->id = $tiidbid;
        $tiiuser->turnitin_uid = 0;

        // Check if the deleted flag has been set. if yes delete the TII record rather than updating it.
        if ($DB->get_record("user", array('id' => $this->id, 'deleted' => 1), "deleted")) {
            $DB->delete_records('turnitintooltwo_users', array('userid' => $this->id));
        } else {
            $DB->update_record('turnitintooltwo_users', $tiiuser);
        }

        turnitintooltwo_activitylog("User unlinked: ".$this->id." (".$tiidbid.") ", "REQUEST");
    }


    /**
     * Save the link between the moodle user and Turnitin
     *
     * @global type $DB
     * @return void
     */
    private function save_tii_user() {
        global $DB;
        $user = new stdClass();
        $user->userid = $this->id;
        $user->turnitin_uid = $this->tiiuserid;
        $user->turnitin_utp = 1;
        if ($this->role == "Instructor") {
            $user->turnitin_utp = 2;
        }

        if ($turnitintooltwouser = $DB->get_record("turnitintooltwo_users", array("userid" => $this->id))) {
            $user->id = $turnitintooltwouser->id;
            $user->turnitin_utp = $turnitintooltwouser->turnitin_utp;
            if ((!$DB->update_record('turnitintooltwo_users', $user))) {
                if ($this->workflowcontext != "cron") {
                    turnitintooltwo_print_error('userupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                    exit();
                }
            }
        } else if (!$DB->insert_record('turnitintooltwo_users', $user)) {
            if ($this->workflowcontext != "cron") {
                turnitintooltwo_print_error('userupdateerror', 'turnitintooltwo', null, null, __FILE__, __LINE__);
                exit();
            }
        }
    }


    /**
     * Enrol the user on this course/class in Turnitin
     *
     * @param type $tiicourseid id for the course/class in Turnitin
     * @return boolean
     */
    public function join_user_to_class($tiicourseid) {

        $turnitincomms = new turnitintooltwo_comms();

        // We only want an API log entry for this if diagnostic mode is set to Debugging.
        if (empty($config)) {
            $config = turnitintooltwo_admin_config();
        }
        if ($config->enablediagnostic != 2) {
            $turnitincomms->set_diagnostic(0);
        }
        $turnitincall = $turnitincomms->initialise_api();

        $membership = new TiiMembership();
        $membership->setClassId($tiicourseid);
        $membership->setUserId($this->tiiuserid);
        $membership->setRole($this->role);

        try {
            $turnitincall->createMembership($membership);

            turnitintooltwo_activitylog("User ".$this->id." (".$this->tiiuserid.") joined to class (".
                $tiicourseid.")", "REQUEST");

            return true;
        } catch (Exception $e) {
            // Ignore exception as we don't need it, this saves time as the alternative
            // is checking all class memberships to see if user is already enrolled.
            $faultcode = $e->getFaultCode();
            if ($faultcode == 'invaliddata') {
                return null;
            } else {
                return false;
            }
        }
    }

    /**
     * Remove a user from a class in Turnitin
     *
     * @param type $membershipid for the course/class for this user
     * @return boolean true if successful
     */
    public static function remove_user_from_class($membershipid) {

        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        $membership = new TiiMembership();
        $membership->setMembershipId($membershipid);

        try {
            $turnitincall->deleteMembership($membership);
            turnitintooltwo_activitylog("User removed from class - Membership Id: (".$membershipid.")" , "REQUEST");
            return true;
        } catch (Exception $e) {
            $turnitincomms->handle_exceptions($e, 'userremoveerror');
        }
    }


    /**
     * Get whether the student has accepted the Turnitin User agreement
     *
     * @return boolean
     */
    public function get_accepted_user_agreement() {
        global $DB;

        $turnitincomms = new turnitintooltwo_comms();
        $turnitincall = $turnitincomms->initialise_api();

        $user = new TiiUser();
        $user->setUserId($this->tiiuserid);

        try {
            $response = $turnitincall->readUser($user);
            $readuser = $response->getUser();

            if ($readuser->getAcceptedUserAgreement()) {
                $turnitintooltwouser = $DB->get_record('turnitintooltwo_users', array('userid' => $this->id));

                $tiiuserinfo = new stdClass();
                $tiiuserinfo->id = $turnitintooltwouser->id;
                $tiiuserinfo->user_agreement_accepted = 1;

                $DB->update_record('turnitintooltwo_users', $tiiuserinfo);
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            // Avoid API calls when running unit tests.
            if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
                return true;
            }

            $this->set_user_values_from_tii();
            $this->get_accepted_user_agreement();
        }
    }

}