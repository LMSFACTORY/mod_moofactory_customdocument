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
 * Simple Certificate module core interaction API
 *
 * @package mod
 * @subpackage customdocument
 * @copyright Carlos Fonseca <carlos.alexandre@outlook.com>, Mark Nelson <mark@moodle.com.au>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');

/**
 * Adds a customdocument instance
 * This is done by calling the add_instance() method of the assignment type class
 *
 * @param stdClass $data
 * @param mod_assign_mod_form $form
 * @return int The instance id of the new customdocument
 */
function customdocument_add_instance(stdclass $data) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/customdocument/locallib.php');

    $context = context_module::instance($data->coursemodule);
    $customdocument = new customdocument($context, null, null);
    $data->id = $data->instance;

    return $customdocument->add_instance($data);

}

/**
 * Update certificate instance.
 *
 * @param stdClass $certificate
 * @return bool true
 */
function customdocument_update_instance(stdclass $data) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/customdocument/locallib.php');

    $context = context_module::instance($data->coursemodule);
    $customdocument = new customdocument($context, null, null);
    $data->id = $data->instance;

    if (class_exists('\core_completion\api')) {
        $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
        \core_completion\api::update_completion_date_event($data->coursemodule, 'customdocument', $data->id, $completiontimeexpected);
    }

    return $customdocument->update_instance($data);

}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool true if successful
 */
