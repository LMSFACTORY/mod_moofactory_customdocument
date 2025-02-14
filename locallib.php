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
 * Custom Document module core interaction API
 *
 * @package mod
 * @subpackage customdocument
 * @copyright Carlos Alexandre Fonseca <carlos.alexandre@outlook.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * @package     mod_customdocument
 * @copyright   2024 Patrick ROCHET <patrick.r@lmsfactory.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/mod/customdocument/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot.'/mod/assign/feedback/editpdf/fpdi/autoload.php');

use core_availability\info;
use core_availability\info_module;
use core\message\inbound\private_files_handler;
use core_table\external\dynamic\get;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use setasign\Fpdi\TcpdfFpdi;


class customdocument {
    /**
     *  module constats using in file storage
     * @var CERTIFICATE_COMPONENT_NAME  base componete name
     * @var CERTIFICATE_CERTIMAGE_FILE_AREA certificate image filearea
     * @var CERTIFICATE_IMAGE_FILE_AREA image filearea
     * @var CERTIFICATE_ISSUES_FILE_AREA issued certificates filearea
     */
    const CERTIFICATE_COMPONENT_NAME = 'mod_customdocument';
    const CERTIFICATE_CERTIMAGE_FILE_AREA = 'certimage';
    const CERTIFICATE_IMAGE_FILE_AREA = 'image';
    const CERTIFICATE_ISSUES_FILE_AREA = 'issues';

    const OUTPUT_OPEN_IN_BROWSER = 0;
    const OUTPUT_FORCE_DOWNLOAD = 1;
    const OUTPUT_SEND_EMAIL = 2;

    // Date Options Const.
    const CERT_ISSUE_DATE = -1;
    const COURSE_COMPLETATION_DATE = -2;
    const COURSE_START_DATE = -3;

    // Grade Option Const.
    const NO_GRADE = 0;
    const COURSE_GRADE = -1;

    // View const.
    const DEFAULT_VIEW = 0;
    const ISSUED_CERTIFCADES_VIEW = 1;
    const BULK_ISSUE_CERTIFCADES_VIEW = 2;

    // Pagination.
    const CUSTOMDOCUMENT_MAX_PER_PAGE = 200;

    /**
     *
     * @var stdClass the assignment record that contains the global settings for this customdocument instance
     */
    private $instance;

    /**
     *
     * @var context the context of the course module for this customdocument instance
     *      (or just the course if we are creating a new one)
     */
    private $context;

    /**
     *
     * @var stdClass the course this customdocument instance belongs to
     */
    private $course;

    /**
     *
     * @var stdClass the course module for this customdocument instance
     */
    private $coursemodule;

    /**
     *
     * @var array cache for things like the coursemodule name or the scale menu -
     *      only lives for a single request.
     */
    private $cache;

    /**
     *
     * @var stdClass the current issued certificate
     */
    private $issuecert;

    /**
     * Constructor for the base customdocument class.
     *
     * @param mixed $coursemodulecontext context|null the course module context
     *        (or the course context if the coursemodule has not been
     *        created yet).
     * @param mixed $coursemodule the current course module if it was already loaded,
     *        otherwise this class will load one from the context as required.
     * @param mixed $course the current course if it was already loaded,
     *        otherwise this class will load one from the context as required.
     */
    public function __construct($coursemodulecontext, $coursemodule = null, $course = null) {
        $this->context = $coursemodulecontext;
        $this->coursemodule = $coursemodule;
        $this->course = $course;
        // Temporary cache only lives for a single request - used to reduce db lookups.
        $this->cache = array();
    }

    /**
     * Add this instance to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @param mod_customdocument_mod_form $mform the form object to get files
     * @return mixed false if an error occurs or the int id of the new instance
     */
    public function add_instance(stdClass $formdata) {
        global $DB;

        // Add the database record.
        $update = $this->populate_customdocument_instance($formdata);
        $update->timecreated = time();
        $update->timemodified = $update->timecreated;

        $returnid = $DB->insert_record('customdocument', $update, true);

        $this->course = $DB->get_record('course', array('id' => $formdata->course), '*', MUST_EXIST);

        $this->instance = $DB->get_record('customdocument', array('id' => $returnid), '*', MUST_EXIST);
        if (class_exists('\core_completion\api')) {
            $completiontimeexpected = !empty($update->completionexpected) ? $update->completionexpected : null;
            \core_completion\api::update_completion_date_event($update->coursemodule, 'customdocument', $returnid, $completiontimeexpected);

        }
        if (!$this->instance) {
            print_error('certificatenot', 'customdocument');
        }

        return $returnid;
    }

