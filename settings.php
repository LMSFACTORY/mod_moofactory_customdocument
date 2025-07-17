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
 * Provides some custom settings for the certificate module
 *
 * @package    mod
 * @subpackage customdocument
 * @copyright  Carlos Alexandre S. da Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * @package     mod_customdocument
 * @copyright   2024 Patrick ROCHET <patrick.r@lmsfactory.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->dirroot/mod/customdocument/lib.php");

    // Pour verifier que la Format de course Moo Factory est installé.

    if (!array_key_exists('moofactory', \core_component::get_plugin_list('format'))) {
        $status_String = get_string('formatwarning', 'customdocument');
        $settings->add(new admin_setting_heading('StatusFormatMooFacTory', "<h3 class='alert alert-warning text-center'>{$status_String}</h3>" , ''));
    }else{
        $status_String = get_string('formatfound', 'customdocument');
        $settings->add(new admin_setting_heading('StatusFormatMooFacToryinstalled', "<p class='h6 alert alert-info text-center'>{$status_String}</p>" , ''));
    }

    // General settings.
    $settings->add(new admin_setting_configtext('customdocument/width', get_string('defaultwidth', 'customdocument'),
        get_string('size_help', 'customdocument'), 210, PARAM_INT));
    $settings->add(new admin_setting_configtext('customdocument/height', get_string('defaultheight', 'customdocument'),
        get_string('size_help', 'customdocument'), 297, PARAM_INT));

    $settings->add(new admin_setting_configtext('customdocument/certificatetextx',
                    get_string('defaultcertificatetextx', 'customdocument'),
        get_string('textposition_help', 'customdocument'), 5, PARAM_INT));
    $settings->add(new admin_setting_configtext('customdocument/certificatetexty',
                    get_string('defaultcertificatetexty', 'customdocument'),
        get_string('textposition_help', 'customdocument'), 5, PARAM_INT));

    // $settings->add(new admin_setting_configselect('customdocument/certdate', get_string('printdate', 'customdocument'),
    //     get_string('printdate_help', 'customdocument'), -2, customdocument_get_date_options()));


    $settings->add(new admin_setting_configtext('customdocument/certlifetime', get_string('certlifetime', 'customdocument'),
        get_string('certlifetime_help', 'customdocument'), 120, PARAM_INT));

    // QR CODE.
    $settings->add(new admin_setting_configcheckbox('customdocument/printqrcode',
        get_string('printqrcode', 'customdocument'), get_string('printqrcode_help', 'customdocument'), 0));
    $settings->add(new admin_setting_configtext('customdocument/codex', get_string('defaultcodex', 'customdocument'),
        get_string('qrcodeposition_help', 'customdocument'), 10, PARAM_INT));
    $settings->add(new admin_setting_configtext('customdocument/codey', get_string('defaultcodey', 'customdocument'),
        get_string('qrcodeposition_help', 'customdocument'), 10, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('customdocument/qrcodefirstpage',
            get_string('qrcodefirstpage', 'customdocument'), get_string('qrcodefirstpage_help', 'customdocument'), 0));

    // Certificate second page.
    $settings->add(new admin_setting_configcheckbox('customdocument/enablesecondpage',
            get_string('enablesecondpage', 'customdocument'), get_string('enablesecondpage_help', 'customdocument'), 0));

    // Certificate third page.
    $settings->add(new admin_setting_configcheckbox('customdocument/enablethirdpage',
            get_string('enablethirdpage', 'customdocument'), get_string('enablethirdpage_help', 'customdocument'), 0));
    $settings->hide_if('customdocument/enablethirdpage', 'customdocument/enablesecondpage', 'neq', '1');

    // Certificate fourth page.
    $settings->add(new admin_setting_configcheckbox('customdocument/enablefourthpage',
            get_string('enablefourthpage', 'customdocument'), get_string('enablefourthpage_help', 'customdocument'), 0));
    $settings->hide_if('customdocument/enablefourthpage', 'customdocument/enablesecondpage', 'neq', '1');
    $settings->hide_if('customdocument/enablefourthpage', 'customdocument/enablethirdpage', 'neq', '1');

    // Pagination.
    $settings->add(new admin_setting_configtext('customdocument/perpage', get_string('defaultperpage', 'customdocument'),
            get_string('defaultperpage_help', 'customdocument'), 30, PARAM_INT));

    if(array_key_exists('moofactory_resetmod', \core_component::get_plugin_list('local'))){
        // Validity.
        $settings->add(new admin_setting_configtext('customdocument/validity', get_string('defaultvalidity', 'customdocument'),
                get_string('validity_help', 'customdocument'), 0, PARAM_INT));
        $settings->add(new admin_setting_configtext('customdocument/renewalperiod', get_string('defaultrenewalperiod', 'customdocument'),
                get_string('renewalperiod_help', 'customdocument'), 0, PARAM_INT));
        $settings->add(new admin_setting_configcheckbox('customdocument/resetall',
                get_string('resetall', 'customdocument'), get_string('resetall_help', 'customdocument'), 1));
        }


}