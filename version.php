<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$plugin->version =  2016011102;
$plugin->release = "2.6+";
$plugin->requires =  2013111800;
$plugin->cron     = 300;
$plugin->component = 'plagiarism_turnitin';
$plugin->maturity  = MATURITY_STABLE;

$plugin->dependencies = array(
	'mod_turnitintooltwo' => 2016011101,
    'mod_assign' => 2013110500
);