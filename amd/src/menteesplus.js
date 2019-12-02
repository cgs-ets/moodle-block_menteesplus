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
 * @package    block_menteesplus
 * @copyright  2019 Michael de Raadt michaelderaadt@gmai.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax'], function($, Ajax) {
    "use strict";

    /**
     * Click handler to toggle the given nav node.
     * @param {Object} node The nav node which should be toggled.
     */
    function toggleClickHandler(click, toggle, userid) {
        click.click(function(e) {
            // Prevent that the browser opens the node's default action link (if existing).
            e.preventDefault();

            // Toggle expanded/collapsed.
            if (toggle.attr('data-collapse') == 0) {
                toggle.attr("data-collapse", "1");
                click.html("&#xf0da;");
            } else if (toggle.attr('data-collapse') == 1) {
                toggle.attr("data-collapse", "0");
                click.html("&#xf0d7;");
            }

            // Save toggle state as a preference.
            var value = toggle.attr('data-collapse') == '1' ? 1 : 0;
            var preferences = [{
                'name': 'block_menteesplus_collapsed',
                'value': value,
                'userid': userid
            }];
            Ajax.call([{
                methodname: 'core_user_set_user_preferences',
                args: { preferences: preferences },
            }]);

        });
    }

    return {
        init: function(clickNode, toggleNode, userid, keepExpanded) {
            // Search node to be collapsible.
            var click = $('.' + clickNode);

            // Search node to be collapsible.
            var toggle = $('.' + toggleNode);

            // Add a click handler to this node.
            toggleClickHandler(click, toggle, userid);

            // Set initial state to collapsed.
            if (!keepExpanded) {
                toggle.attr("data-collapse", "1");
            }
        }
    };
});