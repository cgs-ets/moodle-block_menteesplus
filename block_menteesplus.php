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
 * Mentees Plus block.
 *
 * @package    block_menteesplus
 * @copyright  2019 Michael de Raadt michaelderaadt@gmai.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/menteesplus/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

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
        $this->title = '';
        if (isset($this->config->title) && trim($this->config->title) != '' ) {
            $this->title = format_string($this->config->title);
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
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $data = block_menteesplus_init($this->instance->id);
        if (empty($data['users']) && empty($data['courses'])) {
            return null;
        }
        $this->content->text = $OUTPUT->render_from_template('block_menteesplus/content', $data);

        $title = format_string($this->config->title);  
        $this->page->requires->js_call_amd('block_menteesplus/menteesplus', 'init',
            array($USER->id, $data['menteestoggle'], $data['blocktoggle'], $data['title'])
        );

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
    public function has_config() {
        return true;
    }

}
