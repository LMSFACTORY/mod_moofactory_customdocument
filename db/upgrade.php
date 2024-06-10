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

// This file keeps track of upgrades to
// the certificate module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php.

/**
 * @package     mod_customdocument
 * @copyright   2024 Patrick ROCHET <patrick.r@lmsfactory.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();


function xmldb_customdocument_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2013053102) {

        $table = new xmldb_table('customdocument');
        $field = new xmldb_field('disablecode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'requiredtime');

        // Conditionally launch add field disablecode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('codex', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '10', 'disablecode');

        // Conditionally launch add field codex.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('codey', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '10', 'codex');

        // Conditionally launch add field codey.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('enablesecondpage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'codey');

        // Conditionally launch add field enablesecondpage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('secondpagex', XMLDB_TYPE_INTEGER, '4', null, null, null, '10', 'enablesecondpage');

        // Conditionally launch add field secondpagex.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('secondpagey', XMLDB_TYPE_INTEGER, '4', null, null, null, '50', 'secondpagex');

        // Conditionally launch add field secondpagey.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('secondpagetext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'secondpagey');

        // Conditionally launch add field secondpagetext.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('secondpagetextformat', XMLDB_TYPE_TEXT, null, null, null, null, null, 'secondpagetext');

        // Conditionally launch add field secondpagetextformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('secondimage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'secondpagetextformat');

        // Conditionally launch add field secondimage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changing type of field certdatefmt on table customdocument to char.
        $field = new xmldb_field('certdatefmt', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'certdate');

        // Launch change of type for field certdatefmt.
        $dbman->change_field_type($table, $field);

        // Updating old values (thanks hqhoang for reporting and fix it).
        $sql = 'UPDATE {customdocument} SET certdatefmt = :dateformat WHERE certdatefmt = :old_1';
        $DB->execute($sql, array('dateformat' => '%B %d, %Y', 'old_1' => '1'));

        $sql = 'UPDATE {customdocument} SET certdatefmt = :dateformat WHERE certdatefmt = :old_2';
        $DB->execute($sql, array('dateformat' => 'F jS, Y', 'old_2' => '2'));

        $sql = 'UPDATE {customdocument} SET certdatefmt = :dateformat WHERE certdatefmt = :old_3';
        $DB->execute($sql, array('dateformat' => '%d %B %Y', 'old_3' => '3'));

        $sql = 'UPDATE {customdocument} SET certdatefmt = :dateformat WHERE certdatefmt = :old_4';
        $DB->execute($sql, array('dateformat' => '%B %Y', 'old_4' => '4'));

        $sql = 'UPDATE {customdocument} SET certdatefmt = \'\' WHERE certdatefmt = :old_5 OR certdatefmt = :old_6';
        $DB->execute($sql, array('old_5' => '5', 'old_6' => '6'));

        // Customdocument savepoint reached.
        upgrade_mod_savepoint(true, 2013053102, 'customdocument');
    }

    if ($oldversion < 2013092000) {

        // Changing nullability of field certificateimage on table customdocument to null.
        $table = new xmldb_table('customdocument');
        $field = new xmldb_field('certificateimage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'height');

        // Launch change of type for field certificateimage.
        $dbman->change_field_type($table, $field);

        // Launch rename field disablecode->printqrcode.

        $field = new xmldb_field('disablecode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'requiredtime');

        if ($dbman->field_exists($table, $field)) {
            $objs = $DB->get_records('customdocument', array("disablecode" => 0), '', 'id');
            $ids = '';

            foreach ($objs as $obj) {
                $ids = $ids . $obj->id . ',';
            }
            if (!empty($ids)) {
                $ids = chop($ids, ',');

                $sql = 'UPDATE {customdocument} SET disablecode = 1 WHERE id in (' . $ids . ')';
                $DB->execute($sql);

                $sql = 'UPDATE {customdocument} SET disablecode = 0 WHERE id not in (' . $ids . ')';
                $DB->execute($sql);
            }

            // Launch change of default for field.
            $dbman->change_field_default($table, $field);
            // Launch rename field printqrcode.
            $dbman->rename_field($table, $field, 'printqrcode');
        } else {
            $field = new xmldb_field('printqrcode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'requiredtime');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $field = new xmldb_field('qrcodefirstpage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'printqrcode');

        // Conditionally launch add field qrcodefirstpage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('savecert');

        // Conditionally launch drop field savecert.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('customdocument_issues');
        $field = new xmldb_field('certificatename', XMLDB_TYPE_TEXT, null, null, null, null, null, 'userid');

        // Conditionally launch add field certificatename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('username');

        // Conditionally launch drop field username.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('coursename');

        // Conditionally launch drop field coursename.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Populating certificatename.
        $certs = $DB->get_records('customdocument');
        foreach ($certs as $cert) {
            $DB->execute('UPDATE {customdocument_issues} SET certificatename = ? WHERE certificateid = ?',
                        array($cert->name, $cert->id));
        }

        // Customdocument savepoint reached.
        upgrade_mod_savepoint(true, 2013092000, 'customdocument');
    }

    if ($oldversion < 2013111900) {

        // Certdate update.
        $objs = $DB->get_records('customdocument', array("certdate" => 1), '', 'id');
        $objs = $objs + $DB->get_records('customdocument', array("certdate" => 2), '', 'id');
        $ids = '';

        foreach ($objs as $obj) {
            $ids = $ids . $obj->id . ',';
        }
        if (!empty($ids)) {
            $ids = chop($ids, ',');
            $sql = 'UPDATE {customdocument} SET certdate = -1 * certdate where id in (' . $ids . ')';
            $DB->execute($sql);
        }

        // Certgrade update.
        $objs = $DB->get_records('customdocument', array("certgrade" => 1), '', 'id');
        $ids = '';

        foreach ($objs as $obj) {
            $ids = $ids . $obj->id . ',';
        }
        if (!empty($ids)) {
            $ids = chop($ids, ',');
            $sql = 'UPDATE {customdocument} SET certdate = -1 * certgrade where id in (' . $ids . ')';
            $DB->execute($sql);
        }

        // Customdocument savepoint reached.
        upgrade_mod_savepoint(true, 2013111900, 'customdocument');
    }
    if ($oldversion < 2013112500) {
        // Changing the default of field certdate on table customdocument to -2.
        $table = new xmldb_table('customdocument');
        $field = new xmldb_field('certdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '-2', 'outcome');

        // Launch change of default for field certdate.
        $dbman->change_field_default($table, $field);

        $field = new xmldb_field('emailothers', XMLDB_TYPE_TEXT, null, null, null, null, null, 'emailfrom');

        // Launch change of nullability for field emailothers.
        $dbman->change_field_notnull($table, $field);

        // Customdocument savepoint reached.
        upgrade_mod_savepoint(true, 2013112500, 'customdocument');
    }

    if ($oldversion < 2013112901) {

        // Define field coursename to be added to customdocument_issues.
        $table = new xmldb_table('customdocument_issues');
        $field = new xmldb_field('coursename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'timedeleted');

        // Conditionally launch add field coursename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $sql = 'UPDATE {customdocument_issues} set coursename = (select fullname from {course} ';
        $sql .= 'where id = (select course from {customdocument} where id = certificateid)) where timedeleted is null';
        $DB->execute($sql);

        // Customdocument savepoint reached.
        $field = new xmldb_field('haschange', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'coursename');

        // Conditionally launch add field haschange.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $sql = 'UPDATE {customdocument_issues} SET haschange = 1';
        $DB->execute($sql);

        // Customdocument savepoint reached.
        upgrade_mod_savepoint(true, 2013112901, 'customdocument');
    }
    // ...v2.1.3.
    if ($oldversion < 2014051000) {

        // Define field timestartdatefmt to be added to customdocument.
        $table = new xmldb_table('customdocument');
        $field = new xmldb_field('timestartdatefmt', XMLDB_TYPE_CHAR, '255', null, null, null, '', 'secondimage');

        // Conditionally launch add field timestartdatefmt.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('customdocument_issues');

        $field = new xmldb_field('haschange', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timedeleted');

        // Conditionally launch add field haschange.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pathnamehash', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'haschange');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Must move files to new area and add the certificate files hashs.
        $issuedcerts = $DB->get_records('customdocument_issues');
        $countcerts = count($issuedcerts);

        $fs = get_file_storage();

        $pbar = new progress_bar('customdocumentmoveissuedfiles', 500, true);
        $i = 0;
        foreach ($issuedcerts as $issued) {
            $i++;
            try {
                $courseid = $DB->get_field('customdocument', 'course', array('id' => $issued->certificateid), MUST_EXIST);
                $cm = get_coursemodule_from_instance('customdocument', $issued->certificateid, $courseid, false, MUST_EXIST);
                $context = context_module::instance($cm->id);

                $user = $DB->get_record("user", array('id' => $issued->userid));
                if ($user) {
                    $filename = str_replace(' ', '_',
                                            clean_filename(
                                               $issued->certificatename . ' ' . fullname($user) . ' ' . $issued->id . '.pdf'));
                } else {
                    $filename = str_replace(' ', '_', clean_filename($issued->certificatename . ' ' . $issued->id . '.pdf'));
                }

                $fileinfo = array('contextid' => $context->id, 'component' => 'mod_customdocument', 'filearea' => 'issues',
                    'itemid' => $issued->id, 'filepath' => '/', 'filename' => $filename);

                if ($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'],
                                    $fileinfo['filepath'], $fileinfo['filename'])) {

                    $file = $fs->get_file(
                        $fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                        $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']
                    );

                    $fileinfo['filename'] = str_replace(
                        ' ', '_', clean_filename($issued->certificatename . ' ' . $issued->id . '.pdf')
                    );

                    $newfile = $fs->create_file_from_storedfile($fileinfo, $file);
                    if ($newfile) {
                        $file->delete();
                        $issued->pathnamehash = $newfile->get_pathnamehash();
                    }
                } else {
                    throw new moodle_exception('filenotfound', 'customdocument', null, null, '');
                }
            } catch (Exception $e) {
                if (empty($issued->timedeleted)) {
                    $issued->haschange = 1;
                }
                $issued->pathnamehash = '';
            }
            $pbar->update($i, $countcerts, "Moving Issued certificate files  ($i/$countcerts)");
            if (!$DB->update_record('customdocument_issues', $issued)) {
                print_error('upgradeerror', 'customdocument', null, "Can't update an issued certificate [id->$issued->id]");
            }
        }

        $field = new xmldb_field('pathnamehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'haschange');

        // Launch change of nullability for field pathnamehash.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('coursename');

        // Conditionally launch drop field coursename.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Customdocument savepoint reached.
        upgrade_mod_savepoint(true, 2014051000, 'customdocument');
    }

    // ... v2.2.4.
    if ($oldversion < 2017013001) {

        // Define coursename in customdocument_issues table.
        $table = new xmldb_table('customdocument_issues');

        // ...<FIELD NAME="coursename" TYPE="char" LENGTH="255" NOTNULL="true" SEQUENCE="false" PREVIOUS="pathnamehash" />.
        $field = new xmldb_field('coursename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '---', 'pathnamehash');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // Must add course name in new column.
            $issuedcerts = $DB->get_records('customdocument_issues');
            $countcerts = count($issuedcerts);
            $count = 0;
            $pbar = new progress_bar('customdocumentupdate', 500, true);
            foreach ($issuedcerts as $issued) {
                $coursename = $DB->get_field('customdocument', 'coursename', array('id' => $issued->certificateid));
                if (!$coursename) {
                    try {
                        $courseid = $DB->get_field(
                            'customdocument', 'course', array('id' => $issued->certificateid), MUST_EXIST
                        );
                        $coursename = $DB->get_field('course', 'fullname', array('id' => $courseid), MUST_EXIST);
                    } catch (Exception $e) {
                        if (empty($issued->timedeleted)) {
                            $issued->haschange = 1;
                        }
                        $coursename = '';
                    }
                }
                $issued->coursename = $coursename;
                if (!$DB->update_record('customdocument_issues', $issued)) {
                    print_error('upgradeerror', 'customdocument', null, "Can't update an issued certificate [id->$issued->id]");
                }
                $count++;
                $pbar->update($count, $countcerts, "Moving Issued certificate files  ($i/$countcerts)");
            }
        }
        // Customdocument savepoint reached.
        upgrade_mod_savepoint(true, 2017013001, 'customdocument');
    }

    if ($oldversion < 2023032202) {
        $table = new xmldb_table('customdocument');

        $field = new xmldb_field('validity', XMLDB_TYPE_INTEGER, '4', null, null, null, '12', 'timestartdatefmt');

        // Conditionally launch add field validity.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('renewalperiod', XMLDB_TYPE_INTEGER, '4', null, null, null, '2', 'validity');

        // Conditionally launch add field renewalperiod.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('resetall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'renewalperiod');

        // Conditionally launch add field resetall.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $table = new xmldb_table('customdocument_issues');

        $field = new xmldb_field('timedisabled', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timedeleted');

        // Conditionally launch add field validity.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('courseversion', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'coursename');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Creation of the course custom fields in the 'Version' category.
        $categoryid = $DB->get_field('customfield_category', 'id', array('name' => get_string('version_category', 'customdocument')));

        if(empty($categoryid)){
            $handler = core_course\customfield\course_handler::create();
            $categoryid = $handler->create_category(get_string('version_category', 'customdocument'));
        }

        if ($categoryid) {
            $category = \core_customfield\category_controller::create($categoryid);

            // 'Current version' field.
            $id = $DB->get_field('customfield_field', 'id', array('shortname' => 'courseversion'));
            if(empty($id)){
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
        }

        // Customdocument savepoint reached.
        upgrade_mod_savepoint(true, 2023032202, 'customdocument');
    }

    if ($oldversion < 2024042600) {
        $issues = $DB->get_records('customdocument_issues');
        foreach ($issues as $issue) {
            $customdocument = $DB->get_record('customdocument', ['id'=>$issue->certificateid]);
            if(!empty($customdocument)){
                $course = get_course($customdocument->course);
            
                $coureseshortname = str_replace(' ', '_', substr($course->shortname, 0, 20));
                $certname = str_replace(' ', '_', substr($customdocument->name, 0, 20));
            
                $user = get_complete_user_data('id', $issue->userid);
                $userfirstname = str_replace(' ', '_', substr($user->firstname, 0, 10));
                $userlastname = str_replace(' ', '_', substr($user->lastname, 0, 10));
            
                $filename = $coureseshortname.'-'.$certname.'-'.$userfirstname.'_'.$userlastname.'-'.$issue->id.'.pdf';
            
                $fs = get_file_storage();
                if(!empty($issue->pathnamehash)){
                    $file = $fs->get_file_by_hash($issue->pathnamehash);
                    if(!empty($file)){
                        $currentfilename = $file->get_filename();
                
                        if($filename != $currentfilename){
                            $file->rename($file->get_filepath(), $filename);
            
                            $data = new stdClass();
                            $data->id = $issue->id;
                            $data->pathnamehash = $file->get_pathnamehash();
                            $DB->update_record('customdocument_issues', $data);
                        }
                    }
                }
            }
        }
    }

    return true;
}