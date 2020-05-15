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
 * Version information
 *
 * @package    auth_cloudflareaccess
 * @copyright  2018 Tim Obezuk (tobezuk@cloudflare.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * Plugin for no authentication.
 */
class auth_plugin_cloudflareaccess extends auth_plugin_base {

    public function __construct() {
        $this->authtype = 'cloudflareaccess';
        $this->config = get_config('auth_cloudflareaccess');
    }

    public function validate_jwt_assertion($jwt, $certs) {  

        global $CFG;

        $certs_object = json_decode($certs);

        try {
            $alg = $certs_object->keys[0]->alg;
        } catch (Exception $e) {
            throw new Exception('Could not extract algorithm from claim');
        }

        require_once($CFG->dirroot . '/auth/cloudflareaccess/vendor/autoload.php');

        $set = new SimpleJWT\Keys\KeySet();
        $set->load($certs);

        try {
            $decoded = SimpleJWT\JWT::decode($jwt, $set, $alg);
            return $decoded->getClaim('email');
        } catch (Exception $e) {
            return false;
        }

    }

    public function fetch_cf_access_certs($url) {
        global $CFG;
        $certs = file_get_contents($url);
        return $certs;
    }

    public function cf_access_find_user($email_address) {

        global $CFG, $DB;

        $user_by_username = $DB->get_record('user', array('username' => $email_address));

        if ($user_by_username) {
            return $user_by_username;
        }

        $users_by_email = $DB->get_records('user', array('email' => $email_address));

        if (count($users_by_email) > 1) {
            print_error(get_string('error_manyemails', 'auth_cloudflareaccess'));
        }

        $user_by_email = reset($users_by_email);

        if ($user_by_email) {
            return $user_by_email;
        }

        $auto_create_new_users = get_config('auth_cloudflareaccess', 'autocreate');

        if ($auto_create_new_users) {
            require_once($CFG->dirroot . '/user/lib.php');
            $user = new stdClass();
            $user->username = $email_address;
            $user->suspended = 0;
            $user->confirmed = 1;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->email = $email_address;
            $user_id = user_create_user($user);
            $user_by_id = $DB->get_record('user', array('id' => $user_id));
            return $user_by_id;
        }

        return false;

    }

    public function cf_access_loginpage_hook() {

        global $CFG;

        if (@$CFG->cfaccess_skip) {
            return false;
        }

        @$jwt = $_SERVER['HTTP_CF_ACCESS_JWT_ASSERTION'];

        if (!$jwt) {
            return false;
        }
        
        $cfaccess_certificates = $this->fetch_cf_access_certs($CFG->wwwroot . '/cdn-cgi/access/certs');

        $email_address = $this->validate_jwt_assertion($jwt, $cfaccess_certificates);

        if ($email_address) {

            $user = $this->cf_access_find_user($email_address);

            if ($user) {
                return complete_user_login($user);
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    public function pre_loginpage_hook() {
        return $this->cf_access_loginpage_hook();
    }

    public function loginpage_hook() {
        return $this->cf_access_loginpage_hook();
    }

    public function postlogout_hook($user) {
        global $CFG;

        @$jwt = $_SERVER['HTTP_CF_ACCESS_JWT_ASSERTION'];

        if ($jwt) {
            redirect($CFG->wwwroot . '/cdn-cgi/access/logout');
        }
        
    }

}

