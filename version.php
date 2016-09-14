<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$plugin->version =  2016091401;
$plugin->release = "2.6+";
$plugin->requires =  2013111800;
$plugin->component = 'plagiarism_turnitin';
$plugin->maturity  = MATURITY_STABLE;

global $CFG;
$plugin->cron = 0;
if (!empty($CFG->version)) {
	$plugin->cron = ($CFG->version > 2014051200) ? 0 : 300;
}

$plugin->dependencies = array(
	'mod_turnitintooltwo' => 2016091401,
    'mod_assign' => 2013110500
);
