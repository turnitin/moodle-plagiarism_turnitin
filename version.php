<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$plugin->version =  2015012413;
$plugin->requires =  2012062500.00;
$plugin->cron     = 300;
$plugin->component = 'plagiarism_turnitin';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release  = '2.3+';
$plugin->dependencies = array('mod_turnitintooltwo' => 2014012413);