    /**
     * Update this instance in the database.
     *
     * @param stdClass $formdata - the data submitted from the form
     * @return bool false if an error occurs
     */
    public function update_instance(stdClass $formdata) {
        global $USER, $DB;

        $update = $this->populate_customdocument_instance($formdata);
        $update->timemodified = time();

        $result = $DB->update_record('customdocument', $update);

        // No update if current course version is higher than issue courseversion (deprecated).
        /******************************************* */
        // Never update exept if the user is manager.
        /******************************************* */
        $courseversion = $this->get_course_custom_field($this->get_course()->id, 'courseversion', 'text');
        $issuedcerts = $DB->get_records('customdocument_issues', array('certificateid' => $this->get_instance()->id,));

        foreach ($issuedcerts as $issuedcert) {
            $ismanager = has_capability('mod/customdocument:manage', $this->context, $issuedcert->userid);
            if(empty($issuedcert->courseversion) || $courseversion <= $issuedcert->courseversion || $issuedcert->courseversion == "--"){
                // $haschange = 1;

                // Never update modification.
                if($ismanager){
                    $haschange = 1;
                }
                else{
                    $haschange = 0;
                }
                // End never update modification.

            }
            else{
                $haschange = 0;
            }
            if (!$DB->execute(
                            'UPDATE {customdocument_issues} SET haschange = :haschange WHERE timedeleted is NULL AND timedisabled is NULL AND id = :issuedcertid',
                            array('haschange' => $haschange, 'issuedcertid' => $issuedcert->id))) {
                print_error('cannotupdatemod', '', '', self::CERTIFICATE_COMPONENT_NAME,
                            'Error update customdocument, markig issues
                        with has change');
            }
        }

        $this->instance = $DB->get_record('customdocument', array('id' => $update->id), '*', MUST_EXIST);
        if (!$this->instance) {
            print_error('certificatenot', 'customdocument');
        }

        return $result;
    }

    /**
     * Delete this instance from the database.
     * @return bool false if an error occurs
     */
    public function delete_instance() {
        global $DB;
        try {
            if ($this->get_instance()) {
                // Delete issued certificates.
                $this->remove_issues($this->get_instance());

                // Delete files associated with this certificate.
                $fs = get_file_storage();
                if (!$fs->delete_area_files($this->get_context()->id)) {
                    return false;
                }

                // Delete the instance.
                return $DB->delete_records('customdocument', array('id' => $this->get_instance()->id));
            }
            return true;
        } catch (moodle_exception $e) {
            print_error($e->errorcode, $e->module, $e->link, $e->a, $e->debuginfo);
        }
    }

    /**
     * Remove all issued certificates for specified certificate id
     *
     * @param mixed stdClass/null $certificateisntance certificate object, certificate id or null
     */
    protected function remove_issues($certificateisntance = null) {
        global $DB;
        try {
            if (empty($certificateisntance)) {
                $certificateisntance = $this->get_instance();
            }

            if ($issues = $DB->get_records_select('customdocument_issues',
                                                'certificateid = :certificateid AND timedeleted is NULL',
                                                array('certificateid' => $certificateisntance->id))) {

                foreach ($issues as $issue) {
                    if (!$this->remove_issue($issue)) {
                        // TODO add exception msg.
                        throw new moodle_exception('TODO');
                    }
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Remove an issue certificate
     *
     * @param stdClass $issue Issue certificate object
     * @param boolean $movecertfile Move the certificate file to usuer private folder (defaul true)
     * @return bool true if removed
     */
    protected function remove_issue(stdClass $issue,  $movecertfile = true) {
        global $DB;

        // Try to move certificate to users private file area.
        try {
            // Try to get issue file.
            if (!$this->issue_file_exists($issue)) {
                throw new moodle_exception('filenotfound', 'customdocument', null, null, 'issue id:[' . $issue->id . ']');
            }
            $fs = get_file_storage();

            // Do not use $this->get_issue_file($issue), it has many functions calls.
            $file = $fs->get_file_by_hash($issue->pathnamehash);

            // Try get user context.
            $userctx = context_user::instance($issue->userid);
            if (!$userctx) {
                throw new moodle_exception('usercontextnotfound', 'customdocument',
                                null, null, 'userid [' . $issue->userid . ']');
            }

            // Check if it's to move certificate file or not.
            if ($movecertfile) {
                $coursename = $issue->coursename;

                $fileinfo = array(
                        'contextid' => $userctx->id,
                        'component' => 'user',
                        'filearea' => 'private',
                        'itemid' => $issue->certificateid,
                        'filepath' => '/certificates/' . $coursename . '/',
                        'filename' => $file->get_filename());

                if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'],
                                    $fileinfo['filepath'], $fileinfo['filename'])) {
                    $newfile = $fs->create_file_from_storedfile($fileinfo, $file);
                    if ($newfile) {
                        $issue->pathnamehash = $newfile->get_pathnamehash();
                    } else {
                        throw new moodle_exception('cannotsavefile', null, null, null, $file->get_filename());
                    }
                }
            } else {
                $file->delete();
            }
        } catch (moodle_exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
            $issue->pathnamehash = '';
        }
        try {
            if ($movecertfile) {
                $issue->timedeleted = time();
                return $DB->update_record('customdocument_issues', $issue);
            } else {
                return $DB->delete_records('customdocument_issues', array('id' => $issue->id));
            }
        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * Get the settings for the current instance of this certificate
     *
     * @return stdClass The settings
     */
    public function get_instance() {
        global $DB;

        if (!isset($this->instance)) {
            $cm = $this->get_course_module();
            if ($cm) {
                $params = array('id' => $cm->instance);
                $this->instance = $DB->get_record('customdocument', $params, '*', MUST_EXIST);
            }
            if (!$this->instance) {
                throw new coding_exception('Improper use of the customdocument class. ' .
                                'Cannot load the customdocument record.');
            }
        }
        if (empty($this->instance->coursename)) {
            $this->instance->coursename = $this->get_course()->fullname;
        }
        return $this->instance;
    }

    /**
     * Get context module.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course.
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;

        if ($this->course) {
            return $this->course;
        }

        if (!$this->context) {
            return null;
        }
        $params = array('id' => $this->get_course_context()->instanceid);
        $this->course = $DB->get_record('course', $params, '*', MUST_EXIST);

        return $this->course;
    }

    /**
     * Get the context of the current course.
     *
     * @return mixed context|null The course context
     */
    public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the customdocument class. ' . 'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }

    /**
     * Get the current course module.
     *
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }

        if ($this->context && $this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id('customdocument', $this->context->instanceid, 0, false, MUST_EXIST);
            return $this->coursemodule;
        }
        return null;
    }

    /**
     * Set the submitted form data.
     *
     * @param stdClass $data The form data (instance)
     */
    public function set_instance(stdClass $data) {

        $this->instance = $data;

    }

    /**
     * Set the context.
     *
     * @param context $context The new context
     */
    public function set_context(context $context) {
        $this->context = $context;
    }

    /**
     * Set the course data.
     *
     * @param stdClass $course The course data
     */
    public function set_course(stdClass $course) {
        $this->course = $course;
    }

    /**
     *
     * @param stdClass $formdata The data submitted from the form
     * @param mod_customdocument_mod_form $mform The form object to get files
     * @return stdClass The customdocument instance object
     */
    private function populate_customdocument_instance(stdclass $formdata) {
        // Clear image filearea and certificate image file area.
        $fs = get_file_storage();
        $fs->delete_area_files($this->get_context()->id, self::CERTIFICATE_COMPONENT_NAME, self::CERTIFICATE_IMAGE_FILE_AREA);
        $fs->delete_area_files($this->get_context()->id, self::CERTIFICATE_COMPONENT_NAME, self::CERTIFICATE_CERTIMAGE_FILE_AREA);
        // Creating a customdocument instace object.
        $update = new stdClass();

        if (isset($formdata->certificatetext['text'])) {

            $fileinfo = self::get_certificate_text_fileinfo($this->context->id);
            $update->certificatetext = $this->save_upload_file($formdata->certificatetext['itemid'], $fileinfo, $formdata->certificatetext['text']);

            if (!isset($formdata->certificatetextformat)) {
                $update->certificatetextformat = $formdata->certificatetext['format'];
            }
            unset($formdata->certificatetext);
        }

        if (isset($formdata->secondpagetext['text'])) {

            $fileinfo = self::get_certificate_secondtext_fileinfo($this->context->id);
            $update->secondpagetext = $this->save_upload_file($formdata->secondpagetext['itemid'], $fileinfo, $formdata->secondpagetext['text']);

            if (!isset($formdata->secondpagetextformat)) {
                $update->secondpagetextformat = $formdata->secondpagetext['format'];
            }
            unset($formdata->secondpagetext);
        }

        if (isset($formdata->thirdpagetext['text'])) {

            $fileinfo = self::get_certificate_thirdtext_fileinfo($this->context->id);
            $update->thirdpagetext = $this->save_upload_file($formdata->thirdpagetext['itemid'], $fileinfo, $formdata->thirdpagetext['text']);

            if (!isset($formdata->thirdpagetextformat)) {
                $update->thirdpagetextformat = $formdata->thirdpagetext['format'];
            }
            unset($formdata->thirdpagetext);
        }

        if (isset($formdata->fourthpagetext['text'])) {

            $fileinfo = self::get_certificate_fourthtext_fileinfo($this->context->id);
            $update->fourthpagetext = $this->save_upload_file($formdata->fourthpagetext['itemid'], $fileinfo, $formdata->fourthpagetext['text']);

            if (!isset($formdata->fourthpagetextformat)) {
                $update->fourthpagetextformat = $formdata->fourthpagetext['format'];
            }
            unset($formdata->fourthpagetext);
        }

        if (isset($formdata->certificateimage)) {
            if (!empty($formdata->certificateimage)) {
                $fileinfo = self::get_certificate_image_fileinfo($this->context->id);
                $formdata->certificateimage = $this->save_upload_file($formdata->certificateimage, $fileinfo);
            }
        } else {
            $formdata->certificateimage = null;
        }

        if (isset($formdata->secondimage)) {
            if (!empty($formdata->secondimage)) {
                $fileinfo = self::get_certificate_secondimage_fileinfo($this->context->id);
                $formdata->secondimage = $this->save_upload_file($formdata->secondimage, $fileinfo);
            }
        } else {
            $formdata->secondimage = null;
        }

        if (isset($formdata->thirdimage)) {
            if (!empty($formdata->thirdimage)) {
                $fileinfo = self::get_certificate_thirdimage_fileinfo($this->context->id);
                $formdata->thirdimage = $this->save_upload_file($formdata->thirdimage, $fileinfo);
            }
        } else {
            $formdata->thirdimage = null;
        }

        if (isset($formdata->fourthimage)) {
            if (!empty($formdata->fourthimage)) {
                $fileinfo = self::get_certificate_fourthimage_fileinfo($this->context->id);
                $formdata->fourthimage = $this->save_upload_file($formdata->fourthimage, $fileinfo);
            }
        } else {
            $formdata->fourthimage = null;
        }

        foreach ($formdata as $name => $value) {
            $update->{$name} = $value;
        }

        if (isset($formdata->instance)) {
            $update->id = $formdata->instance;
            unset($update->instance);
        }

        return $update;
    }

    /**
     * Save upload files in $fileinfo array and return the filename
     *
     * @param string $formitemid Upload file form id
     * @param array $fileinfo The file info array, where to store uploaded file
     * @return string filename
     */
    private function save_upload_file($formitemid, array $fileinfo, $text=null) {
        global $USER;
        // Clear file area.
        if (empty($fileinfo['itemid'])) {
            $fileinfo['itemid'] = '';
        }

        $fs = get_file_storage();
        $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
        $certtext = file_save_draft_area_files($formitemid, $fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                $fileinfo['itemid'], null, $text);

        // Delete current draft
        $usercontext = context_user::instance($USER->id);
        $fs->delete_area_files_select($usercontext->id, 'user', 'draft', "=$formitemid");


        // Get only files, not directories.
        $files = $fs->get_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], '',
                                    false);
        $file = array_shift($files);
        if(!empty($text)){
            return $certtext;
        }
        else{
            if(!empty($file)){
                return $file->get_filename();
            }
        }
    }

    /**
     * Get the first page text fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the first page background image fileinfo
     */
    public static function get_certificate_text_fileinfo($context) {
        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        return array('contextid' => $contextid, // ID of context
                    'component' => self::CERTIFICATE_COMPONENT_NAME, // Usually = table name.
                    'filearea' => self::CERTIFICATE_CERTIMAGE_FILE_AREA, // Usually = table name.
                    'itemid' => 1, // Usually = ID of row in table.
                    'filepath' => '/'); // Any path beginning and ending in /.
    }

    /**
     * Get the second page text fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the first page background image fileinfo
     */
    public static function get_certificate_secondtext_fileinfo($context) {
        $fileinfo = self::get_certificate_text_fileinfo($context);
        $fileinfo['itemid'] = 2;
        return $fileinfo;
    }

    /**
     * Get the third page text fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the first page background image fileinfo
     */
    public static function get_certificate_thirdtext_fileinfo($context) {
        $fileinfo = self::get_certificate_text_fileinfo($context);
        $fileinfo['itemid'] = 3;
        return $fileinfo;
    }

    /**
     * Get the fourth page text fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the first page background image fileinfo
     */
    public static function get_certificate_fourthtext_fileinfo($context) {
        $fileinfo = self::get_certificate_text_fileinfo($context);
        $fileinfo['itemid'] = 4;
        return $fileinfo;
    }

    /**
     * Get the first page background image fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the first page background image fileinfo
     */
    public static function get_certificate_image_fileinfo($context) {
        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        return array('contextid' => $contextid, // ID of context
                    'component' => self::CERTIFICATE_COMPONENT_NAME, // Usually = table name.
                    'filearea' => self::CERTIFICATE_IMAGE_FILE_AREA, // Usually = table name.
                    'itemid' => 1, // Usually = ID of row in table.
                    'filepath' => '/'); // Any path beginning and ending in /.
    }

    /**
     * Get the second page background image fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the second page background image fileinfo
     */
    public static function get_certificate_secondimage_fileinfo($context) {

        $fileinfo = self::get_certificate_image_fileinfo($context);
        $fileinfo['itemid'] = 2;
        return $fileinfo;
    }

    /**
     * Get the third page background image fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the third page background image fileinfo
     */
    public static function get_certificate_thirdimage_fileinfo($context) {

        $fileinfo = self::get_certificate_image_fileinfo($context);
        $fileinfo['itemid'] = 3;
        return $fileinfo;
    }

    /**
     * Get the fourth page background image fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the fourth page background image fileinfo
     */
    public static function get_certificate_fourthimage_fileinfo($context) {

        $fileinfo = self::get_certificate_image_fileinfo($context);
        $fileinfo['itemid'] = 4;
        return $fileinfo;
    }

    /**
     * Get the temporary filearea, used to store user
     * profile photos to make the certiticate
     *
     * @param int/object $context The module context
     * @return the temporary fileinfo
     */
    public static function get_certificate_tmp_fileinfo($context) {

        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        return array('contextid' => $contextid,
                            'component' => self::CERTIFICATE_COMPONENT_NAME,
                            'filearea' => 'tmp',
                            'itemid' => 0,
                            'filepath' => '/');
    }

    /**
     * Get issued certificate object, if it's not exist, it will be create
     *
     * @param mixed User obj or id
     * @param boolean Issue the user certificate if it's not exists (default = true)
     * @return stdClass the issue certificate object
     */
    public function get_issue($user = null, $issueifempty = true) {
        global $DB, $USER;

        if (empty($user)) {
            $userid = $USER->id;
        } else {
            if (is_object($user)) {
                $userid = $user->id;
            } else {
                $userid = $user;
            }
        }

        // Check if certificate has already issued.
        // Trying cached first.

        // The cache issue is from this user ?
        $created = false;

        // echo("<br><br><br>issuedcert<pre>");
        // var_dump($DB->get_record('customdocument_issues',
        // array('userid' => $userid, 'certificateid' => $this->get_instance()->id, 'timedeleted' => null)));
        // echo("</pre>");

        if (!empty($this->issuecert) && $this->issuecert->userid == $userid) {
            if (empty($this->issuecert->haschange)) {
                // ...haschange is marked, if no return from cache.
                return $this->issuecert;
            } else {
                // ...haschange is marked, must update.
                $issuedcert = $this->issuecert;
            }
            // Not in cache, trying get from database.
        } else if (!$issuedcert = $DB->get_record('customdocument_issues',
                        array('userid' => $userid, 'certificateid' => $this->get_instance()->id, 'timedeleted' => null, 'timedisabled' => null))) {
                        // array('userid' => $userid, 'certificateid' => $this->get_instance()->id, 'timedeleted' => null))) {
            // Not in cache and not in DB, create new certificate issue record.

            if (!$issueifempty) {
                // Not create a new one, only check if exists.
                return null;
            }

            // Mark as created.
            $created = true;
            $issuedcert = new stdClass();
            $issuedcert->certificateid = $this->get_instance()->id;
            $issuedcert->coursename = format_string($this->get_instance()->coursename, true);
            $issuedcert->userid = $userid;
            $issuedcert->haschange = 1;
            $formatedcoursename = str_replace('-', '_', $this->get_instance()->coursename);
            $formatedcertificatename = str_replace('-', '_', $this->get_instance()->name);
            $issuedcert->certificatename = format_string($formatedcoursename . '-' . $formatedcertificatename, true);
            $issuedcert->timecreated = time();
            $issuedcert->code = $this->get_issue_uuid();
            // Avoiding not null restriction.
            $issuedcert->pathnamehash = '';

            $coursectx = context_course::instance($this->get_course()->id);
            $studentroles = array_keys(get_archetype_roles('student'));
            // $students = get_role_users($studentroles, $coursectx, false, 'u.id', null, true, '', '', '');
            // $isnotstudent = empty($students[$userid]);

            $alluserroles = get_users_roles($coursectx, array($userid));
            foreach ($alluserroles[$userid] as $userrole) {
                $userroleids[] = $userrole->roleid;
            }
            $isstudent = false;
            foreach ($userroleids as $userroleid) {
                if (in_array($userroleid, $studentroles)) {
                    // User is in a role that is based on a student archetype on the course.
                    $isstudent = true;
                    break;
                }
            }
            // echo("<br><br><br><pre>");
            // var_dump($studentroles);
            // var_dump($alluserroles);
            // var_dump($userroleids);
            // var_dump($isstudent);
            // echo("</pre>");
            // die;
            
            // if (has_capability('mod/customdocument:manage', $this->context, $userid) && $isnotstudent) {
            if (has_capability('mod/customdocument:manage', $this->context, $userid) && !$isstudent) {
                $issuedcert->id = 0;
            } else {
                $issuedcert->id = $DB->insert_record('customdocument_issues', $issuedcert);
                
                // Email to the teachers and anyone else.
                if (!empty($this->get_instance()->emailteachers)) {
                    $this->send_alert_email_teachers($issuedcert);
                }
                
                if (!empty($this->get_instance()->emailothers)) {
                    $this->send_alert_email_others($issuedcert);
                }
            }
        }

        // If cache or db issued certificate is maked as haschange, must update.
        if (!empty($issuedcert->haschange) && !$created) { // Check haschange, if so, reissue.
            $formatedcoursename = str_replace('-', '_', $this->get_instance()->coursename);
            $formatedcertificatename = str_replace('-', '_', $this->get_instance()->name);
            $issuedcert->certificatename = format_string($formatedcoursename . '-' . $formatedcertificatename, true);
            $DB->update_record('customdocument_issues', $issuedcert);
        }

        // Caching to avoid unessecery db queries.
        $this->issuecert = $issuedcert;
        return $issuedcert;
    }

    /**
     * Returns a list of previously issued certificates--used for reissue.
     *
     * @param int $certificateid
     * @return stdClass the attempts else false if none found
     */
    public function get_attempts() {
        global $DB, $USER;

        $sql = "SELECT *
                FROM {customdocument_issues} i
                WHERE certificateid = :certificateid
                AND userid = :userid AND timedeleted IS NULL AND timedisabled IS NULL";

        $issues = $DB->get_records_sql($sql, array('certificateid' => $this->get_instance()->id, 'userid' => $USER->id));
        if ($issues) {
            return $issues;
        }

        return false;
    }

    /**
     * Prints a table of previously issued certificates--used for reissue.
     *
     * @param stdClass $attempts
     * @return string the attempt table
     */
    public function print_attempts($attempts) {
        global $OUTPUT;

        echo $OUTPUT->heading(get_string('summaryofattempts', 'customdocument'));

        // Prepare table header.
        $table = new html_table();
        $table->class = 'generaltable';
        $table->head = array(get_string('issued', 'customdocument'));
        $table->align = array('left');
        $table->attributes = array("style" => "width:20%; margin:auto");
        $gradecolumn = $this->get_instance()->certgrade;

        if ($gradecolumn) {
            $table->head[] = get_string('grade', 'customdocument');
            $table->align[] = 'center';
            $table->size[] = '';
        }
        // One row for each attempt.
        foreach ($attempts as $attempt) {
            $row = array();

            // Prepare strings for time taken and date completed.
            $datecompleted = userdate($attempt->timecreated);
            $row[] = $datecompleted;

            if ($gradecolumn) {
                $attemptgrade = $this->get_grade();
                $row[] = $attemptgrade;
            }

            $table->data[$attempt->id] = $row;
        }

        echo html_writer::table($table);
    }

    /**
     * Returns the grade to display for the certificate.
     *
     * @param int $userid
     * @return string the grade result
     */
    protected function get_grade($userid = null) {
        global $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        // If certgrade = 0 return nothing.
        if (empty($this->get_instance()->certgrade)) { // No grade.
            return '';
        }

        switch ($this->get_instance()->certgrade) {
            case self::COURSE_GRADE: // Course grade.
                $courseitem = grade_item::fetch_course_item($this->get_course()->id);
                if ($courseitem) {
                    $grade = new grade_grade(array('itemid' => $courseitem->id, 'userid' => $userid));
                    $courseitem->gradetype = GRADE_TYPE_VALUE;
                    $coursegrade = new stdClass();
                    $decimals = $courseitem->get_decimals();

                    // If no decimals is set get the default decimals.
                    if (empty($decimals)) {
                        $decimals = 2;
                    }

                    // String used.
                    $coursegrade->points = grade_format_gradevalue(
                        $grade->finalgrade, $courseitem, true, GRADE_DISPLAY_TYPE_REAL, $decimals
                    );
                    $coursegrade->percentage = grade_format_gradevalue(
                        $grade->finalgrade, $courseitem, true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals
                    );
                    $coursegrade->letter = grade_format_gradevalue(
                        $grade->finalgrade, $courseitem, true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0
                    );
                }
            break;

            default: // Module grade.
                // Get grade from a specific module, stored at certgrade.
                $modinfo = $this->get_mod_grade($this->get_instance()->certgrade, $userid);
                if ($modinfo) {
                    // String used.
                    $coursegrade = new stdClass();
                    $coursegrade->points = $modinfo->points;
                    $coursegrade->percentage = $modinfo->percentage;
                    $coursegrade->letter = $modinfo->letter;
                    break;
                }
        }

        return $this->get_formated_grade($coursegrade);
    }

    private function get_formated_grade(stdClass $coursegrade) {
        if (empty($coursegrade)) {
            return '';
        }

        switch ($this->get_instance()->gradefmt) {
            case 1:
                return $coursegrade->percentage;
            break;

            case 3:
                return $coursegrade->letter;
            break;

            default:
                return $coursegrade->points;
            break;
        }
    }

    /**
     * Prepare to print an activity grade.
     *
     * @param int $moduleid
     * @param int $userid
     * @return stdClass bool the mod object if it exists, false otherwise
     */
    protected function get_mod_grade($moduleid, $userid) {
        global $DB;

        $cm = $DB->get_record('course_modules', array('id' => $moduleid));
        if($cm != false){
            $module = $DB->get_record('modules', array('id' => $cm->module));
            $gradeitem = grade_get_grades($this->get_course()->id, 'mod', $module->name, $cm->instance, $userid);
            if ($gradeitem) {
                $item = new grade_item();
                $itemproperties = reset($gradeitem->items);
                foreach ($itemproperties as $key => $value) {
                    $item->$key = $value;
                }
                $modinfo = new stdClass();
                $modinfo->name = utf8_decode($DB->get_field($module->name, 'name', array('id' => $cm->instance)));
                $grade = $item->grades[$userid]->grade;
                $item->gradetype = GRADE_TYPE_VALUE;
                $item->courseid = $this->get_course()->id;

                $modinfo->points = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_REAL, $decimals = 2);
                $modinfo->percentage = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2);
                $modinfo->letter = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);

                $modinfo->hidden = $item->grades[$userid]->hidden;

                if ($grade) {
                    $modinfo->dategraded = $item->grades[$userid]->dategraded;
                } else {
                    $modinfo->dategraded = time();
                }
                return $modinfo;
            }
        }

