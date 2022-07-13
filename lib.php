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
 * Mentees Plus block lib
 *
 * @package    block_menteesplus
 * @copyright  2020 Michael de Raadt, Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Callback to define user preferences.
 *
 * @return array
 */
function block_menteesplus_user_preferences() {
    $preferences = array();
    $preferences['block_menteesplus_blocktoggle'] = array(
        'type' => PARAM_INT,
        'null' => NULL_NOT_ALLOWED,
        'default' => 1,
        'choices' => array(0, 1),
        'permissioncallback' => function($user, $preferencename) {
            global $USER;
            return $user->id == $USER->id;
        }
    );
    $preferences['block_menteesplus_menteestoggle'] = array(
        'type' => PARAM_INT,
        'null' => NULL_NOT_ALLOWED,
        'default' => 0,
        'choices' => array(0, 1),
        'permissioncallback' => function($user, $preferencename) {
            global $USER;
            return $user->id == $USER->id;
        }
    );

    return $preferences;
}

/**
 * Prepare data for rendering.
 * @param $instanceid.
 * @return array
 */
function block_menteesplus_init($instanceid) {
    global $USER, $PAGE, $DB, $COURSE;

    $data = array(
        'profiletitle' => get_string('profiletitle', 'block_menteesplus'),
        'blocktoggle' => 0,
        'menteestoggle' => 0,
        'title' => '',
        'users' => array(),
        'courses' => array(),
    );
  
    // Print single user's courses for profile page.
    if ( $PAGE->url->get_path() == '/user/profile.php' ) {
        $user = $DB->get_record('user', ['id' => $PAGE->url->get_param('id')]);

        // Output just the mentee's courses.
        $data['courses'] = block_menteesplus_menteecourses($user);
    } else {
        // Get mentees for the current user.
        $context = \context_system::instance();
        $userfields = (\core_user\fields::for_name()->with_identity($context))->get_sql('u');
        $userfields = $userfields->selects;

        $sql = "SELECT u.id $userfields
                  FROM {role_assignments} ra, {context} c, {user} u
                 WHERE ra.userid = :mentorid
                   AND ra.contextid = c.id
                   AND c.instanceid = u.id
                   AND c.contextlevel = :contextlevel";
        $params = array(
            'mentorid' => $USER->id, 
            'contextlevel' => CONTEXT_USER
        );
        if ($users = $DB->get_records_sql($sql, $params)) {
            // Check if the sort order requires a custom profile field and get it.
            $sortby = get_config('block_menteesplus', 'sortby') ?: 'firstname';
            $customfields = array_map(function ($field) {
                return $field->shortname;
            }, profile_get_custom_fields());
            if (in_array($sortby, $customfields)) {
                foreach ($users as $id => $user) {
                    $customefields = profile_user_record($id);
                    $users[$id]->{$sortby} = $customefields->{$sortby};
                }
            }

            // Sort the users.
            usort($users, function($student1, $student2) use ($sortby) {
                if ($student1->{$sortby} > $student2->{$sortby}) {
                    return 1;
                } else if ($student1->{$sortby} < $student2->{$sortby}) {
                    return -1;
                }
                return 0;
            });

            // Prepare the data for rendering.
            $data['users'] = block_menteesplus_menteesdata($users);
        }

        $data['blocktoggle'] = (int) get_user_preferences('block_menteesplus_blocktoggle', 1, $USER);            
        $data['menteestoggle'] = (int) get_user_preferences('block_menteesplus_menteestoggle', 0, $USER);
        $data['blocktogletitle'] = $data['blocktoggle'] == 1 ? 'Hide' : 'Show';
        $data['menteestoggletitle'] = $data['menteestoggle'] == 1 ? 'Collapse' : 'Expand';
        // Get block instance data
        $blockrecord = $DB->get_record('block_instances', array('id' => $instanceid), '*');
        $config = unserialize(base64_decode($blockrecord->configdata));
        $data['title'] = format_string($config->title);
    }
    return $data;
}

/**
 * Prepare data for rendering.
 *
 * @return array
 */
function block_menteesplus_menteesdata($users) {
    global $PAGE;
    // Export user data.
    $usersdata = array();
    foreach ($users as $user) {
        $menteepicture = new \user_picture($user, ['size' => 35]);
        $menteepictureurl = $menteepicture->get_url($PAGE)->out(false);
        $menteepicture->includetoken = $user->id;
        $menteepictureurltok = $menteepicture->get_url($PAGE)->out(false);
        $menteename = format_string(fullname($user));
        $menteeurl = new \moodle_url('/user/profile.php', array('id' => $user->id));

        // Export course data.
        $coursesdata = block_menteesplus_menteecourses($user);
        $usersdata[] = array (
            'menteepictureurl' => $menteepictureurl,
            'menteepictureurltok' => $menteepictureurltok,
            'menteename' => $menteename,
            'menteeurl' => $menteeurl->out(false),
            'courses' => $coursesdata,
        );
    }

    return $usersdata;
}

/**
 * This block has global settings.
 *
 * @param user A user object
 * @return void
 */
function block_menteesplus_menteecourses($user) {
    global $CFG;

    $coursesdata = array();

    // Get the mentee's courses.
    $usercourses = enrol_get_users_courses($user->id, true, array('enddate'), 'sortorder');
    $currentcourses = array_filter($usercourses, function($course) {
        $classify = course_classify_for_timeline($course);
        return $classify == COURSE_TIMELINE_INPROGRESS;
    });

    // Output the mentee's courses.
    foreach ($currentcourses as $key => $course) {
        $coursename = format_string($CFG->navshowfullcoursenames ? $course->fullname : $course->shortname);
        $courseurl = new \moodle_url('/course/view.php', array('id' => $course->id));
        $coursesdata[] = array(
            'coursename' => $coursename,
            'courseurl' => $courseurl,
        );
    }

    return $coursesdata;
}
