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
     * Sets the block title
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
            'course-view'    => true,
            'site'           => true,
            'mod'            => false,
            'my'             => false
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
            $this->title = get_string('newmenteesblock', 'block_menteesplus');
        }
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        // Get mentees for the current user.
        $userfields = user_picture::fields('u');
        $sql = "SELECT u.id, $userfields
                  FROM {role_assignments} ra, {context} c, {user} u
                 WHERE ra.userid = :mentorid
                   AND ra.contextid = c.id
                   AND c.instanceid = u.id
                   AND c.contextlevel = :contextlevel";
        $params = ['mentorid' => $USER->id, 'contextlevel' => CONTEXT_USER];
        if ($users = $DB->get_records_sql($sql, $params)) {

            // Sort the users by first name.
            usort($users, function($student1, $student2) {
                if ($student1->firstname > $student2->firstname) {
                    return 1;
                } else if ($student1->firstname < $student2->firstname) {
                    return -1;
                }
                return 0;
            });

            // Display list of mentees.
            $this->content->text = html_writer::start_tag('div', ['class' => 'menteesplus']);
            foreach ($users as $user) {

                // Output the mentee's photo.
                $this->content->text .= html_writer::start_tag('div', ['class' => 'mentee']);
                $this->content->text .= html_writer::start_tag('div', ['class' => 'menteename']);
                $this->content->text .= $OUTPUT->user_picture($user, ['size' => 35]);

                // Output the mentee's name.
                $menteename = format_string(fullname($user));
                $namelink = html_writer::link($CFG->wwwroot.'/user/view.php?id='.$user->id.'&course='.SITEID, $menteename);
                $this->content->text .= $namelink;
                $this->content->text .= ' ';
                $this->content->text .= html_writer::end_tag('div');

                // Get the mentee's courses.
                $this->content->text .= html_writer::start_tag('div', ['class' => 'menteecourses']);
                $usercourses = enrol_get_users_courses($user->id, true, array('enddate'), 'sortorder');
                $currentcourses = array_filter($usercourses, function($course) {
                    $classify = course_classify_for_timeline($course);
                    return $classify == COURSE_TIMELINE_INPROGRESS;
                });

                // Output the mentee's courses.
                foreach ($currentcourses as $key => $course) {
                    $coursename = format_string($CFG->navshowfullcoursenames ? $course->fullname : $course->shortname);
                    $courselink = html_writer::link($CFG->wwwroot.'/course/view.php?id='.$course->id, $coursename);
                    $this->content->text .= html_writer::start_tag('span', ['class' => 'menteecourse btn btn-primary']);
                    $this->content->text .= $courselink;
                    $this->content->text .= html_writer::end_tag('span');
                    $this->content->text .= ' ';
                }
                $this->content->text .= html_writer::end_tag('div');
                $this->content->text .= html_writer::end_tag('div');
            }
            $this->content->text .= html_writer::end_tag('div');
        }

        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Returns true if the block can be docked.
     * The menteesplus block can only be docked if it has a non-empty title.
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return parent::instance_can_be_docked() && isset($this->config->title) && !empty($this->config->title);
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
}

