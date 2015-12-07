<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$plugin->version =  2015040112;
$plugin->release = "2.6+";
$plugin->requires =  2013111800;
$plugin->cron     = 300;
$plugin->component = 'plagiarism_turnitin';
$plugin->maturity  = MATURITY_STABLE;

$plugin->dependencies = array(
	'mod_turnitintooltwo' => 2015040111,
    'mod_assign' => 2013110500
);