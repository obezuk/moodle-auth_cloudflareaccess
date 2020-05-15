<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

	$settings->add(
	  new admin_setting_configcheckbox(
	    'auth_cloudflareaccess/autocreate',
	    get_string('autocreate', 'auth_cloudflareaccess'),
	    get_string('autocreate_desc', 'auth_cloudflareaccess'),
	    1
	  )
	);

}