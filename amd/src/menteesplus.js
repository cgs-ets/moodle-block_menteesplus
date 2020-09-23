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

define(['jquery', 'core/ajax'], function ($, Ajax) {
    "use strict";

    /**
     * Initializes the block controls.
     */
    function init(userid, menteestoggle, blocktoggle, title) {
        console.log('block_menteesplus: initializing controls');

        var region = $('.menteesplus');

        // Init block title
        if (region.attr('data-blocktoggle') == 0) {
            $('section.block_menteesplus').find('.card-title').addClass('show-title');
        }

        // Handle the block toggle event.
        var btnmenteestoggle = $('.menteestoggle');
        btnmenteestoggle.click(function (e) {
            // Prevent that the browser opens the node's default action link (if existing).
            e.preventDefault();

            // Toggle expanded/collapsed.
            if (region.attr('data-menteestoggle') == 1) {
                region.attr("data-menteestoggle", "0");
                btnmenteestoggle.html("&#xf0da;");
            } else if (region.attr('data-menteestoggle') == 0) {
                region.attr("data-menteestoggle", "1");
                btnmenteestoggle.html("&#xf0d7;");
            }

            // Save toggle state as a preference.
            var value = region.attr('data-menteestoggle') == '1' ? 1 : 0;
            var preferences = [{
                'name': 'block_menteesplus_menteestoggle',
                'value': value,
                'userid': userid
            }];
            Ajax.call([{
                methodname: 'core_user_set_user_preferences',
                args: {preferences: preferences},
                done: function (response) {
                    console.log(response);
                }
            }]);
        });

        // Handle the toggle mentees event.
        var btnblocktoggle = $('.blocktoggle');
        btnblocktoggle.click(function (e) {
            // Prevent that the browser opens the node's default action link (if existing).
            e.preventDefault();

            // Toggle show/hide mentees.
            if (region.attr('data-blocktoggle') == 0) { // Closed
                region.attr("data-blocktoggle", "1");
                btnblocktoggle.html("&#xf06e;");
                $('section.block_menteesplus').find(".card-title").removeClass('show-title');
            } else if (region.attr('data-blocktoggle') == 1) { // Opened.
                region.attr("data-blocktoggle", "0");
                $('section.block_menteesplus').find(".card-title").addClass('show-title');
                btnblocktoggle.html("&#xf070;");
            }

            // Save toggle state as a preference.
            var value = region.attr('data-blocktoggle') == '1' ? 1 : 0;

            var preferences = [{
                'name': 'block_menteesplus_blocktoggle',
                'value': value,
                'userid': userid
            }];

            Ajax.call([{
                methodname: 'core_user_set_user_preferences',
                args: {preferences: preferences},
                done: function (response) {
                    console.log(response);
                }
            }]);
        });
    }

    return {
        init: init
    };

});