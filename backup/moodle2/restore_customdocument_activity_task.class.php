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
 *
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// Because it exists (must).
require_once($CFG->dirroot . '/mod/customdocument/backup/moodle2/restore_customdocument_stepslib.php');

/**
 * certificate restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_customdocument_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Certificate only has one structure step.
        $this->add_step(
                        new restore_customdocument_activity_structure_step(
                                        'customdocument_structure', 'customdocument.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('customdocument', array('intro'), 'customdocument');
        $contents[] = new restore_decode_content('customdocument', array('certificatetext'), 'customdocument');
        $contents[] = new restore_decode_content('customdocument', array('secondpagetext'), 'customdocument');
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('CUSTOMDOCUMENTVIEWBYID', '/mod/customdocument/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CUSTOMDOCUMENTINDEX', '/mod/customdocument/index.php?id=$1', 'course');
        
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * certificate logs.
     * It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('customdocument', 'add', 'view.php?id={course_module}', '{customdocument}');
        $rules[] = new restore_log_rule('customdocument', 'update', 'view.php?id={course_module}', '{customdocument}');
        $rules[] = new restore_log_rule('customdocument', 'view', 'view.php?id={course_module}', '{customdocument}');
        $rules[] = new restore_log_rule('customdocument', 'received', 'report.php?a={customdocument}', '{customdocument}');
        $rules[] = new restore_log_rule('customdocument', 'view report', 'report.php?id={customdocument}',
                                        '{customdocument}');
        $rules[] = new restore_log_rule('customdocument', 'verifyt', 'verify.php?code={customdocument_issues}',
                                        '{customdocument}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs.
     * It must return one array
     * of {@link restore_log_rule} objects
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();
        // Fix old wrong uses (missing extension).
        $rules[] = new restore_log_rule('customdocument', 'view all', 'index.php?id={course}', null);
        return $rules;
    }

    /*
     * This function is called after all the activities in the backup have been restored. This allows us to get the new course
     * module ids, as they may have been restored after the
     * certificate module, meaning no id was available at the time.
     */
    public function after_restore() {
        global $DB;

        $certificate = $DB->get_record('customdocument', array('id' => $this->get_activityid()));
        if ($certificate) {
            if ($certificate->certdate <= -1000) { // If less or equal -1000, is mark as not sucefully retored in stepslib.
                $certificate->certdate = $certificate->certdate / -1000;

                if ($mapping = restore_dbops::get_backup_ids_record(
                                $this->get_restoreid(), 'course_module', $certificate->certdate)) {
                    // If certdate == certgrade the function get_backup_ids_record for certgrade returns null, could be a bug.
                    if ($certificate->certdate == $certificate->certgrade / -1000) {
                        $certificate->certgrade = $mapping->newitemid;
                    }
                    $certificate->certdate = $mapping->newitemid;
                } else {
                    $this->get_logger()->process(
                           "Failed to restore dependency in customdocument 'certdate'. " .
                           "Backup and restore will not work correctly unless you include the dependent module.",
                           backup::LOG_ERROR);
                }
            }

            if ($certificate->certgrade <= -1000) { // If greater than 0, then it is a grade item value.
                $certificate->certgrade = $certificate->certgrade / -1000;

                $mapping = restore_dbops::get_backup_ids_record(
                    $this->get_restoreid(), 'course_module', $certificate->certgrade
                );
                if ($mapping) {
                    $certificate->certgrade = $mapping->newitemid;
                } else {
                    $this->get_logger()->process(
                           "Failed to restore dependency in customdocument 'certgrade'. " .
                           "Backup and restore will not work correctly unless you include the dependent module.",
                           backup::LOG_ERROR);
                }
            }

            if (!$DB->update_record('customdocument', $certificate)) {
                throw new restore_task_exception('cannotrestore');
            }

            // Process issued files.
            $issues = $DB->get_records('customdocument_issues', array('certificateid' => $certificate->id));
            if ($issues) {

                $fs = get_file_storage();
                foreach ($issues as $issued) {
                    try {
                        $context = context_module::instance($this->get_moduleid());

                        if ($this->get_old_moduleversion() < 2014051000 &&
                             ($user = $DB->get_record("user", array('id' => $issued->userid)))) {
                            $filename = str_replace(' ', '_',
                                                    clean_filename(
                                                                $issued->certificatename . ' ' . fullname($user) . ' ' .
                                                                 $issued->pathnamehash . '.pdf'));
                        } else {
                            $filename = str_replace(' ', '_',
                                                  clean_filename($issued->certificatename . ' ' . $issued->pathnamehash . '.pdf'));
                        }

                        $fileinfo = array('contextid' => $context->id, 'component' => 'mod_customdocument',
                            'filearea' => 'issues', 'itemid' => $issued->pathnamehash, 'filepath' => '/', 'filename' => $filename);

                        if ($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {

                            $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

                            $context = context_user::instance($issued->userid);
                            $newfileinfo = $fileinfo;
                            $newfileinfo['itemid'] = $issued->userid;
                            $newfileinfo['filename'] = str_replace(' ', '_',
                                                                clean_filename(
                                                                            $issued->certificatename . ' ' . $issued->id . '.pdf'));

                            if ($fs->file_exists($newfileinfo['contextid'], $newfileinfo['component'], $newfileinfo['filearea'],
                                                $newfileinfo['itemid'], $newfileinfo['filepath'], $newfileinfo['filename'])) {
                                $newfile = $fs->get_file($newfileinfo['contextid'], $newfileinfo['component'],
                                                        $newfileinfo['filearea'], $newfileinfo['itemid'], $newfileinfo['filepath'],
                                                        $newfileinfo['filename']);
                            } else {
                                $newfile = $fs->create_file_from_storedfile($newfileinfo, $file);
                            }

                            $issued->pathnamehash = $newfile->get_pathnamehash();
                            $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                $fileinfo['itemid']);
                        } else {
                            throw new moodle_exception('filenotfound', 'customdocument', null, null, '');
                        }
                    } catch (Exception $e) {
                        $this->log(" Can't restore file $filename. " . $e->getMessage(), backup::LOG_WARNING);
                        $issued->haschange = 1;
                    }

                    if (!$DB->update_record('customdocument_issues', $issued)) {
                        throw new restore_task_exception('cannotrestore');
                    }
                }
            }
        }
    }
}
