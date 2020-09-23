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
 * Mentees Plus block settings
 *
 * @package    block_menteesplus
 * @copyright  2020 Michael de Raadt, Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Allow sorting of mentees by profile fields, incl. custom profile fields.
    $sortablefields = [
        'firstname' => get_string('firstname'),
        'lastname'  => get_string('lastname'),
        'email    ' => get_string('email')
    ];
    $customfields = profile_get_custom_fields();
    foreach ($customfields as $key => $field) {
        $sortablefields[$field->shortname] = $field->name;
    }
    $settings->add(new admin_setting_configselect('block_menteesplus/sortby',
        get_string('sortby', 'block_menteesplus'),
        '',
        'firstname',
        $sortablefields)
    );
}