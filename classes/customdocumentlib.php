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
namespace mod_customdocument;

class customdocumentlib {

    /**
     * Send mail after lifting access restriction
     */
    public static function sendmailrestriction() {
        global $CFG, $DB;
        require_once ($CFG->dirroot . '/mod/customdocument/locallib.php');

        $nbsendmail = 0;

        $sql = "SELECT cm.id AS cmid, cd.course AS courseid, cd.id AS customdocid FROM {customdocument} cd ";
        $sql .= "JOIN {course_modules} cm ON cd.id = cm.instance ";
        $sql .= "JOIN {modules} m ON m.id = cm.module ";
        $sql .= "WHERE m.name = ? AND cd.delivery = ?;";
        
        $now = time();
        $customdocs = $DB->get_records_sql($sql, array('customdocument', 5));
        
        // For each customdoc where delivery is "Send to student email after lifting the access restriction".
        foreach ($customdocs as $customdoc) {
            $coursename = $DB->get_field('course', 'fullname',  ['id' => $customdoc->courseid]);
            $coursecontext = \context_course::instance($customdoc->courseid);
            $course = $DB->get_record('course', array('id' => $customdoc->courseid));
            $enrolledusers = get_enrolled_users($coursecontext, '', 0, '*', 'u.id', 0, 0, true);

            // For each enrolled user.
            foreach ($enrolledusers as $user) {
                $context = \context_module::instance($customdoc->cmid);
                $ismanager = has_capability('mod/customdocument:manage', $context, $user->id);
                $coursectx = \context_course::instance($customdoc->courseid);
                $studentroles = array_keys(get_archetype_roles('student'));
                $students = get_role_users($studentroles, $coursectx, false, 'u.id', null, true, '', '', '');
                $isstudent = !empty($students[$user->id]);

                // echo("<br>");
                // var_dump($user->id);
                // var_dump("ismanager : ".$ismanager);
                // var_dump("isstudent : ".$isstudent);

                // If the user is not admin and not manager (e.g. teacher) unless he is a student.
                if(!is_siteadmin($user->id) && (!$ismanager || ($ismanager && $isstudent))){
                    $modinfo = get_fast_modinfo($customdoc->courseid, $user->id);
                    $cm = $modinfo->get_cm($customdoc->cmid);
                    // var_dump("cm->available : ".$cm->available);

                    // If the restriction is lifted.
                    if($cm->available){
                        $customdocument = new \customdocument($context, $cm, $course);
                        $issuecert = $customdocument->get_issue($user);
                        // var_dump($issuecert);

                        // If the document exists and was created now.
                        if ($customdocument->get_issue_file($issuecert)) {
                            if($issuecert->timecreated >= $now){
                                // Send the mail.
                                $nbsendmail++;
                                mtrace("Utilisateur $user->id du cours '$coursename ($customdoc->courseid)', document '$cm->name'");
                                $ret = $customdocument->send_certificade_email($issuecert);
                            }
                        }
                    }
                }
            }
        }
        if($nbsendmail == 0){
            mtrace("Aucun document envoyé par mail");
        }
        else if($nbsendmail == 1){
            mtrace("$nbsendmail document envoyé par mail");

        }
        else{
            mtrace("$nbsendmail documents envoyés par mail");
            
        }
    }
}