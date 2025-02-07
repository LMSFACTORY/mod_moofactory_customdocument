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

namespace mod_customdocument;

/**
 * Event observers
 *
 * @package     mod_customdocument
 * @copyright   2024 Patrick ROCHET <patrick.r@lmsfactory.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Triggered when user completes a course.
     *
     * @param \core\event\course_completed $event
     */
    public static function sendemailsorgenerate(\core\event\course_completed $event) {
        global $DB, $CFG;
        require_once ($CFG->dirroot . '/mod/customdocument/locallib.php');

        $sql = "SELECT cd.*, cm.visible FROM {customdocument} cd ";
        $sql .= "JOIN {course_modules} cm ON cd.id = cm.instance ";
        $sql .= "JOIN {modules} m ON m.id = cm.module ";
        $sql .= "WHERE m.name = ? AND cm.course = ? AND (cd.delivery = ? OR cd.delivery = ?) AND cm.deletioninprogress = 0;";
        $customdocs = $DB->get_records_sql($sql, array('customdocument', $event->courseid, 4, 6));

        foreach ($customdocs as $customdoc) {
            $coursevisible = $DB->get_field('course', 'visible',  ['id' => $event->courseid]);
            if($customdoc->visible && $coursevisible){
                $cm = get_coursemodule_from_instance( 'customdocument', $customdoc->id, $event->courseid );
                $context = \context_module::instance($cm->id);
                $course = $DB->get_record('course', array('id' => $cm->course));
                $user = $DB->get_record('user', array('id' => $event->relateduserid));
                $customdocument = new \customdocument($context, $cm, $course);
                $issuecert = $customdocument->get_issue($user);
                // If delivery option is 4, send email with certificate.
                // If delivery option is 6, just generate certificate and no email is sent.
                if ($customdocument->get_issue_file($issuecert)) {
                    if ($customdoc->delivery == 4) {
                        $ret = $customdocument->send_certificade_email($issuecert);
                    }
                }
            }
        }
    }
}