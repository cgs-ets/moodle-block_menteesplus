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

defined('MOODLE_INTERNAL') || die();

/**
 * Mentees Plus block.
 *
 * @package    block_menteesplus
 * @copyright  2019 Michael de Raadt michaelderaadt@gmai.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_menteesplus extends block_base {

    /**
     * Called when the block object is instantiated.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_menteesplus');
    }

    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view'    => false,
            'mod'            => false,
            'my'             => false,
            'user-profile'   => true,
            'site'           => true,
        );
    }

    /**
     * Controls the block title based on instance configuration
     *
     * @return bool
     */
    public function specialization() {
        if (isset($this->config->title) && trim($this->config->title) != '') {
            $this->title = format_string($this->config->title);
        } else {
            $this->title = '';
        }
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        global $CFG, $COURSE, $USER, $DB, $OUTPUT;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if ($COURSE->id == 1) {

            // Get mentees for the current user.
            $userfields = user_picture::fields('u');
            $sql = "SELECT u.id, $userfields
                      FROM {role_assignments} ra, {context} c, {user} u
                     WHERE ra.userid = :mentorid
                       AND ra.contextid = c.id
                       AND c.instanceid = u.id
                       AND c.contextlevel = :contextlevel";
            $params = ['mentorid' => $USER->id, 'contextlevel' => CONTEXT_USER];
            $collapsed = get_user_preferences('block_menteesplus_collapsed', 1, $USER);

            if ($users = $DB->get_records_sql($sql, $params)) {

                // Check if the sort order requires a custom profile field and get it.
                $sortby = get_config('block_menteesplus', 'sortby') ?: 'firstname';
                $customfields = array_map(function ($field) {return $field->shortname;}, profile_get_custom_fields());
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

                // Display list of mentees.
                $this->content->text .= html_writer::start_tag('div', ['class' => 'menteesplus', 'data-collapse' => "$collapsed"]);
                foreach ($users as $user) {

                    $this->content->text .= html_writer::start_tag('div', ['class' => 'mentee']);

                    // Output the mentee's photo and name.
                    $this->content->text .= html_writer::start_tag('div', ['class' => 'menteename']);
                    $this->content->text .= $OUTPUT->user_picture($user, ['size' => 35]);
                    $menteename = format_string(fullname($user));
                    $namelink = html_writer::link($CFG->wwwroot.'/user/view.php?id='.$user->id.'&course='.SITEID, $menteename);
                    $this->content->text .= $namelink;
                    $this->content->text .= ' ';
                    $this->content->text .= html_writer::end_tag('div');

                    // Output the mentee's courses.
                    $this->content->text .= $this->mentee_courses($user);

                    $this->content->text .= html_writer::end_tag('div');
                }
                $this->content->text .= html_writer::tag('div', '', ['class' => 'menteestoggle']);
                $this->content->text .= html_writer::end_tag('div');
            }
            $this->page->requires->js_call_amd('block_menteesplus/menteesplus', 'init',
                ['menteestoggle', 'menteesplus', $USER->id, $collapsed]);
        }
        else {
            // Output the mentee's courses.
            $this->content->text .= html_writer::start_tag('div', ['class' => 'menteesplus']);
            $this->content->text .= $this->mentee_courses($user);
            $this->content->text .= html_writer::end_tag('div');
        }

        return $this->content;
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = !empty($this->config) ? $this->config : new stdClass();

        return (object) [
            'instance' => $configs,
            'plugin' => new stdClass(),
        ];
    }

    /**
     * This block has global settings.
     *
     * @return boolean
     */
    function has_config() {
        return true;
    }

    /**
     * This block has global settings.
     *
     * @param user A user object
     * @return void
     */
    function mentee_courses($user) {
        global $CFG;

        $content = '';

        // Get the mentee's courses.
        $content .= html_writer::start_tag('div', ['class' => 'menteecourses']);
        $usercourses = enrol_get_users_courses($user->id, true, array('enddate'), 'sortorder');
        $currentcourses = array_filter($usercourses, function($course) {
            $classify = course_classify_for_timeline($course);
            return $classify == COURSE_TIMELINE_INPROGRESS;
        });

        // Output the mentee's courses.
        foreach ($currentcourses as $key => $course) {
            $coursename = format_string($CFG->navshowfullcoursenames ? $course->fullname : $course->shortname);
            $courselink = html_writer::link($CFG->wwwroot.'/course/view.php?id='.$course->id, $coursename);
            $content .= html_writer::start_tag('span', ['class' => 'menteecourse btn btn-primary']);
            $content .= $courselink;
            $content .= html_writer::end_tag('span');
            $content .= ' ';
        }
        $content .= html_writer::end_tag('div');

        return $content;
    }
}