        return false;
    }

    /**
     * Generate a UUID
     * you can verify the generated code in:
     * http://www.famkruithof.net/uuid/uuidgen?typeReq=-1
     *
     * @return string UUID
     */
    // protected function get_issue_uuid() {
    //     global $CFG;
    //     require_once($CFG->libdir . '/horde/framework/Horde/Support/Uuid.php');
    //     return (string)new Horde_Support_Uuid();
    // }

    /**
     * Generate a V4 UUID.
     * @see https://tools.ietf.org/html/rfc4122
     *
     * @return string UUID
     */
    protected function get_issue_uuid() {
        global $CFG;
        require_once($CFG->libdir . '/classes/uuid.php');
        return \core\uuid::generate();
    }

    /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     *
     * @return array the teacher array
     */
    protected function get_teachers($fusionfield=false) {
        global $CFG, $DB;

        $teachers = array();
        if ($fusionfield && !empty($CFG->coursecontact)) {
            $coursecontactroles = explode(',', $CFG->coursecontact);
        } else {
            list($coursecontactroles, $trash) = get_roles_with_cap_in_context($this->get_context(), 'mod/customdocument:manage');
        }
        foreach ($coursecontactroles as $roleid) {
            $roleid = (int)$roleid;
            $role = $DB->get_record('role', array('id' => $roleid));
            $users = get_role_users($roleid, $this->context, true, '', null, false);
            if ($users) {
                foreach ($users as $teacher) {
                    $manager = new stdClass();
                    $manager->user = $teacher;
                    $manager->username = fullname($teacher);
                    $manager->rolename = role_get_name($role, $this->get_context());
                    $teachers[$teacher->id] = $manager;
                }
            }
        }
        return $teachers;
    }

    /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     *
     * @return array the teacher array
     */
    protected function get_students() {
        global $DB;

        $students = array();
        $coursectx = $this->get_course_context();
        $enrolledusers = get_enrolled_users($coursectx, '', 0, 'u.*', $DB->sql_fullname(), 0, 0, true);
        $studentroles = array_keys(get_archetype_roles('student'));
        $users = get_role_users($studentroles, $coursectx, false, 'u.id', null, true, '', '', '');
        foreach ($enrolledusers as $enrolleduser) {
            $isstudent = !empty($users[$enrolleduser->id]);
            $ismanager = has_capability('mod/customdocument:manage', $this->context, $enrolleduser->id);
            if($isstudent && !$ismanager){
                $students[] = $enrolleduser;
            }
        }
        return $students;
    }


    /**
     * Alerts teachers by email of received certificates.
     * First checks whether the option to email teachers is set for this certificate.
     */
    protected function send_alert_email_teachers($issuedcert) {
        $teachers = $this->get_teachers();
        if (!empty($this->get_instance()->emailteachers) && $teachers) {
            $teachersinfo = array();
            foreach ($teachers as $teacher) {
                $userid = $teacher->user->id;

                $aag = has_capability('moodle/site:accessallgroups', $this->context, $userid);
                $groupmode = $this->coursemodule->groupmode;
                $teachergroups = explode(',', $this->getGroupData($userid, $this->course->id));
                $usergroups = explode(',', $this->getGroupData($issuedcert->userid, $this->course->id));

                $intersect = array_intersect($teachergroups, $usergroups);
                // Searching if intersection between teachergroups and usergroups is an empty string (no group) or a group name.
                $searchnogroup = array_search('', $intersect);
                // If searchnogroup is not false, intersection corresponds to "no group". Don't send mail !

                if (($groupmode == VISIBLEGROUPS || $aag || (!empty($intersect) && $searchnogroup === false)) && has_capability('mod/customdocument:canreceivenotifications', $this->context, $userid)){
                    $info = new stdClass;
                    $info->email = $teacher->user->email;
                    $info->username = ' '.format_string(fullname($teacher->user), true);
                    $teachersinfo[] = $info;
                }
            }
            $this->send_alert_emails_to_all($teachersinfo, $issuedcert);
        }
    }

    /**
     * Alerts others by email of received certificates.
     * First checks whether the option to email others is set for this certificate.
     */
    protected function send_alert_email_others($issuedcert) {
        if (!empty($this->get_instance()->emailothers)) {
            $othersinfo = array();
            $others = explode(',', $this->get_instance()->emailothers);
            if ($others) {
                foreach ($others as $other) {
                    $info = new stdClass;
                    $info->email = $other;
                    $info->username = '';
                    $othersinfo[] = $info;
                }               
                $this->send_alert_emails_to_all($othersinfo, $issuedcert);
            }
        }
    }

    /**
     * Send Alerts email of received certificates
     * despite of teacher and student. It is the corrected version of the send_alert_emails()
     * which had errors.
     * @param array $emails emails arrays
     */
    protected function send_alert_emails_to_all($teachersinfo, $issuedcert) {
        global $USER, $CFG, $DB, $SITE;

        $user = $DB->get_record('user', array('id' => $issuedcert->userid));
        if (!empty($teachersinfo)) {

            $url = new moodle_url($CFG->wwwroot . '/mod/customdocument/view.php',
                                array('id' => $this->coursemodule->id, 'tab' => self::ISSUED_CERTIFCADES_VIEW));

            foreach ($teachersinfo as $teacherinfo) {
                $email = trim($teacherinfo->email);
                if (validate_email($email)) {
                    $file = $this->get_issue_file($issuedcert);
                    if ($file) { // Put in a tmp dir, for e-mail attachament.
                        $fullfilepath = $this->create_temp_file($file->get_filename());
                        $file->copy_content_to($fullfilepath);
                        $relativefilepath = str_replace($CFG->dataroot . DIRECTORY_SEPARATOR, "", $fullfilepath);
            
                        if (strpos($relativefilepath, DIRECTORY_SEPARATOR, 1) === 0) {
                            $relativefilepath = substr($relativefilepath, 1);
                        }
                    }
                    $destination = new stdClass();
                    $destination->email = $email;
                    $destination->id = rand(-10, -1);

                    $info = new stdClass();
                    $info->username = $teacherinfo->username;
                    $info->student = fullname($user);
                    $info->course = format_string($this->get_instance()->coursename, true);
                    $info->document = format_string($this->get_instance()->name, true);
                    $info->url = $url->out();
                    $info->sitefullname = $SITE->fullname;
                    $from = $info->student;
                    $postsubject = get_string('awardedsubject', 'customdocument', $info);

                    // Getting email body plain text.
                    $posttext = get_string('emailteachermail', 'customdocument', $info) . "\n";

                    // Getting email body html.
                    $posthtml = '<font face="sans-serif">';
                    $posthtml .= '<p>' . get_string('emailteachermailhtml', 'customdocument', $info) . '</p>';
                    $posthtml .= '</font>';

                    if ($file) {
                        @email_to_user($destination, $from, $postsubject, $posttext, $posthtml, $relativefilepath, $file->get_filename());
                    }
                    else{
                        @email_to_user($destination, $from, $postsubject, $posttext, $posthtml);
                    }
                }// If it fails, oh well, too bad.
            }
        }
    }
    /**
     * Send Alerts email of received certificates
     *
     * @param array $emails emails arrays
     */
    protected function send_alert_emails($emails) {
        global $USER, $CFG, $DB;

        if (!empty($emails)) {

            $url = new moodle_url($CFG->wwwroot . '/mod/customdocument/view.php',
                                array('id' => $this->coursemodule->id, 'tab' => self::ISSUED_CERTIFCADES_VIEW));

            foreach ($emails as $email) {
                $email = trim($email);
                if (validate_email($email)) {
                    $destination = new stdClass();
                    $destination->email = $email;
                    $destination->id = rand(-10, -1);

                    $info = new stdClass();
                    $info->student = fullname($USER);
                    $info->course = format_string($this->get_instance()->coursename, true);
                    $info->certificate = format_string($this->get_instance()->name, true);
                    $info->url = $url->out();
                    $from = $info->student;
                    $postsubject = get_string('awardedsubject', 'customdocument', $info);

                    // Getting email body plain text.
                    $posttext = get_string('emailteachermail', 'customdocument', $info) . "\n";

                    // Getting email body html.
                    $posthtml = '<font face="sans-serif">';
                    $posthtml .= '<p>' . get_string('emailteachermailhtml', 'customdocument', $info) . '</p>';
                    $posthtml .= '</font>';

                    @email_to_user($destination, $from, $postsubject, $posttext, $posthtml); // If it fails, oh well, too bad.
                }// If it fails, oh well, too bad.
            }
        }
    }

    /**
     * Create PDF object using parameters
     *
     * @return PDF
     */
    protected function create_pdf_object() {

        // Default orientation is Landscape.
        $orientation = 'L';

        if ($this->get_instance()->height > $this->get_instance()->width) {
            $orientation = 'P';
        }

        // Remove commas to avoid a bug in TCPDF where a string containing a commas will result in two strings.
        $keywords = get_string('keywords', 'customdocument') . ',' . format_string($this->get_instance()->coursename, true);
        $keywords = str_replace(",", " ", $keywords); // Replace commas with spaces.
        $keywords = str_replace("  ", " ", $keywords); // Replace two spaces with one.

        $pdf = new TcpdfFpdi($orientation, 'mm', array($this->get_instance()->width, $this->get_instance()->height), true, 'UTF-8');
        $pdf->SetTitle($this->get_instance()->name);
        $pdf->SetSubject($this->get_instance()->name . ' - ' . $this->get_instance()->coursename);
        $pdf->SetKeywords($keywords);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setFontSubsetting(true);
        $pdf->SetMargins(0, 0, 0, true);

        return $pdf;
    }

    /**
     * Create certificate PDF file
     *
     * @param stdClass $issuecert The issue certifcate obeject
     * @param PDF $pdf A PDF object, if null will create one
     * @param bool $isbulk Tell if it is a bulk operation or not
     * @return mixed PDF object or error
     */
    protected function create_pdf(stdClass $issuecert, $pdf = null, $isbulk = false) {
        global $CFG;

        // Check if certificate file is already exists, if issued has changes, it will recreated.
        if (empty($issuecert->haschange) && $this->issue_file_exists($issuecert) && !$isbulk) {
            return false;
        }

        if (empty($pdf)) {
            $pdf = $this->create_pdf_object();
        }

        
        // Getting certificate image.
        $fs = get_file_storage();
        
        if($isbulk && !empty($issuecert->pathnamehash && empty($issuecert->haschange))){
            $file = $fs->get_file_by_hash($issuecert->pathnamehash);
            
            $tmpfile = $file->copy_content_to_temp();
            $pageCount = $pdf->setSourceFile($tmpfile);
            for ($i = 0; $i < $pageCount; $i++) {
                $tpl = $pdf->importPage($i + 1, '/MediaBox');
                $pdf->addPage();
                $pdf->useTemplate($tpl);
            }
        }
        else{
            $pdf->AddPage();

            // Get first page image file.
            if (!empty($this->get_instance()->certificateimage)) {
                // Prepare file record object.
                $fileinfo = self::get_certificate_image_fileinfo($this->context->id);

                $firstpageimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'],
                                $fileinfo['filearea'],
                                $fileinfo['itemid'], $fileinfo['filepath'],
                                $this->get_instance()->certificateimage);

                // Read contents.
                if ($firstpageimagefile) {
                    $tmpfilename = $firstpageimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'first_image_');

                    $pdf->Image($tmpfilename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                    @unlink($tmpfilename);
                } else {
                    print_error(get_string('filenotfound', 'customdocument', $this->get_instance()->certificateimage));
                }
            }

            // Writing text.
            $pdf->SetXY($this->get_instance()->certificatetextx, $this->get_instance()->certificatetexty);
            $certTextVar = $this->get_certificate_text($issuecert, $this->get_instance()->certificatetext, 1);

            $pdf->writeHTMLCell(0, 0, '', '', $certTextVar , 0, 0, 0, true, 'C');

            // Print QR code in first page (if enable).
            if (!empty($this->get_instance()->qrcodefirstpage) && !empty($this->get_instance()->printqrcode)) {
                $this->print_qrcode($pdf, $issuecert->code);
            }

            if (!empty($this->get_instance()->enablesecondpage)) {
                $pdf->AddPage();
                if (!empty($this->get_instance()->secondimage)) {
                    // Prepare file record object.
                    $fileinfo = self::get_certificate_secondimage_fileinfo($this->context->id);
                    // Get file.
                    $secondimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                    $fileinfo['itemid'], $fileinfo['filepath'], $this->get_instance()->secondimage);

                    // Read contents.
                    if (!empty($secondimagefile)) {
                        $tmpfilename = $secondimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'second_image_');
                        $pdf->Image($tmpfilename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                        @unlink($tmpfilename);
                    } else {
                        print_error(get_string('filenotfound', 'customdocument', $this->get_instance()->secondimage));
                    }
                }
                if (!empty($this->get_instance()->secondpagetext)) {
                    $pdf->SetXY($this->get_instance()->secondpagex, $this->get_instance()->secondpagey);
                    $secondpageTextVar = $this->get_certificate_text($issuecert, $this->get_instance()->secondpagetext, 2);

                    $pdf->writeHTMLCell(0, 0, '', '', $secondpageTextVar , 0, 0, 0, true, 'C');
                }
            }

            if (!empty($this->get_instance()->enablethirdpage) && !empty($this->get_instance()->enablesecondpage)) {
                $pdf->AddPage();
                if (!empty($this->get_instance()->thirdimage)) {
                    // Prepare file record object.
                    $fileinfo = self::get_certificate_thirdimage_fileinfo($this->context->id);
                    // Get file.
                    $thirdimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                    $fileinfo['itemid'], $fileinfo['filepath'], $this->get_instance()->thirdimage);

                    // Read contents.
                    if (!empty($thirdimagefile)) {
                        $tmpfilename = $thirdimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'third_image_');
                        $pdf->Image($tmpfilename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                        @unlink($tmpfilename);
                    } else {
                        print_error(get_string('filenotfound', 'customdocument', $this->get_instance()->thirdimage));
                    }
                }
                if (!empty($this->get_instance()->thirdpagetext)) {
                    $pdf->SetXY($this->get_instance()->thirdpagex, $this->get_instance()->thirdpagey);
                    $thirdpageTextVar = $this->get_certificate_text($issuecert, $this->get_instance()->thirdpagetext, 2);

                    $pdf->writeHTMLCell(0, 0, '', '', $thirdpageTextVar , 0, 0, 0, true, 'C');
                }
            }

            if (!empty($this->get_instance()->enablefourthpage) && !empty($this->get_instance()->enablethirdpage) && !empty($this->get_instance()->enablesecondpage)) {
                $pdf->AddPage();
                if (!empty($this->get_instance()->fourthimage)) {
                    // Prepare file record object.
                    $fileinfo = self::get_certificate_fourthimage_fileinfo($this->context->id);
                    // Get file.
                    $fourthimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                    $fileinfo['itemid'], $fileinfo['filepath'], $this->get_instance()->fourthimage);

                    // Read contents.
                    if (!empty($fourthimagefile)) {
                        $tmpfilename = $fourthimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'fourth_image_');
                        $pdf->Image($tmpfilename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                        @unlink($tmpfilename);
                    } else {
                        print_error(get_string('filenotfound', 'customdocument', $this->get_instance()->fourthimage));
                    }
                }
                if (!empty($this->get_instance()->fourthpagetext)) {
                    $pdf->SetXY($this->get_instance()->fourthpagex, $this->get_instance()->fourthpagey);
                    $fourthpageTextVar = $this->get_certificate_text($issuecert, $this->get_instance()->fourthpagetext, 2);

                    $pdf->writeHTMLCell(0, 0, '', '', $fourthpageTextVar , 0, 0, 0, true, 'C');
                }
            }

            if (!empty($this->get_instance()->printqrcode) && empty($this->get_instance()->qrcodefirstpage)) {
                // Add certificade code using QRcode, in a new page (to print in the back).
                if (empty($this->get_instance()->enablesecondpage)) {
                    // If secondpage is disabled, create one.
                    $pdf->AddPage();
                }
                $this->print_qrcode($pdf, $issuecert->code);

            }
        }

        return $pdf;
    }

    /**
     * Put a QR code in cerficate pdf object
     *
     * @param pdf $pdf The pdf object
     * @param string $code The certificate code
     */
    protected function print_qrcode($pdf, $code) {
        global $CFG;
        $style = array('border' => 2, 'vpadding' => 'auto', 'hpadding' => 'auto',
                       'fgcolor' => array(0, 0, 0),  // Black.
                       'bgcolor' => array(255, 255, 255), // White.
                       'module_width' => 1, // Width of a single module in points.
                       'module_height' => 1); // Height of a single module in points.

        $codeurl = new moodle_url("$CFG->wwwroot/mod/customdocument/verify.php");
        $codeurl->param('code', $code);

        $pdf->write2DBarcode($codeurl->out(false), 'QRCODE,M', $this->get_instance()->codex, $this->get_instance()->codey, 50, 50,
                            $style, 'N');
        $pdf->SetXY($this->get_instance()->codex, $this->get_instance()->codey + 49);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell(50, 0, $code, 'LRB', 0, 'C', true, '', 2);
    }

    /**
     * Save a certificate pdf file
     *
     * @param stdClass $issuecert the certificate issue record
     * @return mixed return stored_file if successful, false otherwise
     */
    protected function save_pdf(stdClass $issuecert) {
        global $DB, $CFG;

        // Check if file exist.
        // If issue certificate has no change, it's must has a file.
        if (empty($issuecert->haschange)) {
            if ($this->issue_file_exists($issuecert)) {
                return $this->get_issue_file($issuecert);
            } else {
                print_error(get_string('filenotfound', 'customdocument', ''));
                return false;
            }
        } else {
            // Cache issued cert, to avoid db queries.
            $this->issuecert = $issuecert;
            $pdf = $this->create_pdf($this->get_issue($issuecert->userid));
            if (!$pdf) {
                // TODO add can't create certificate file error.
                print_error('TODO');
                return false;
            }

            // This avoid function calls loops.
            $issuecert->haschange = 0;

            // Remove old file, if exists.
            if ($this->issue_file_exists($issuecert)) {
                $file = $this->get_issue_file($issuecert);
                $file->delete();
            }

            // Prepare file record object.
            $context = $this->get_context();

            $coureseshortname = str_replace(' ', '_', substr($this->get_course()->shortname, 0, 20));
            $certname = str_replace(' ', '_', substr($DB->get_field('customdocument', 'name', ['id'=>$issuecert->certificateid]), 0, 20));
            
            $user = get_complete_user_data('id', $issuecert->userid);
            $userfirstname = str_replace(' ', '_', substr($user->firstname, 0, 10));
            $userlastname = str_replace(' ', '_', substr($user->lastname, 0, 10));

            $filename = $coureseshortname.'-'.$certname.'-'.$userfirstname.'_'.$userlastname.'-'.$issuecert->id.'.pdf';
            // $filename = str_replace(' ', '_', clean_filename($issuecert->certificatename . ' ' . $issuecert->id . '.pdf'));
            $fileinfo = array('contextid' => $context->id,
                    'component' => self::CERTIFICATE_COMPONENT_NAME,
                    'filearea' => self::CERTIFICATE_ISSUES_FILE_AREA,
                    'itemid' => $issuecert->id,
                    'filepath' => '/',
                    'mimetype' => 'application/pdf',
                    'userid' => $issuecert->userid,
                    'filename' => $filename
            );

            $fs = get_file_storage();
           
            if($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])){
                $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
            }
            else{
                $file = $fs->create_file_from_string($fileinfo, $pdf->Output('', 'S'));
            }

            if (!$file) {
                print_error('cannotsavefile', 'error', '', $fileinfo['filename']);
                return false;
            }

            if (!empty($CFG->forceloginforprofileimage)) {
                $this->remove_user_image($issuecert->userid);
            }

            $issuecert->pathnamehash = $file->get_pathnamehash();

            $ismanager = has_capability('mod/customdocument:manage', $this->context, $issuecert->userid);
            if(!$ismanager){
                // Insert courant course version.
                $courseversion = $this->get_course_custom_field($this->get_course()->id, 'courseversion', 'text');
            }
            else{
                $courseversion = "--";
            }
            $issuecert->courseversion = $courseversion;

            $coursectx = context_course::instance($this->get_course()->id);
            $studentroles = array_keys(get_archetype_roles('student'));
            $students = get_role_users($studentroles, $coursectx, false, 'u.id', null, true, '', '', '');
            $isstudent = !empty($students[$issuecert->userid]);
            $ismanager = has_capability('mod/customdocument:manage', $this->context, $issuecert->userid);
            // Verify if user is a manager, if not, update issuedcert.
            if ((!$ismanager || ($ismanager && $isstudent)) && !$DB->update_record('customdocument_issues', $issuecert)) {
                print_error('cannotupdatemod', 'error', null, 'customdocument_issues');
                return false;
            }
            return $file;
        }
    }

    protected function get_course_custom_field($courseid, $name, $type){
        global $DB;
    
        switch($type){
            case "select" :
            case "checkbox" :
                $fieldvalue = "intvalue";
                break;
            case "text" :
                $fieldvalue = "charvalue";
                break;
        }
        $sql = "SELECT cd.$fieldvalue FROM {customfield_data} cd ";
        $sql .= "LEFT JOIN {customfield_field} cf ON cf.id = cd.fieldid ";
        $sql .= "WHERE cd.instanceid = ? AND cf.shortname = ?";
        $record = $DB->get_record_sql(
            $sql,
            array($courseid, $name));
        $value = $record->$fieldvalue;
        return $value;
    }

    protected function get_course_custom_fields($courseid){
        global $DB;
    
        $coursecustomfields = new stdClass();
        $sql = "SELECT cd.id, cf.shortname, cf.type, cd.intvalue, cd.charvalue, cd.value FROM {customfield_data} cd ";
        $sql .= "LEFT JOIN {customfield_field} cf ON cf.id = cd.fieldid ";
        $sql .= "WHERE cd.instanceid = ?";
        $records = $DB->get_records_sql(
            $sql,
            array($courseid));

        foreach ($records as $record) {
            switch($record->type){
                case "select" :
                case "checkbox" :
                    $fieldvalue = "intvalue";
                    break;
                case "text" :
                    $fieldvalue = "charvalue";
                    break;
                case "textarea" :
                    $fieldvalue = "value";
                    break;
            }
            $coursecustomfields->{$record->shortname} = $record->$fieldvalue;
        }
        return $coursecustomfields;
    }

    /**
     * Sends the student their issued certificate as an email
     * attachment.
     *
     * @param $issuecert The issue certificate object
     */
    public function send_certificade_email(stdClass $issuecert) { // previously protected
        global $DB, $CFG, $SITE;

        $user = $DB->get_record('user', array('id' => $issuecert->userid));
        if (!$user) {
            print_error('nousersfound', 'moodle');
        }

        $info = new stdClass();
        $info->username = format_string(fullname($user), true);
        // $certificatename = explode('-', $issuecert->certificatename);
        $info->certificate = format_string($this->get_instance()->name, true);
        $info->course = format_string($this->get_instance()->coursename, true);
        $info->sitefullname = $SITE->fullname;

        $subject = get_string('emailstudentsubject', 'customdocument', $info);
        $message = get_string('emailstudenttext', 'customdocument', $info) . "\n";

        // Make the HTML version more XHTML happy  (&amp;).
        $messagehtml = text_to_html($message);

        // Get generated certificate file.
        $file = $this->get_issue_file($issuecert);
        if ($file) { // Put in a tmp dir, for e-mail attachament.
            $fullfilepath = $this->create_temp_file($file->get_filename());
            $file->copy_content_to($fullfilepath);
            $relativefilepath = str_replace($CFG->dataroot . DIRECTORY_SEPARATOR, "", $fullfilepath);

            if (strpos($relativefilepath, DIRECTORY_SEPARATOR, 1) === 0) {
                $relativefilepath = substr($relativefilepath, 1);
            }

            if (!empty($this->get_instance()->emailfrom)) {
                $from = core_user::get_support_user();
                $from->email = format_string($this->get_instance()->emailfrom, true);
            } else {
                $from = format_string($this->get_instance()->emailfrom, true);
            }

            $ret = email_to_user($user, $from, $subject, $message, $messagehtml, $relativefilepath, $file->get_filename());
            @unlink($fullfilepath);

            return $ret;
        } else {
            print_error(get_string('filenotfound', 'customdocument', ''));
        }
    }



    /**
     * Return a stores_file object with issued certificate PDF file or false otherwise
     *
     * @param stdClass $issuecert Issued certificate object
     * @return mixed <stored_file, boolean>
     */
    public function get_issue_file(stdClass $issuecert) {
        if (!empty($issuecert->haschange)) {
            return $this->save_pdf($issuecert);
        }

        if (!$this->issue_file_exists($issuecert)) {
            return false;
        }

        $fs = get_file_storage();
        return $fs->get_file_by_hash($issuecert->pathnamehash);
    }

    /**
     * Get the time the user has spent in the course
     *
     * @param int $userid User ID (default= $USER->id)
     * @return int the total time spent in seconds
     */
    public function get_course_time($user = null) {
        global $CFG, $USER;

        if (empty($user)) {
            $userid = $USER->id;
        } else {
            if (is_object($user)) {
                $userid = $user->id;
            } else {
                $userid = $user;
            }
        }
        $manager = get_log_manager();
        $selectreaders = $manager->get_readers('\core\log\sql_reader');
        $reader = reset($selectreaders);

        // This can take a log time to process, but it's accurate
        // it's can be done by get only first and last log entry creation time,
        // but it's far more inaccurate,  could have an option to choose.
        set_time_limit(0);
        $totaltime = 0;
        $sql = "action = 'viewed' AND target = 'course' AND courseid = :courseid AND userid = :userid";

        $logs = $reader->get_events_select(
            $sql, array('courseid' => $this->get_course()->id, 'userid' => $userid), 'timecreated ASC', '', ''
        );
        if ($logs) {
            foreach ($logs as $log) {
                if (empty($login)) {
                    // For the first time $login is not set so the first log is also the first login.
                    $login = $log->timecreated;
                    $lasthit = $log->timecreated;
                }
                $delay = $log->timecreated - $lasthit;

                if (!($delay > ($CFG->sessiontimeout))) {
                    // The difference between the last log and the current log is more than
                    // the timeout.
                    // Register session value so that we have found a new session!
                    $totaltime += $delay;
                }
                // Now the actual log became the previous log for the next cycle.
                $lasthit = $log->timecreated;
            }
        }
        return $totaltime / 60;

    }

    /**
     * Delivery the issue certificate
     *
     * @param stdClass $issuecert The issued certificate object
     */
    public function output_pdf(stdClass $issuecert) {
        global $OUTPUT;

        $file = $this->get_issue_file($issuecert);
        if ($file) {
            switch ($this->get_instance()->delivery) {
                case self::OUTPUT_FORCE_DOWNLOAD:
                    send_stored_file($file, 10, 0, true, array('filename' => $file->get_filename(), 'dontdie' => true));
                break;

                case self::OUTPUT_SEND_EMAIL:
                    $this->send_certificade_email($issuecert);
                    echo $OUTPUT->header();
                    echo $OUTPUT->box(get_string('emailsent', 'customdocument') . '<br>' . $OUTPUT->close_window_button(),
                                    'generalbox', 'notice');
                    echo $OUTPUT->footer();
                break;

                // OUTPUT_OPEN_IN_BROWSER.
                default: // Open in browser.
                    send_stored_file($file, 10, 0, false, array('dontdie' => true));
                break;
            }

            $coursectx = context_course::instance($this->get_course()->id);
            $studentroles = array_keys(get_archetype_roles('student'));
            $students = get_role_users($studentroles, $coursectx, false, 'u.id', null, true, '', '', '');
            $isnotstudent = empty($students[$issuecert->userid]);

            if (has_capability('mod/customdocument:manage', $this->context, $issuecert->userid) && $isnotstudent) {
                $file->delete();
            }
        } else {
            print_error(get_string('filenotfound', 'customdocument', ''));
        }
    }

    /**
     * Substitutes the certificate text variables
     *
     * @param stdClass $issuecert The issue certificate object
     * @param string $certtext The certificate text without substitutions
     * @return string Return certificate text with all substutions
     */
    protected function get_certificate_text($issuecert, $certtext = null, $itemid = null) {
        global $DB, $CFG;

        $user = get_complete_user_data('id', $issuecert->userid);
        if (!$user) {
            print_error('nousersfound', 'moodle');
        }

        // If no text set get firstpage text.
        if (empty($certtext)) {
            $certtext = $this->get_instance()->certificatetext;
        }

        $a = new stdClass();
        $a->fullname = strip_tags(fullname($user));
        $a->idnumber = strip_tags($user->idnumber);
        $a->firstname = strip_tags($user->firstname);
        $a->lastname = strip_tags($user->lastname);
        $a->email = strip_tags($user->email);
        // $a->icq = strip_tags($user->icq);
        // $a->skype = strip_tags($user->skype);
        // $a->yahoo = strip_tags($user->yahoo);
        // $a->aim = strip_tags($user->aim);
        // $a->msn = strip_tags($user->msn);
        $a->phone1 = strip_tags($user->phone1);
        $a->phone2 = strip_tags($user->phone2);
        $a->institution = strip_tags($user->institution);
        $a->department = strip_tags($user->department);
        $a->address = strip_tags($user->address);
        $a->city = strip_tags($user->city);

        // Add userimage url only if have a picture.
        if ($user->picture > 0) {
            $a->userimage = $this->get_user_image_url($user);
        } else {
            $a->userimage = '';
        }

        if (!empty($user->country)) {
            $a->country = get_string($user->country, 'countries');
        } else {
            $a->country = '';
        }

        // Formatting URL, if needed.
        // $url = $user->url;
        // if (!empty($url) && strpos($url, '://') === false) {
        //     $url = 'http://' . $url;
        // }
        // $a->url = $url;

        // Getting user custom profiles fields.
        $userprofilefields = $this->get_user_profile_fields($user->id);
        foreach ($userprofilefields as $key => $value) {
            $key = 'profile_' . $key;
            $a->$key = strip_tags($value);
        }

        // The course name never change form a certificate to another, useless
        // text mark and atribbute, can be removed.
        $a->coursename = strip_tags($this->get_instance()->coursename);
        $a->grade = $this->get_grade($user->id);
        $a->activitycompletiondate = $this->get_date($issuecert, $user->id);
        $a->outcome = $this->get_outcome($user->id);
        $a->certificatecode = $issuecert->code;
        $a->documentid = $issuecert->id;

        // NOTE: Each function for all the merge fields is defined below.

        // Merge field for deliverance date.
        $a->deliverancedate = $this->date_deliverance($issuecert);
        // Merge field for course start date.
        $a->coursestartdate = $this->course_start_date($issuecert, $user->id);
        // Merge field for course end date.
        $a->courseenddate = $this->course_end_date($issuecert, $user->id);
        // Merge field for the name of the groups in which the user is registered.
        $a->groupNames = $this->getGroupData($user->id);
        // Merge field for the moofactory time spent given by the statistics settings in the course management.
        if(!is_siteadmin($user)){
            $a->usermoofactorytime = $this->get_moofactory_timespent($user->id);
        }
        // Merge field for the first user access to the course.
        $a->coursefirstaccess = $this->get_first_user_access($user->id);
        // Merge field for the last user access to the course.
        $a->courselastaccess = $this->get_last_user_access($user->id);
        // Merge field for user enrol date to the course.
        $a->enrolmenttimeend = $this->get_user_inscription_time_end($user->id);
        // Merge field for the user course completion date.
        $a->coursecompletiondate = $this->get_course_completion_date($user->id);
        // Merge field for the course version.
        $a->courseversion = $this->get_course_custom_field($this->get_course()->id, 'courseversion', 'text');
        // Getting user custom profiles fields.
        $coursecustomfields = $this->get_course_custom_fields($this->get_course()->id);
        foreach ($coursecustomfields as $key => $value) {
            $key = 'coursecustomfield_' . $key;
            $a->$key = strip_tags($value);
        }

        // This code stay here only because legacy support, coursehours variable was removed
        // see issue 61 https://github.com/bozoh/moodle-mod_customdocument/issues/61.
        // if (isset($this->get_instance()->coursehours)) {
        //     $a->hours = strip_tags($this->get_instance()->coursehours . ' ' . get_string('hours', 'customdocument'));
        // } else {
        //     $a->hours = '';
        // }

        $teachers = $this->get_teachers(true);
        if (empty($teachers)) {
            $a->teachers = '';
        } else {
            $t = array();
            foreach ($teachers as $teacher) {
                $t[] = content_to_text($teacher->rolename . get_string('colon', 'customdocument') . $teacher->username, FORMAT_MOODLE);
            }
            $a->teachers = implode("<br>", $t);
        }

        $students = $this->get_students();
        if (empty($students)) {
            $a->students = '';
        } else {
            $s = array();
            foreach ($students as $student) {
                $s[] = content_to_text($student->firstname . ' ' . $student->lastname, FORMAT_MOODLE);
            }
            $a->students = implode(", ", $s);
        }

        // Fetch user actitivy grades.
        $a->activitygrades = $this->get_activity_grades($issuecert->userid);

        // Get User role name in course.
        $userrolename = get_user_roles_in_course($user->id, $this->get_course()->id);
        if ($userrolename) {
            $a->userrolename = content_to_text($userrolename, FORMAT_MOODLE);
        } else {
            $a->userrolename = '';
        }

        // Get user enrollment start date
        // see funtion  enrol_get_enrolment_end($courseid, $userid), which get enddate, not start.
        $sql = "SELECT ue.timestart
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
              JOIN {user} u ON u.id = ue.userid
              WHERE ue.userid = :userid AND e.status = :enabled AND u.deleted = 0";

        $params = array('enabled' => ENROL_INSTANCE_ENABLED, 'userid' => $user->id, 'courseid' => $this->get_course()->id);

        $timestart = $DB->get_field_sql($sql, $params);

        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        }else {
            $format = $this->get_instance()->certdatefmt;
        }

        if ($timestart) {
            $a->enrolmenttimestart = userdate($timestart, $format);
        } else {
            $a->enrolmenttimestart = '';
        }

        $a = (array)$a;

        // For compatibility with previous versions user's documents, $search and $search2 are used 
        $search = array();
        $search2 = array();
        $replace = array();
        foreach ($a as $key => $value) {
            $search[] = '{' . strtoupper($key) . '}';
            $search2[] = '{{' . strtoupper($key) . '}}';
            // Due #148 bug, i must disable filters, because activities names {USERRESULTS}
            // will be replaced by actitiy link, don't make sense put activity link
            // in the certificate, only activity name and grade
            // para=> false to remove the <div> </div>  form strings.
            $replace[] = (string)$value;
        }

        if ($search2) {
            $certtext = str_replace($search2, $replace, $certtext);
        }

        if ($search) {
            $certtext = str_replace($search, $replace, $certtext);
        }

        $certtext = file_rewrite_pluginfile_urls($certtext, 'pluginfile.php', $this->context->id, 'mod_customdocument', 'certimage', $itemid);

        $certtext = format_text($certtext, FORMAT_HTML, array('noclean' => true));

        // Clear not setted merge fields.
        // $certtext = preg_replace('[\{(.*)\}]', "", $certtext);
        return $this->remove_links(format_text($certtext, FORMAT_MOODLE));
    }

    /**
     * Get the course end date for the user
     */
    protected function get_course_completion_date($userid) {
        global $DB;
        // getting course id
        $courseid = $this->get_course()->id;
        // sql querry to get the data from eh database
        $sql = 'SELECT * FROM {course_completions} WHERE course = :courseid AND userid = :userid';
        // executing the query
        $completion_date = $DB->get_record_sql($sql, array('courseid' => $courseid, 'userid' => $userid)) ;
        // getting the time from the data
        $course_completion_date = $completion_date->timecompleted;

        // using an if condition to verify if the admin has defined any date format
        // if not it will take the default date format
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        }else{
            $format = $this->get_instance()->certdatefmt;
        }


        // converting the time in seconds from the database to the human readable date
        if ($course_completion_date) {
            $course_completion_date = userdate($course_completion_date, $format);
            return $course_completion_date;

        }else {
            $course_completion_date = '';
            return $course_completion_date;
        }
    }

    /**
     * Get the user inscription end time
     * which will take in the userid as a parameter and do the query to the database
     * convert the time in seconds to human readable date and then return in as string
     */
    protected function get_user_inscription_time_end($userid) {
        global $DB;
        // Get the course id
        $courseid = $this->get_course()->id;

        // sql query to get the data from the database
        // here joined the two tables to get the specified data

        $sql = "SELECT ue.*
        FROM {user_enrolments} ue
        JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
        JOIN {user} u ON u.id = ue.userid
        WHERE ue.userid = :userid AND ue.status = :active AND e.status = :enabled AND u.deleted = 0";

        // defining the parameters for the query
        $params = array('enabled'=>ENROL_INSTANCE_ENABLED, 'active'=>ENROL_USER_ACTIVE, 'userid'=>$userid, 'courseid'=>$courseid);

        // executing the query which will return an array of the objects

        $enrolements = $DB->get_records_sql($sql, $params);

        // using the if condition to determine the date format is predefined by the admin
        // If not it will use the date format defined by default

        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        }else {
            $format = $this->get_instance()->certdatefmt;
        }


        // using a foreach loop to get the required value from the array as it is an array of objects

        foreach ($enrolements as $enrol) {
            $timeend  = $enrol->timeend;
            if ($timeend) {
                // converting the time in seconds to human readable date
                $timeend = userdate($timeend, $format);
            }else {
                $timeend ='';
            }
            // returning the date as a string
            return $timeend;
        }
    }

    /**
     * Get the moofactory time
     * which includes timespent, intelliboard and log time
     * for each type there is only one function but admin can defined which time to use in the
     * course setting
     */
    protected function get_moofactory_timespent ($userid) {

        // Checking if the plugin format exist
        if (array_key_exists('moofactory', \core_component::get_plugin_list('format'))) {
            $formatmoofactoryinstance = \format_moofactory\format::getInstance();

              // Get the course id
        $courseid = $this->get_course()->id;

        // Getting the time moofactory
        $usertimespent = $formatmoofactoryinstance->get_user_timespent($courseid, $userid);
        if ($usertimespent > 0) {

            // Converting the time in the hours and mins
            $hours = floor($usertimespent / 3600);
            $minutes = floor(($usertimespent - $hours * 3600) / 60);
            $usertime = $hours .' h '. $minutes .' min';

        }else {
            $usertime = "0 h 0 min";
        }
        }else {
            $usertime = get_string('formatnotfound', 'customdocument');
        }


        // Returning the time as a sting
        return $usertime;

    }

    /**
     * Get the user last access date of the course
     * It will take user id as a parameter and do the query to the database
     * and return the value as a string
     */
    protected function get_last_user_access ($userid) {

            global $DB;

            // using the get_field function to get the single field from the database
            $record = $DB->get_field('user_lastaccess', 'timeaccess', array('userid' => $userid, 'courseid' => $this->get_course()->id), IGNORE_MISSING);

            // Using he if statement to check the format is defined by the admin
            // If not then it will take the default date format
            if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
            }else {
                $format = $this->get_instance()->certdatefmt;
            }
            // Checking if the record is not empty and then convrting it to the human readable date
            if (!empty($record)) {
                $lastaccess = userdate(substr($record, 0, 10), $format);
                return $lastaccess;
            }

            // Returning the date as a string

    }

    /**
     * Get the first access date of the course
     * takes in userid as a parameter and then do a query to the database
     * and return the value as a string
     */

    protected function get_first_user_access ($userid) {
        global $DB;

        // Get course id
        $courseid = $this->get_course()->id;
        // Prepare sql query to get the created time value from the database based on the user id and course id
        $sql = "SELECT timecreated FROM {logstore_standard_log} WHERE userid = :userid AND courseid = :courseid ORDER BY timecreated ASC";
        // Defining the parameters of the query
        $params = array('userid' => $userid, 'courseid' => $courseid);
        // Executing the query
        $records = $DB->get_records_sql($sql, $params);
        $record = array_key_first($records);

        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        }else {
            $format = $this->get_instance()->certdatefmt;
        }


        // Converting the time in sec to human readable date
        if ($record) {
            $firstaccess = userdate(substr($record, 0, 10), $format);
            return $firstaccess;
        }else {
            $firstaccess = '';
            return $firstaccess;
        }
        // returning the date as a string
        // return $firstaccess;
    }

    /**
     * Get all the group names in which the participant is added
     * takes in userid as a parameter and then do a query to the database
     * and return the value as a string
     */
    protected function getGroupData ($userid, $courseid = null) {
        global $DB;

        // sql query to get the group names from the database
        if(empty($courseid)){
            $sql = 'SELECT g.name as groupname FROM {groups} g INNER JOIN {groups_members} gm ON gm.groupid = g.id WHERE gm.userid = :userid';
            $param = array('userid' => $userid);
        }
        else{
            $sql = 'SELECT g.name as groupname FROM {groups} g INNER JOIN {groups_members} gm ON gm.groupid = g.id WHERE gm.userid = :userid AND g.courseid = :courseid';
            $param = array('userid' => $userid, 'courseid' => $courseid);
        }
        // executing the query using the function get_records_sql which return an array of objects
        // with the first field of the table as an index which in this case is id of the group
        $groupData = $DB->get_records_sql($sql, $param);

        // using array_values function to change the index of the array to 0,1,3 ....
        // previously was like 64,65,66 .... etc
        $groupData = array_values($groupData);

        // looping through the array and getting the string (groupname)
        $groupName = array();
        for ($i=0; $i < count($groupData); $i++) {
            $groupName[] = $groupData[$i]->groupname;
        }

        // converting the array to a single string and then returning back
        $groupNames = implode(',', $groupName);
        return $groupNames;
    }

    /**
     * Get the deliverance date
     * takes in $issuecert obj as a parameter and then do a query to the database
     * and return the value as a string
     */
    protected function date_deliverance (stdClass $issuecert) {
        // Get date format.
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        } else {
            $format = $this->get_instance()->certdatefmt;
        }
        $date = time();

        $date = $issuecert->timecreated;

        // converting and returning the date as a string
        return (userdate($date, $format));
    }

    /**
     * Get the course start date
     * takes in $issuecert obj as a parameter and then do a query to the database
     * and return the value as a string
     */
    protected function course_start_date (stdClass $issuecert) {
        global $DB;
        // Get date format.
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        } else {
            $format = $this->get_instance()->certdatefmt;
        }
        $date = time();
        // Prepare the query to fetch the data from the database
        $sql = "SELECT id, startdate FROM {course} c WHERE c.id = :courseid";
        // Execute the query
        $coursestartdate = $DB->get_record_sql($sql, array('courseid' => $this->get_course()->id));
        // Get the concerning value
        $date = $coursestartdate->startdate;
        // Converting and returning the date
        return userdate($date, $format);
    }

    /**
     * Get the course end date
     * takes in $issuecert obj as a parameter and then do a query to the database
     * and return the value as a string
    */
    protected function course_end_date (stdClass $issuecert) {
        global $DB;
        // Get date format
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        }else {
            $format = $this->get_instance()->certdatefmt;
        }

        $date = time();

        // Prepare the query to get the value from the database
        $sql = "SELECT enddate FROM {course}
                 WHERE  id = :courseid";

        // Executing the query
        $courseenddate = $DB->get_record_sql($sql, array('courseid' => $this->get_course()->id));

        // Verifying if the value has returned
        if ($courseenddate && !empty($courseenddate->enddate)) {
            $date = $courseenddate->enddate;
            // Converting and returning the date in the correct format
            return userdate($date, $format);
        }else {
            $date = " ";
            return $date;
        }

    }

    // Auto link filter puts links in the certificate text,
    // and it's must be removed. See #111.
    protected function remove_links($htmltext) {
        global $CFG;
        require_once($CFG->libdir.'/htmlpurifier/HTMLPurifier.safe-includes.php');
        require_once($CFG->libdir.'/htmlpurifier/locallib.php');

        // This code is in weblib.php (purify_html function).
        $config = HTMLPurifier_Config::createDefault();
        $version = empty($CFG->version) ? 0 : $CFG->version;
        $cachedir = "$CFG->localcachedir/htmlpurifier/$version";
        $version = empty($CFG->version) ? 0 : $CFG->version;
        $cachedir = "$CFG->localcachedir/htmlpurifier/$version";
        if (!file_exists($cachedir)) {
            // Purging of caches may remove the cache dir at any time,
            // luckily file_exists() results should be cached for all existing directories.
            $purifiers = array();
            $caches = array();
            gc_collect_cycles();

            make_localcache_directory('htmlpurifier', false);
            check_dir_exists($cachedir);
        }
        $config->set('Cache.SerializerPath', $cachedir);
        $config->set('Cache.SerializerPermissions', $CFG->directorypermissions);
        $config->set('HTML.ForbiddenElements', array('script', 'style', 'applet', 'a'));
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($htmltext);
    }

    protected function remove_user_image($userid) {
        $filename = 'f1-' . $userid;

        $fileinfo = self::get_certificate_tmp_fileinfo($this->get_context());
        $fs = get_file_storage();

        if ($file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $filename)) {
            // Got it,  now remove it.
            $file->delete();
        }
    }

    /**
     * Return user profile image URL
     */
    protected function get_user_image_url($user) {
        global $CFG;

        // Beacuse bug #141 forceloginforprofileimage=enabled
        // i must check if this contiguration is enalbe and by pass it.
        $path = '/';
        $filename = 'f1';
        $usercontext = context_user::instance($user->id, IGNORE_MISSING);
        if (empty($CFG->forceloginforprofileimage)) {
            // Not enable so it's very easy.
            $url = moodle_url::make_pluginfile_url($usercontext->id, 'user', 'icon', null, $path, $filename);
            $url->param('rev', $user->picture);
        } else {

            // It's enable, so i must copy the profile image to somewhere else, so i can get the image;
            // Try to get the profile image file.
            $fs = get_file_storage();
            $file = $fs->get_file($usercontext->id, 'user', 'icon', 0, '/', $filename . '.png');

            if (!$file) {
                $file = $fs->get_file($usercontext->id, 'user', 'icon', 0, '/', $filename . '.jpg');
                if (!$file) {
                    // I Can't get the file, sorry.
                    return '';
                }
            }

            // With the file, now let's copy to plugin filearea.
            $fileinfo = self::get_certificate_tmp_fileinfo($this->get_context()->id);

            // Since f1 is the same name for all user, i must to rename the file, i think
            // add userid, since it's unique.
            $fileinfo['filename'] = 'f1-' . $user->id;

            // I must verify if image is already copied, or i get an error.
            // This file will be removed  as soon as certificate file is generated.
            if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
                // File don't exists yet, so, copy to tmp file area.
                $fs->create_file_from_storedfile($fileinfo, $file);
            }

            // Now creating the image URL.
            $url = moodle_url::make_pluginfile_url($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                    null, $fileinfo['filepath'], $fileinfo['filename']);
        }
        return '<img src="' . $url->out() . '"  width="100" height="100" />';
    }

    /**
     * Returns the date to display for the certificate.
     *
     * @param stdClass $issuecert The issue certificate object
     * @param int $userid
     * @return string the date
     */
    protected function get_date(stdClass $issuecert, $userid) {
        global $DB;

        // Get date format.
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        } else {
            $format = $this->get_instance()->certdatefmt;

        }

        if ($this->get_instance()->certdate > 0
            && $modinfo = $this->get_mod_grade($this->get_instance()->certdate, $issuecert->userid)) {
                $moduleid = $this->get_instance()->certdate;
                $sql = "SELECT * FROM {course_modules_completion} WHERE coursemoduleid = :moduleid AND userid = :userid";
                $activitycompletiontime = $DB->get_record_sql($sql, array('moduleid'=> $moduleid, 'userid' => $userid), IGNORE_MISSING);
                if (!empty($activitycompletiontime)) {
                    $date = $activitycompletiontime->timemodified;
                }else {
                    $date = ' ';
                }
            }

            if (empty($date)) {
                return $date = " ";

            }else {
                if ($date == ' ') {
                    return $date = '';
                }else {

                    return userdate($date, $format);
                }
            }

    }

    /**
     *  Return all actitvity grades, in the format:
     *  Grade Item Name: grade<br>
     *
     * @param int $userid the user id, if none are supplied, gets $USER->id
     */
    protected function get_activity_grades($userid = null) {
        global $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $items = grade_item::fetch_all(array('courseid' => $this->course->id));
        if (empty($items)) {
            return '';
        }

        // Sorting grade items by sortorder.
        usort($items, function($a, $b) {
            $asortorder = $a->sortorder;
            $bsortorder = $b->sortorder;
            if ($asortorder == $bsortorder) {
                return 0;
            }
            return ($asortorder < $bsortorder) ? -1 : 1;
        });

        $retval = '';
        foreach ($items as $id => $item) {
            // Do not include grades for course itens.
            if ($item->itemtype != 'mod') {
                continue;
            }
            $cm = get_coursemodule_from_instance($item->itemmodule, $item->iteminstance);
            $modinfo = $this->get_mod_grade($cm->id, $userid);
            if(!empty($modinfo) AND !$modinfo->hidden){
                $usergrade = $this->get_formated_grade($this->get_mod_grade($cm->id, $userid));
                $retval = $item->itemname . ": $usergrade<br>" . $retval;
            }
        }
        return $retval;
    }

    /**
     * Get the course outcomes for for mod_form print outcome.
     *
     * @return array
     */
    protected function get_outcomes() {
        global $COURSE;

        // Get all outcomes in course.
        $gradeseq = new grade_tree($COURSE->id, false, true, '', false);
        $gradeitems = $gradeseq->items;
        if ($gradeitems) {
            // List of item for menu.
            $printoutcome = array();
            foreach ($gradeitems as $gradeitem) {
                if (!empty($gradeitem->outcomeid)) {
                    $itemmodule = $gradeitem->itemmodule;
                    $printoutcome[$gradeitem->id] = $itemmodule . get_string('colon', 'customdocument') . $gradeitem->get_name();
                }
            }
        }
        if (!empty($printoutcome)) {
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
     * Returns the outcome to display on the certificate
     *
     * @return string the outcome
     */
    protected function get_outcome($userid) {
        global $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        if ($this->get_instance()->outcome > 0
            && $gradeitem = new grade_item(array('id' => $this->get_instance()->outcome))) {

            $outcomeinfo = new stdClass();
            $outcomeinfo->name = $gradeitem->get_name();
            $outcome = new grade_grade(array('itemid' => $gradeitem->id, 'userid' => $userid));
            $outcomeinfo->grade = grade_format_gradevalue($outcome->finalgrade, $gradeitem, true, GRADE_DISPLAY_TYPE_REAL);
            return $outcomeinfo->name . get_string('colon', 'customdocument') . $outcomeinfo->grade;
        }

        return '';
    }

    protected function create_temp_file($file) {
        global $CFG;

        $path = make_temp_directory(self::CERTIFICATE_COMPONENT_NAME);
        return tempnam($path, $file);
    }

    protected function get_user_profile_fields($userid) {
        global $CFG, $DB;

        $usercustomfields = new stdClass();
        $categories = $DB->get_records('user_info_category', null, 'sortorder ASC');
        if ($categories) {
            foreach ($categories as $category) {
                $fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC');
                if ($fields) {
                    foreach ($fields as $field) {
                        require_once($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
                        $newfield = 'profile_field_' . $field->datatype;
                        $formfield = new $newfield($field->id, $userid);
                        // if ($formfield->is_visible() && !$formfield->is_empty()) {
                        if (!$formfield->is_empty()) {
                            if ($field->datatype == 'checkbox') {
                                $usercustomfields->{$field->shortname} = (
                                    $formfield->data == 1 ? get_string('yes') : get_string('no')
                                );
                            } else {
                                $usercustomfields->{$field->shortname} = $formfield->display_data();
                            }
                        } else {
                            $usercustomfields->{$field->shortname} = '';
                        }
                    }
                }
            }
        }
        return $usercustomfields;
    }

    /**
     * Verify if user meet issue conditions
     *
     * @param int $userid User id
     * @return string null if user meet issued conditions, or an text with erro
     */
    protected function can_issue($user = null, $chkcompletation = true) {
        global $USER, $CFG;

        if (empty($user)) {
            $user = $USER;
        }

        if (has_capability('mod/customdocument:manage', $this->context, $user)) {
            return 'Manager user';
        }

        if ($chkcompletation) {
            $completion = new completion_info($this->course);
            if ($completion->is_enabled($this->coursemodule) && $this->get_instance()->requiredtime) {
                if ($this->get_course_time($user) < $this->get_instance()->requiredtime) {
                    $a = new stdClass();
                    $a->requiredtime = $this->get_instance()->requiredtime;
                    return get_string('requiredtimenotmet', 'customdocument', $a);
                }
                // Mark as complete.
                $completion->update_state($this->coursemodule, COMPLETION_COMPLETE, $user->id);

            }

            if ($CFG->enableavailability
                && !$this->check_user_can_access_certificate_instance($user->id)) {
                    return get_string('cantissue', 'customdocument');

            }
            return null;
        }
    }

    /**
     * get full user status of on certificate instance (if it can view/access)
     * this method helps the unit test (easy to mock)
     * @param int $userid
     */
    protected function check_user_can_access_certificate_instance($userid) {
        return info_module::is_user_visible($this->get_course_module(), $userid, false);
    }

    /**
     * Verify if cetificate file exists
     *
     * @param stdClass $issuecert Issued certificate object
     * @return true if exist
     */
    protected function issue_file_exists(stdClass $issuecert) {
        $fs = get_file_storage();

        // Check for file first.
        return $fs->file_exists_by_hash($issuecert->pathnamehash);
    }

    // View methods.
    protected function show_tabs(moodle_url $url) {
        global $OUTPUT, $CFG;

        $cm = $this->get_course_module();
        $context = context_module::instance ($cm->id);
        $canviewgeneratedoctab = has_capability('mod/customdocument:canviewgeneratedoctab', $context);
        $canviewissueddoctab = has_capability('mod/customdocument:canviewissueddoctab', $context);
        $canviewbulkdoctab = has_capability('mod/customdocument:canviewbulkdoctab', $context);

        if($canviewgeneratedoctab){
            $tabs[] = new tabobject(self::DEFAULT_VIEW, $url->out(false, array('tab' => self::DEFAULT_VIEW)),
                                    get_string('standardview', 'customdocument'));
        }

        if($canviewissueddoctab){
            $tabs[] = new tabobject(self::ISSUED_CERTIFCADES_VIEW, $url->out(false, array('tab' => self::ISSUED_CERTIFCADES_VIEW)),
                                    get_string('issuedview', 'customdocument'));
        }

        if($canviewbulkdoctab){
            $tabs[] = new tabobject(self::BULK_ISSUE_CERTIFCADES_VIEW,
                                    $url->out(false, array('tab' => self::BULK_ISSUE_CERTIFCADES_VIEW)),
                                    get_string('bulkview', 'customdocument'));
        }

        if (!$url->get_param('tab')) {
            $tab = self::DEFAULT_VIEW;
        } else {
            $tab = $url->get_param('tab');
        }

        echo $OUTPUT->tabtree($tabs, $tab);

    }

    // Default view.
    public function view_default(moodle_url $url, $canmanage) {
        global $CFG, $OUTPUT, $USER;

        if (!$url->get_param('action')) {

            echo $OUTPUT->header();

            if ($canmanage) {
                $this->show_tabs($url);
            }

            // Check if the user can view the certificate.
            $msg = $this->can_issue($USER);
            if (!$canmanage && $msg) {
                notice($msg, $CFG->wwwroot . '/course/view.php?id=' . $this->get_course()->id, $this->get_course());
                die();
            }

            // if (!empty($this->get_instance()->intro)) {
            //     echo $OUTPUT->box(format_module_intro('customdocument', $this->get_instance(), $this->coursemodule->id),
            //                     'generalbox', 'intro');
            // }

            $attempts = $this->get_attempts();
            if ($attempts) {
                echo $this->print_attempts($attempts);
            }

            if (!$canmanage) {
                $this->add_to_log('view');
            }

            if ($this->get_instance()->delivery != 3 || $canmanage) {
                // Create new certificate record, or return existing record.
                switch ($this->get_instance()->delivery) {
                    case self::OUTPUT_FORCE_DOWNLOAD:
                        $str = get_string('opendownload', 'customdocument');
                    break;

                    case self::OUTPUT_SEND_EMAIL:
                        $str = get_string('openemail', 'customdocument');
                    break;

                    default:
                        $str = get_string('openwindow', 'customdocument');
                    break;
                }

                echo html_writer::tag('p', $str, array('style' => 'text-align:center'));
                $linkname = get_string('getcertificate', 'customdocument');

                $link = new moodle_url('/mod/customdocument/view.php',
                                array('id' => $this->coursemodule->id, 'action' => 'get'));
                $button = new single_button($link, $linkname);

                $button->add_action(new popup_action('click', $link, 'view' . $this->coursemodule->id,
                                                    array('height' => 600, 'width' => 800)));

                echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));
            }
            echo $OUTPUT->footer();

        } else { // Output to pdf.
            if ($this->get_instance()->delivery != 3 || $canmanage) {
                $this->output_pdf($this->get_issue($USER));
            }
        }
    }

    protected function get_issued_certificate_users($sort = 'issuedate', $groupmode = 0) {
        global $CFG, $DB, $SESSION;

        if ($sort == 'username') {
            $sort = $DB->sql_fullname() . ' ASC, ci.timecreated DESC';
        } else if ($sort == 'issuedate') {
            $sort = 'ci.timecreated DESC';
        } else {
            $sort = '';
        }

        // Get all users that can manage this certificate to exclude them from the report.
        $certmanagers = get_users_by_capability($this->context, 'mod/customdocument:manage', 'u.id');

        $sql = "SELECT ci.code, ci.id AS ciid, ci.timecreated, ci.timedisabled, ci.haschange, ci.pathnamehash, u.id, u.firstname, u.lastname ,u.picture ,u.firstnamephonetic ,u.lastnamephonetic ,u.middlename ,u.alternatename ,u.imagealt ,u.email ";
        $sql .= "FROM {user} u INNER JOIN {customdocument_issues} ci ON u.id = ci.userid ";
        $sql .= "WHERE u.deleted = 0 AND ci.certificateid = :certificateid AND timedeleted IS NULL ";
        $sql .= "ORDER BY {$sort}";
        $issedusers = $DB->get_records_sql($sql, array('certificateid' => $this->get_instance()->id));

        // Now exclude all the certmanagers.
        foreach ($issedusers as $id => $user) {
            $coursectx = $this->get_course_context();
            $studentroles = array_keys(get_archetype_roles('student'));
            $students = get_role_users($studentroles, $coursectx, false, 'u.id', null, true, '', '', '');
            $isstudent = !empty($students[$user->id]);

            if (!empty($certmanagers[$user->id]) && !$isstudent) { // Exclude certmanagers except if they are student.
                unset ($issedusers[$id]);
            }
        }

        // If groupmembersonly used, remove users who are not in any group.
        if (!empty($issedusers) && !empty($CFG->enablegroupings) && $this->coursemodule->groupmembersonly
            && $groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
            $issedusers = array_intersect($issedusers, array_keys($groupingusers));
        }
        if ($groupmode) {
            $currentgroup = groups_get_activity_group($this->coursemodule, true);
            if ($currentgroup) {
                $groupusers = groups_get_members($currentgroup, 'u.*');
                if (empty($groupusers)) {
                    return array();
                }
                foreach ($issedusers as $isseduser) {
                    if (empty($groupusers[$isseduser->id])) {
                        // Remove this user as it isn't in the group!
                        unset($issedusers[$isseduser->code]);
                    }
                }
            }
        }
        return $issedusers;
    }
    // theCodeman
    // Issued certificates view.
    public function view_issued_certificates(moodle_url $url, array $selectedusers = null) {
        global $OUTPUT, $CFG, $DB, $PAGE;

        // Declare some variables.
        $strdate = get_string('receptiondate', 'customdocument');
        $strgrade = get_string('grade', 'customdocument');
        $strcode = get_string('code', 'customdocument');
        $strreport = get_string('report', 'customdocument');
        $groupmode = groups_get_activity_groupmode($this->get_course_module());
        $page = $url->get_param('page');
        $perpage = $url->get_param('perpage');
        $orderby = $url->get_param('orderby');
        $action = $url->get_param('action');
        $usercount = 0;

        if (!$selectedusers) {
            $users = $this->get_issued_certificate_users($orderby, $groupmode);
            $usercount = count($users);
        } else {
            list($sqlissueids, $params) = $DB->get_in_or_equal($selectedusers);
            $sql = "SELECT * FROM {customdocument_issues} WHERE id $sqlissueids";

            $issues = $DB->get_records_sql($sql, $params);
        }

        if (!$action) {
            echo $OUTPUT->header();
            $this->show_tabs($url);

            if ($groupmode) {
                groups_get_activity_group($this->coursemodule, true);
            }

            groups_print_activity_menu($this->coursemodule, $url);

            if (!$users) {
                echo ("<p style='text-align: center;'>" . get_string('notreceived', 'customdocument') ."</p>");
                $OUTPUT->notification(get_string('nocertificatesissued', 'customdocument'));
                echo $OUTPUT->footer();
                exit();
            }

            // Create the table for the users.
            $table = new html_table();
            $table->width = "95%";
            $table->tablealign = "center";

            $table->head = array(' ', get_string('fullname'), get_string('grade', 'customdocument'));
            $table->align = array("left", "left", "center");
            $table->size = array('1%', '89%', '10%');

            $table = new html_table();
            $table->id = "table-document-delivery";
            $table->width = "95%";
            $table->tablealign = "center";
            $selectAllchkBox = html_writer::checkbox('selectallusersdocuments', '',false);
            $table->head = array($selectAllchkBox, get_string('firstname', 'customdocument'), get_string('lastname', 'customdocument'),$strdate, $strgrade, $strcode);

            if($orderby == "issuedate"){
                $table->colclasses = array("", "", "", "th-sort-desc", "", "");
            }
            else{
                $table->colclasses = array("", "", "", "", "", "");
            }


            $table->align = array("left", "left", "left", "center", "center");
            $table->size = array('1%', '15%', '15%' ,'25%', '5%', '30%');

            $users = array_slice($users, intval($page * $perpage), $perpage);

            foreach ($users as $user) {
                // $usercert = $this->get_issue($user, false);
                $usercert = new stdClass();
                $usercert->timecreated = $user->timecreated;
                $usercert->timedisabled = $user->timedisabled;
                $usercert->pathnamehash = $user->pathnamehash;
                $usercert->code = $user->code;
                
                if(!empty($usercert)){
                    // $usercert->id = $user->ciid;
                    // $usercert->userid = $user->id;
                    // $usercert->code = $user->code;
                    // $usercert->timecreated = $user->timecreated;
                    // $usercert->timedisabled = $user->timedisabled;
                    // $usercert->haschange = $user->haschange;
                    // $usercert->pathnamehash = $user->pathnamehash;

                    $name = $OUTPUT->user_picture($user) . $user->firstname;
                    $lastname = $user->lastname;
                    $chkbox = html_writer::checkbox('selectedissues[]', $user->ciid, false);
                    if($user->timedisabled){
                        $date = '<span class="hidden">' . date("Y-m-d H:i:s", $usercert->timecreated) . '</span>' . userdate($usercert->timecreated) . get_string('expired', 'customdocument') . customdocument_print_issue_certificate_file($usercert);
                        $table->rowclasses[] = "disabled";
                        $grade = "";
                    }
                    else{
                        $date =  '<span class="hidden">' . date("Y-m-d H:i:s", $usercert->timecreated) . '</span>' . userdate($usercert->timecreated) . customdocument_print_issue_certificate_file($usercert);  
                        $table->rowclasses[] = "";
                        $grade = $this->get_grade($user->id);
                    }
                    
                    $code = $user->code;
                    $table->data[] = array($chkbox, $name, $lastname ,$date, $grade, $code);

                }
            }

            // Create table to store buttons.
            $cm = $this->get_course_module();
            $context = context_module::instance ($cm->id);
            $candeletedocument = has_capability('mod/customdocument:candeletedocument', $context);


            $tablebutton = new html_table();
            $tablebutton->attributes['class'] = 'downloadreport test';

            if($candeletedocument) {
                $deleteselectedbutton = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'delete', 'type' => 'selected')),
                            get_string('deleteselected', 'customdocument'));
                $deleteallbutton = $OUTPUT->single_button(
                                $url->out_as_local_url(false, array('action' => 'delete', 'type' => 'all')),
                                get_string('deleteall', 'customdocument'));
            }
            $btndownloadods = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'download', 'type' => 'ods')),
                            get_string("downloadods"));
            $btndownloadxls = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'download', 'type' => 'xls')),
                            get_string("downloadexcel"));
            $btndownloadtxt = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'download', 'type' => 'txt')),
                            get_string("downloadtext"));
            if($candeletedocument) {
                $tablebutton->data[] = array($deleteselectedbutton,
                    $deleteallbutton,
                    $btndownloadods,
                    $btndownloadxls,
                    $btndownloadtxt
                );
            }
            else {
                $tablebutton->data[] = array($btndownloadods,
                    $btndownloadxls,
                    $btndownloadtxt
                );
            }

            echo '<br />';
            echo '<form id="bulkissue" name="bulkissue" method="post" action="view.php">';
            echo "";

            echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
            echo html_writer::table($table);

            echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
            echo html_writer::tag('div', html_writer::table($tablebutton), array('style' => 'margin:auto; width:50%'));
            echo '</form>';

        } else {
            $type = $url->get_param('type');
            $url->remove_params('action', 'type', 'selectedusers');
            // Override $users param if no user are selected, but clicks in delete selected.
            switch ($action) {
                case 'delete':
                    switch ($type) {
                        case  'all':
                            // Override $users param, if there is a selected users, but it clicks on delete all.
                            $issues = $users;
                            foreach ($issues as $issuedcert) {
                                $issuedcert->userid = $issuedcert->id;
                                $issuedcert->id = $issuedcert->ciid;
                            }
                        break;

                        case 'selected':
                            // No user selected, add an empty array to avoid errors.
                            if (!$selectedusers) {
                                $issues = array();
                            }
                        break;
                    }

                    foreach ($issues as $issuedcert) {
                        // If it's issued, then i remove.
                        if ($issuedcert) {
                            $this->remove_issue($issuedcert, false);
                        }
                    }
                break;

                case 'download':
                    $page = $perpage = 0;

                    // Override $users param, if there is a selected users.
                    $users = $this->get_issued_certificate_users($orderby, $groupmode);

                    // Calculate file name.
                    $filename = clean_filename($this->get_instance()->coursename . '-' .
                                     strip_tags(format_string($this->get_instance()->name, true)) . '.' .
                                     strip_tags(format_string($type, true)));

                    switch ($type) {
                        case 'ods':
                            require_once("$CFG->libdir/odslib.class.php");

                            // Creating a workbook.
                            $workbook = new MoodleODSWorkbook("-");
                            // Send HTTP headers.
                            $workbook->send(format_text($filename, true));
                            // Creating the first worksheet.
                            $myxls = $workbook->add_worksheet($strreport);

                            // Print names of all the fields.
                            $myxls->write_string(0, 0, get_string("fullname"));
                            $myxls->write_string(0, 1, get_string("idnumber"));
                            $myxls->write_string(0, 2, get_string("group"));
                            $myxls->write_string(0, 3, format_string($strdate));
                            $myxls->write_string(0, 4, $strgrade);
                            $myxls->write_string(0, 5, $strcode);

                            // Generate the data for the body of the spreadsheet.
                            $i = 0;
                            $row = 1;
                            if ($users) {
                                foreach ($users as $user) {
                                    $myxls->write_string($row, 0, fullname($user));
                                    $studentid = (!empty($user->idnumber)) ? $user->idnumber : " ";
                                    $myxls->write_string($row, 1, $studentid);
                                    $ug2 = '';
                                    $usergrps = groups_get_all_groups($this->get_course()->id, $user->id);
                                    if ($usergrps) {
                                        foreach ($usergrps as $ug) {
                                            $ug2 = $ug2 . $ug->name;
                                        }
                                    }
                                    $myxls->write_string($row, 2, $ug2);
                                    if($user->timedisabled){
                                        $myxls->write_string($row, 3, userdate($user->timecreated) . get_string('expiredtxt', 'customdocument'));
                                    }
                                    else{
                                        $myxls->write_string($row, 3, userdate($user->timecreated));
                                    }
                                    $myxls->write_string($row, 4, $this->get_grade($user->id));
                                    $myxls->write_string($row, 5, $user->code);
                                    $row++;
                                }
                                //$pos = 5;
                            }
                            // Close the workbook.
                            $workbook->close();
                        break;

                        case 'xls':
                            require_once("$CFG->libdir/excellib.class.php");

                            // Creating a workbook.
                            $workbook = new MoodleExcelWorkbook("-");
                            // Send HTTP headers.
                            $workbook->send($filename);
                            // Creating the first worksheet.
                            $myxls = $workbook->add_worksheet($strreport);

                            // Print names of all the fields.
                            $myxls->write_string(0, 0, get_string("fullname"));
                            $myxls->write_string(0, 1, get_string("idnumber"));
                            $myxls->write_string(0, 2, get_string("group"));
                            $myxls->write_string(0, 3, format_string($strdate));
                            $myxls->write_string(0, 4, $strgrade);
                            $myxls->write_string(0, 5, $strcode);

                            // Generate the data for the body of the spreadsheet.
                            $i = 0;
                            $row = 1;
                            if ($users) {
                                foreach ($users as $user) {
                                    $myxls->write_string($row, 0, fullname($user));
                                    $studentid = (!empty($user->idnumber)) ? $user->idnumber : " ";
                                    $myxls->write_string($row, 1, $studentid);
                                    $ug2 = '';
                                    $usergrps = groups_get_all_groups($this->get_course()->id, $user->id);
                                    if ($usergrps) {
                                        foreach ($usergrps as $ug) {
                                            $ug2 = $ug2 . $ug->name;
                                        }
                                    }
                                    $myxls->write_string($row, 2, $ug2);
                                    if($user->timedisabled){
                                        $myxls->write_string($row, 3, userdate($user->timecreated) . get_string('expiredtxt', 'customdocument'));
                                    }
                                    else{
                                        $myxls->write_string($row, 3, userdate($user->timecreated));
                                    }
                                    $myxls->write_string($row, 4, $this->get_grade($user->id));
                                    $myxls->write_string($row, 5, $user->code);
                                    $row++;
                                }
                                $pos = 5;
                            }
                            // Close the workbook.
                            $workbook->close();
                        break;

                        // ...txt.
                        default:

                            header("Content-Type: application/download\n");
                            header("Content-Disposition: attachment; filename=\"" . format_text($filename, true) . "\"");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
                            header("Pragma: public");

                            // Print names of all the fields.
                            echo get_string("fullname") . "\t" . get_string("idnumber") . "\t";
                            echo get_string("group") . "\t";
                            echo format_string($strdate) . "\t";
                            echo $strgrade . "\t";
                            echo $strcode . "\n";

                            // Generate the data for the body of the spreadsheet.
                            $i = 0;
                            $row = 1;
                            if ($users) {
                                foreach ($users as $user) {
                                    echo fullname($user);
                                    $studentid = " ";
                                    if (!empty($user->idnumber)) {
                                        $studentid = $user->idnumber;
                                    }
                                    echo "\t" . $studentid . "\t";
                                    $ug2 = '';
                                    $usergrps = groups_get_all_groups($this->get_course()->id, $user->id);
                                    if ($usergrps) {
                                        foreach ($usergrps as $ug) {
                                            $ug2 = $ug2 . $ug->name;
                                        }
                                    }
                                    echo $ug2 . "\t";
                                    if($user->timedisabled){
                                        echo userdate($user->timecreated) . get_string('expiredtxt', 'customdocument') . "\t";
                                    }
                                    else{
                                        echo userdate($user->timecreated) . "\t";
                                    }
                                    echo $this->get_grade($user->id) . "\t";
                                    echo $user->code . "\n";
                                    $row++;
                                }
                            }
                        break;
                    }
                    exit;
                break;
            }
            redirect($url);
        }
        echo $OUTPUT->footer();

    }

    public function view_bulk_certificates(moodle_url $url, array $selectedusers = null) {
        global $OUTPUT, $CFG, $DB;

        $coursectx = context_course::instance($this->get_course()->id);

        $page = $url->get_param('page');
        $perpage = $url->get_param('perpage');
        $issuelist = $url->get_param('issuelist');
        $action = $url->get_param('action');
        $type = $url->get_param('type');
        $groupid = 0;
        $groupmode = groups_get_activity_groupmode($this->coursemodule);
        if ($groupmode) {
            $groupid = groups_get_activity_group($this->coursemodule, true);
        }

        $pagestart = intval($page * $perpage);
        $usercount = 0;
        $users = array();

        if (!$selectedusers) {
            // Seuls les users ayant accès au certificat sont pris en compte
            // $enrolledusers = get_enrolled_users($coursectx, '', $groupid);
            $enrolledusers = get_enrolled_users($coursectx, '', $groupid, 'u.*', $DB->sql_fullname());
            foreach ($enrolledusers as $user) {
                $canissue = $this->can_issue($user, $issuelist != 'allusers');
                if (empty($canissue)) {
                    $users[$user->id] = $user;
                }
            }
            $usercount = count($users);

        } else {
            list($sqluserids, $params) = $DB->get_in_or_equal($selectedusers);
            $sql = "SELECT * FROM {user} WHERE id $sqluserids";
            // Adding sort.
            $sort = '';
            $override = new stdClass();
            $override->firstname = 'firstname';
            $override->lastname = 'lastname';
            $fullnamelanguage = get_string('fullnamedisplay', '', $override);
            if (($CFG->fullnamedisplay == 'firstname lastname') || ($CFG->fullnamedisplay == 'firstname') ||
             ($CFG->fullnamedisplay == 'language' && $fullnamelanguage == 'firstname lastname')) {
                $sort = " ORDER BY firstname, lastname";
            } else {
                $sort = " ORDER BY lastname, firstname";
            }
            $users = $DB->get_records_sql($sql . $sort, $params);
        }

        if (!$action) {
            echo $OUTPUT->header();
            $this->show_tabs($url);

            groups_print_activity_menu($this->coursemodule, $url);

            // Checking that group mode is active or not if yes it will add a line break so the text dont overlap
            if ($groupmode) {
                echo "<br>";
            }
            // Add to  values to constants.
            $selectoptions = array('completed' => get_string('completedusers', 'customdocument'),
                    'allusers' => get_string('allusers', 'customdocument'));

            $selected = $selectoptions['completed'];
            $select = new single_select($url, 'issuelist', $selectoptions, $selected ,$issuelist);
            $select->label = get_string('showusers', 'customdocument');
            echo $OUTPUT->render($select);
            echo '<br>';
            echo '<form id="bulkissue" name="bulkissue" method="post" action="view.php">';

            echo html_writer::label(get_string('bulkaction', 'customdocument'), 'menutype', true);
            echo '&nbsp;';
            $selectoptions = array('pdf' => get_string('onepdf', 'customdocument'),
            'zip' => get_string('multipdf', 'customdocument'),
            'email' => get_string('sendtoemail', 'customdocument'));
            echo html_writer::select($selectoptions, 'type', $type);
            $table = new html_table();
            $table->id = "table-document-delivery";
            $table->width = "95%";
            $table->tablealign = "center";
            $selectAllchkBox = html_writer::checkbox('selectallusersdocuments', '',false);
            $table->head = array($selectAllchkBox, get_string('firstname', 'customdocument'), get_string('lastname', 'customdocument'), get_string('grade', 'customdocument'));
            $table->align = array("left", "left", "left", "center");
            $table->size = array('1%', '10%','10%','45%');

            // BUG #157, the paging is afecting download files,
            // so only apply paging when displaying users.
            $users = array_slice($users, $pagestart, $perpage);

            foreach ($users as $user) {
                $canissue = $this->can_issue($user, $issuelist != 'allusers');
                if (empty($canissue)) {
                    $chkbox = html_writer::checkbox('selectedusers[]', $user->id, false);
                    $name = $OUTPUT->user_picture($user) . $user->firstname;
                    $lastname = $user->lastname;
                    $table->data[] = array($chkbox, $name, $lastname, $this->get_grade($user->id));
                }
            }

            $downloadbutton = $OUTPUT->single_button($url->out_as_local_url(false, array('action' => 'download')),
                                                    get_string('bulkbuttonlabel', 'customdocument'));

            echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
            echo '<br />';
            echo html_writer::table($table);
            echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
            echo html_writer::tag('div', $downloadbutton, array('style' => 'text-align: center'));
            echo html_writer::tag('div', get_string('bulkwarning', 'customdocument'), array('style' => "text-align: center"));
            echo '</form>';

        } else if ($action == 'download') {
            $type = $url->get_param('type');

            // Calculate file name.
            $shortname = substr($this->get_course()->shortname, 0, 20);
            $certname = substr(format_string($this->get_instance()->name, true), 0, 20);
            $filename = str_replace(' ', '_',
                            clean_filename(
                                $shortname . '-' .
                                strip_tags($certname) . '.' .
                                strip_tags(format_string($type, true))));

            switch ($type) {

                // One zip with all certificates in separated files.
                case 'zip':
                    $filesforzipping = array();
                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            $issuedcert = $this->get_issue($user);
                            $file = $this->get_issue_file($issuedcert);
                            if ($file) {
                                $fileforzipname = $file->get_filename();
                                $filesforzipping[$fileforzipname] = $file;
                            } else {
                                print_error(get_string('filenotfound', 'customdocument'));
                            }
                        }
                    }

                    $tempzip = $this->create_temp_file('issuedcertificate_');

                    // Zipping files.
                    $zipper = new zip_packer();
                    if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
                        // Send file and delete after sending.
                        send_temp_file($tempzip, $filename);
                    }
                 break;

                case 'email':
                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            $issuedcert = $this->get_issue($user);
                            if ($this->get_issue_file($issuedcert)) {
                                $this->send_certificade_email($issuedcert);
                            } else {
                                print_error('filenotfound', 'customdocument');
                            }
                        }
                    }
                    $url->remove_params('action', 'type');
                    redirect($url, get_string('emailsent', 'customdocument'), 5);
                 break;

                // One pdf with all certificates.
                default:
                    $pdf = $this->create_pdf_object();

                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            // To one pdf file.
                            $issuedcert = $this->get_issue($user);
                            $this->create_pdf($issuedcert, $pdf, true);

                            // Save certificate PDF.
                            if (!$this->issue_file_exists($issuedcert)) {
                                // To force file creation.
                                $issuedcert->haschange = true;
                                $this->get_issue_file($issuedcert);
                            }
                        }
                    }
                    // $this->output_pdf($issuedcert);
                    $pdf->Output($filename, 'D');

                    break;
            }
            exit();
        }
        echo $OUTPUT->footer();

    }

        
    /**
     * Util function to loggin
     *
     * @param string $action Log action
     */
    private function add_to_log($action) {
        if ($action) {
            $event = \mod_customdocument\event\course_module_viewed::create(
                array(
                    'objectid' => $this->get_course_module()->instance,
                    'context' => $this->get_context(),
                    'other' => array('certificatecode' => $this->get_issue()->code)));
                    $event->add_record_snapshot('course', $this->get_course());
        }
                
        if (!empty($event)) {
            $event->trigger();
        }
    }
                
}