function customdocument_delete_instance($id) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/customdocument/locallib.php');
    $cm = get_coursemodule_from_instance('customdocument', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $customdocument = new customdocument($context, null, null);
    return $customdocument->delete_instance();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified certificate
 * and clean up any related data.
 * Written by Jean-Michel Vedrine
 *
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function customdocument_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'customdocument');
    $status = array();

    if (!empty($data->reset_certificate)) {
        $timedeleted = time();
        $certificates = $DB->get_records('customdocument', array('course' => $data->courseid));

        foreach ($certificates as $certificate) {
            $issuecertificates = $DB->get_records('customdocument_issues',
                                                array('certificateid' => $certificate->id, 'timedeleted' => null));

            foreach ($issuecertificates as $issuecertificate) {
                $issuecertificate->timedeleted = $timedeleted;
                if (!$DB->update_record('customdocument_issues', $issuecertificate)) {
                    print_error(get_string('cantdeleteissue', 'customdocument'));
                }
            }
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('modulenameplural', 'customdocument'),
                'error' => false);
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        shift_course_mod_dates('customdocument', array('timecreated'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the certificate.
 * Written by Jean-Michel Vedrine
 *
 * @param $mform form passed by reference
 */
function customdocument_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'customdocumentheader', get_string('modulenameplural', 'customdocument'));
    $mform->addElement('advcheckbox', 'reset_customdocument', get_string('deletissuedcertificates', 'customdocument'));
}

/**
 * Course reset form defaults.
 * Written by Jean-Michel Vedrine
 *
 * @param stdClass $course
 * @return array
 */
function customdocument_reset_course_form_defaults($course) {
    return array('reset_customdocument' => 1);
}

/**
 * Returns information about received certificate.
 * Used for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $certificate
 * @return stdClass the user outline object
 */
function customdocument_user_outline($course, $user, $mod, $certificate) {
    global $DB;

    $result = new stdClass();
    if ($issue = $DB->get_record('customdocument_issues',
                                array('certificateid' => $certificate->id, 'userid' => $user->id, 'timedeleted' => null))) {
        $result->info = get_string('issued', 'customdocument');
        $result->time = $issue->timecreated;
    } else {
        $result->info = get_string('notissued', 'customdocument');
    }

    return $result;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of certificate.
 *
 * @param int $certificateid
 * @return stdClass list of participants
 */
function customdocument_get_participants($certificateid) {
    global $DB;

    $sql = "SELECT DISTINCT u.id, u.id
            FROM {user} u, {customdocument_issues} a
            WHERE a.certificateid = :certificateid
            AND u.id = a.userid
            AND timedeleted IS NULL";
    return $DB->get_records_sql($sql, array('certificateid' => $certificateid));
}

/**
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function customdocument_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPMEMBERSONLY:
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}

/**
 * Obtains the automatic completion state for this forum based on any conditions
 * in customdocument settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function customdocument_get_completion_state($course, $cm, $userid, $type) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/customdocument/locallib.php');

    $context = context_module::instance($cm->id);
    $customdocument = new customdocument($context, $cm, $course);

    $requiredtime = $customdocument->get_instance()->requiredtime;
    if ($requiredtime) {
        return ($customdocument->get_course_time($userid) >= $requiredtime);
    }
    // Completion option is not enabled so just return $type.

    return $type;
}

/**
 * Function to be run periodically according to the moodle cron
 */
function customdocument_cron() {
    global $CFG, $DB;
    mtrace('Removing old issed certificates... ');
    $lifetime = get_config('customdocument', 'certlifetime');

    if ($lifetime <= 0) {
        return true;
    }

    $month = 2629744;
    $timenow = time();
    $delta = $lifetime * $month;
    $timedeleted = $timenow - $delta;

    if (!$DB->delete_records_select('customdocument_issues', 'timedeleted <= ?', array($timedeleted))) {
        return false;
    }
    mtrace('done');
    return true;
}

/**
 * Serves certificate issues files, only in admin page.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool nothing if file not found, does not return anything if found - just send the file
 */
function customdocument_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB;

    if ($filearea == 'tmp') {
        // Beacuse bug #141 forceloginforprofileimage=enabled by passing.

        $filename = array_shift($args);
        $fs = get_file_storage();

        $file = $fs->get_file($context->id, 'mod_customdocument', 'tmp', 0, '/', $filename);
        if ($file) {
            send_stored_file($file, null, 0, false);
        }

    } else {
        require_login($course);

        if ($context->contextlevel != CONTEXT_MODULE) {
            return false;
        }
        // Passing id to wmsendfile, cause a thread, because an robot can download all certificates by
        // add a simple  number sequence (1,2,3,4....) as id value, it's better use the certificate code
        // instead.

        $issuedcert = $DB->get_record("customdocument_issues", array('id' => $id));
        if (!$issuedcert) {
            return false;
        }
        $url = new moodle_url('wmsendfile.php');
        $url->param('code', $issuedcert->code);

        redirect($url);
    }
}

/**
 * Get the course outcomes for for mod_form print outcome.
 *
 * @return array
 */
function customdocument_get_outcomes() {
    global $COURSE;

    // Get all outcomes in course.
    $gradeseq = new grade_tree($COURSE->id, false, true, '', false);
    $gradeitems = $gradeseq->items;
    if ($gradeitems) {
        // List of item for menu.
        $printoutcome = array();
        foreach ($gradeitems as $gradeitem) {
            if (isset($gradeitem->outcomeid)) {
                $itemmodule = $gradeitem->itemmodule;
                $printoutcome[$gradeitem->id] = $itemmodule . ': ' . $gradeitem->get_name();
            }
        }
    }
    if (isset($printoutcome)) {
        $outcomeoptions['0'] = get_string('no');
        foreach ($printoutcome as $key => $value) {
            $outcomeoptions[$key] = $value;
        }
    } else {
        $outcomeoptions['0'] = get_string('nooutcomes', 'grades');
    }

    return $outcomeoptions;
}

/**
 * Used for course participation report (in case certificate is added).
 *
 * @return array
 */
function customdocument_get_view_actions() {
    return array('view', 'view all', 'view report', 'verify');
}

/**
 * Used for course participation report (in case certificate is added).
 *
 * @return array
 */
function customdocument_get_post_actions() {
    return array('received');
}

/**
 * Update the event if it exists, else create
 */
function customdocument_send_event($certificate) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');
    $event = $DB->get_record('event', array('modulename' => 'customdocument', 'instance' => $certificate->id));
    if ($event) {
        $calendarevent = calendar_event::load($event->id);
        $calendarevent->name = $certificate->name;
        $calendarevent->update($calendarevent);
    } else {
        $event = new stdClass();
        $event->name = $certificate->name;
        $event->description = '';
        $event->courseid = $certificate->course;
        $event->groupid = 0;
        $event->userid = 0;
        $event->modulename = 'customdocument';
        $event->instance = $certificate->id;
        calendar_event::create($event);
    }
}

/**
 * Returns certificate text options
 *
 * @param module context
 * @return array
 */
