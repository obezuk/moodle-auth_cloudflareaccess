<?php

defined('MOODLE_INTERNAL') || die();

class auth_cloudflareaccess_testcase extends advanced_testcase {

	public function test_exact_username_match() {

		global $CFG, $DB;
    	$this->resetAfterTest(true);

    	$email = 'exactusername@example.com';

    	$user = $this->getDataGenerator()->create_user(
    		array(
    			'email' => 'otheremail@example.com',
    			'username' => $email
    		)
    	);

    	$authplugin = get_auth_plugin('cloudflareaccess');

    	$user_from_auth = $authplugin->cf_access_find_user($email);

    	$this->assertEquals($user->id, $user_from_auth->id);

	}

	public function test_exact_email_match() {

		global $CFG, $DB;
    	$this->resetAfterTest(true);

    	$email = 'exactemail@example.com';

    	$user = $this->getDataGenerator()->create_user(
    		array(
    			'email' => $email,
    			'username' => 'someusername'
    		)
    	);

    	$authplugin = get_auth_plugin('cloudflareaccess');

    	$user_from_auth = $authplugin->cf_access_find_user($email);

    	$this->assertEquals($user->id, $user_from_auth->id);
		
	}

	public function test_multiple_emails_match() {
		
		global $CFG, $DB;
    	$this->resetAfterTest(true);

    	$email = 'exactemail@example.com';

    	$user = $this->getDataGenerator()->create_user(
    		array(
    			'email' => $email,
    			'username' => 'someusername'
    		)
    	);

    	$user2 = $this->getDataGenerator()->create_user(
    		array(
    			'email' => $email,
    			'username' => 'anotheruser'
    		)
    	);

    	$authplugin = get_auth_plugin('cloudflareaccess');
    	$this->expectException('moodle_exception');
    	$user_from_auth = $authplugin->cf_access_find_user($email);

	}

	public function test_upsert_user() {

		global $CFG, $DB;
    	$this->resetAfterTest(true);

    	$email = 'newuser@example.com';

    	set_config('autocreate', 1, 'auth_cloudflareaccess');

    	$authplugin = get_auth_plugin('cloudflareaccess');

		$user_from_auth = $authplugin->cf_access_find_user($email);

		$this->assertEquals($email, $user_from_auth->username);
		$this->assertEquals($email, $user_from_auth->email);

	}

	public function test_disable_upsert_user() {

		global $CFG, $DB;
    	$this->resetAfterTest(true);

    	$email = 'newuser@example.com';

    	set_config('autocreate', 0, 'auth_cloudflareaccess');

    	$authplugin = get_auth_plugin('cloudflareaccess');

		$user_from_auth = $authplugin->cf_access_find_user($email);

		$this->assertFalse($user_from_auth);

	}
	
}