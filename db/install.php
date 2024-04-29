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
 * @package     mod_customdocument
 * @copyright   2024 Patrick ROCHET <patrick.r@lmsfactory.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_customdocument_install() {
    // Creation of the course custom fields in the 'Version' category.
    require_login();

    $handler = core_course\customfield\course_handler::create();
    $categoryid = $handler->create_category(get_string('version_category', 'customdocument'));

    if ($categoryid) {
        $category = \core_customfield\category_controller::create($categoryid);

        // 'Current version' field.
        $type = "text";
        $field = \core_customfield\field_controller::create(0, (object)['type' => $type], $category);

        $handler = $field->get_handler();
        if (!$handler->can_configure()) {
            print_error('nopermissionconfigure', 'core_customfield');
        }

        $data = new stdClass();
        $data->name = get_string('courseversion', 'customdocument');
        $data->shortname = 'courseversion';
        $data->configdata = array("required" => "0", "uniquevalues" => "0", "defaultvalue" => "", "displaysize" => 10, "maxlength" => 40, "ispassword" => "0", "link" => "",  "locked" => "0",  "visibility" => "2");
        $data->mform_isexpanded_id_header_specificsettings = 1;
        $data->mform_isexpanded_id_course_handler_header = 1;
        $data->categoryid = $categoryid;
        $data->type = $type;
        $data->id = 0;

        $handler->save_field_configuration($field, $data);
    }

    return true;
}