function customdocument_get_editor_options(stdclass $context) {

    return array('subdirs' => 0, 'maxbytes' => 0,
        'maxfiles' => 0, 'changeformat' => 0,
        'context' => $context, 'noclean' => 1,
        'trusttext' => 0
    );
}

/**
 * Get all the modules
 *
 * @return array
 *
 */
function customdocument_get_mods() {
    global $COURSE, $CFG;
    // $grademodules = array();
    // // If in settings page, i don't have any grade_item or should not list them.
    // if ($COURSE->id == SITEID) {
    //     return $grademodules;
    // }
    // $items = grade_item::fetch_all(array('courseid' => $COURSE->id));
    // $items = $items ? $items : array();
    // foreach ($items as $id => $item) {
    //     // Do not include grades for course items.
    //     if ($item->itemtype != 'mod') {
    //         continue;
    //     }
    //     $cm = get_coursemodule_from_instance($item->itemmodule, $item->iteminstance);
    //     $grademodules[$cm->id] = $item->get_name();
    // }
    // asort($grademodules);

    $allmodulesWithcompletion = array();
    $modinfo = get_fast_modinfo($COURSE);
    foreach ($modinfo->instances as $module => $instances) {
        foreach ($instances as $index => $cm) {
            if ($cm->completion == 1 || $cm->completion == 2 || $cm->completion == "2" || $cm->completion == "1" ) {
                $allmodulesWithcompletion[$cm->id] = $cm->name;
            }
        }
    }

    return $allmodulesWithcompletion;
    // return $grademodules;

}

/**
 * Search through all the modules for grade dates for mod_form.
 *
 * @return array
 */
function customdocument_get_date_options() {
    global $CFG;
    require_once(dirname(__FILE__) . '/locallib.php');

    // $dateoptions[customdocument::CERT_ISSUE_DATE] = get_string('issueddate', 'customdocument');
    // $dateoptions[customdocument::COURSE_COMPLETATION_DATE] = get_string('completiondate', 'customdocument');
    // $dateoptions[customdocument::COURSE_START_DATE] = get_string('coursestartdate', 'customdocument');



    if (empty(customdocument_get_mods())) {

        $emptyString []=  get_string('empty_mods', 'customdocument');
        return $emptyString;
    }else {

        return   customdocument_get_mods();

    }

}

/**
 * Search through all the modules for grade data for mod_form.
 *
 * @return array
 */
function customdocument_get_grade_options() {
    require_once(dirname(__FILE__) . '/locallib.php');
    $gradeoptions[customdocument::NO_GRADE] = get_string('nograde');
    $gradeoptions[customdocument::COURSE_GRADE] = get_string('coursegrade', 'customdocument');
    return $gradeoptions + customdocument_get_mods();
}

/**
 * Print issed file certificate link
 *
 * @param stdClass $issuecert The issued certificate object
 * @return string file link url
 */
function customdocument_print_issue_certificate_file(stdClass $issuecert) {
    global $CFG, $OUTPUT;
    require_once(dirname(__FILE__) . '/locallib.php');

    // Trying to cath course module context.
    try {
        $fs = get_file_storage();
        if (!$fs->file_exists_by_hash($issuecert->pathnamehash)) {
            throw new moodle_exception('filenotfound', 'customdocument', null, null, '');
        }
        $file = $fs->get_file_by_hash($issuecert->pathnamehash);
        $output = '<img src="' . $OUTPUT->image_url(file_mimetype_icon($file->get_mimetype())) . '" height="16" width="16" alt="' .
         $file->get_mimetype() . '" />&nbsp;';

        $url = new moodle_url('wmsendfile.php');
        $url->param('code', $issuecert->code);

        $output .= '<a href="' . $url->out(true) . '" target="_blank" >' . s($file->get_filename()) . '</a>';

    } catch (Exception $e) {
        $output = get_string('filenotfound', 'customdocument', '');
    }

    return '<div class="files">' . $output . '<br /> </div>';

}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_customdocument_core_calendar_provide_event_action(calendar_event $event,
                                                            \core_calendar\action_factory $factory) {
    $cm = get_fast_modinfo($event->courseid)->instances['customdocument'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
            get_string('view'),
            new \moodle_url('/mod/customdocument/view.php', ['id' => $cm->id]),
            1,
            true
    );
}