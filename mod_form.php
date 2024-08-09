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

 if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}


require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');


class mod_customdocument_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        // Pour verifier que la Format de course Moo Factory est installé.

    if (!array_key_exists('moofactory', \core_component::get_plugin_list('format'))) {
        $status_String = get_string('formatwarning', 'customdocument');
        $mform->addElement('static', 'formatstatus', '' ,"<p class='alert alert-warning alert-block fade-in text-center w-100'>{$status_String}</p>");
    }else {
        $status_String = get_string('formatfound', 'customdocument');
        $mform->addElement('static','formatstatus', '', "<p class='alert alert-info fade-in pt-3'>{$status_String}</p>");
    }
        // General options.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('certificatename', 'customdocument'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'certificatename', 'customdocument');

        $this->standard_intro_elements(get_string('intro', 'customdocument'));
        $mform->addElement('advcheckbox', 'showdescription', get_string('showdescription'));
        $mform->addHelpButton('showdescription', 'showdescription');

        // Design Options.
        $mform->addElement('header', 'designoptions', get_string('designoptions', 'customdocument'));

        // Certificate image file.
        $mform->addElement('filemanager', 'certificateimage',
            get_string('certificateimage', 'customdocument'), null,
            $this->get_filemanager_options_array()
        );
        $mform->addHelpButton('certificateimage', 'certificateimage', 'customdocument');

        // Certificate Text HTML editor.
        $mform->addElement('editor', 'certificatetext',
            get_string('certificatetext', 'customdocument'), null,
            customdocument_get_editor_options($this->context)
        );
        global $OUTPUT;
        $url = new moodle_url('/mod/customdocument/values.php');

        $msg = get_string('infochamps', 'customdocument', $url->out());
        // $msg .= ' ' .$OUTPUT->help_icon('infochamps', 'customdocument');
        $mform->addElement('static', 'content', '', $msg);

        $mform->addRule('certificatetext', get_string('error'), 'required', null, 'client');
        $mform->addHelpButton('certificatetext', 'certificatetext', 'customdocument');

        // Certificate Width.
        $mform->addElement('text', 'width', get_string('width', 'customdocument'), array('size' => '5'));
        $mform->setType('width', PARAM_INT);
        $mform->setDefault('width', get_config('customdocument', 'width'));
        $mform->setAdvanced('width');
        $mform->addHelpButton('width', 'size', 'customdocument');

        // Certificate Height.
        $mform->addElement('text', 'height', get_string('height', 'customdocument'), array('size' => '5'));
        $mform->setType('height', PARAM_INT);
        $mform->setDefault('height', get_config('customdocument', 'height'));
        $mform->setAdvanced('height');
        $mform->addHelpButton('height', 'size', 'customdocument');

        // Certificate Position X.
        $mform->addElement('text', 'certificatetextx', get_string('certificatetextx', 'customdocument'), array('size' => '5'));
        $mform->setType('certificatetextx', PARAM_INT);
        $mform->setDefault('certificatetextx', get_config('customdocument', 'certificatetextx'));
        $mform->setAdvanced('certificatetextx');
        $mform->addHelpButton('certificatetextx', 'textposition', 'customdocument');

        // Certificate Position Y.
        $mform->addElement('text', 'certificatetexty', get_string('certificatetexty', 'customdocument'), array('size' => '5'));
        $mform->setType('certificatetexty', PARAM_INT);
        $mform->setDefault('certificatetexty', get_config('customdocument', 'certificatetexty'));
        $mform->setAdvanced('certificatetexty');
        $mform->addHelpButton('certificatetexty', 'textposition', 'customdocument');

        if(array_key_exists('moofactory_resetmod', \core_component::get_plugin_list('local'))){
            // Certificate period of validity.
            $mform->addElement('text', 'validity', get_string('validity', 'customdocument'), array('size' => '5'));
            $mform->setType('validity', PARAM_INT);
            $mform->setDefault('validity', get_config('customdocument', 'validity'));
            $mform->setAdvanced('validity');
            $mform->addHelpButton('validity', 'validity', 'customdocument');
            
            // Certificate renewal period.
            $mform->addElement('text', 'renewalperiod', get_string('renewalperiod', 'customdocument'), array('size' => '5'));
            $mform->setType('renewalperiod', PARAM_INT);
            $mform->setDefault('renewalperiod', get_config('customdocument', 'renewalperiod'));
            $mform->setAdvanced('renewalperiod');
            $mform->addHelpButton('renewalperiod', 'renewalperiod', 'customdocument');

            // Reset all modules.
            $mform->addElement('checkbox', 'resetall', get_string('resetall', 'customdocument'));
            $mform->setDefault('resetall', get_config('customdocument', 'resetall'));
            $mform->setAdvanced('resetall');
            $mform->addHelpButton('resetall', 'resetall', 'customdocument');
        }


        // Second page.
        $mform->addElement('header', 'secondpageoptions', get_string('secondpageoptions', 'customdocument'));
        // Enable back page text.

        $mform->addElement('selectyesno', 'enablesecondpage', get_string('enablesecondpage', 'customdocument'));
        $mform->setDefault('enablesecondpage', get_config('customdocument', 'enablesecondpage'));
        $mform->addHelpButton('enablesecondpage', 'enablesecondpage', 'customdocument');

        // Certificate secondimage file.
        $mform->addElement('filemanager', 'secondimage',
            get_string('secondimage', 'customdocument'), null,
            $this->get_filemanager_options_array());
        $mform->addHelpButton('secondimage', 'secondimage', 'customdocument');
        $mform->disabledIf('secondimage', 'enablesecondpage', 'eq', 0);

        // Certificate secondText HTML editor.
        $mform->addElement('editor', 'secondpagetext',
            get_string('secondpagetext', 'customdocument'), null,
            customdocument_get_editor_options($this->context));
        $mform->addHelpButton('secondpagetext', 'certificatetext', 'customdocument');
        $mform->disabledIf('secondpagetext', 'enablesecondpage', 'eq', 0);

        // Certificate Position X.
        $mform->addElement('text', 'secondpagex', get_string('secondpagex', 'customdocument'), array('size' => '5'));
        $mform->setType('secondpagex', PARAM_INT);
        $mform->setDefault('secondpagex', get_config('customdocument', 'certificatetextx'));
        $mform->setAdvanced('secondpagex');
        $mform->addHelpButton('secondpagex', 'secondtextposition', 'customdocument');
        $mform->disabledIf('secondpagex', 'enablesecondpage', 'eq', 0);

        // Certificate Position Y.
        $mform->addElement('text', 'secondpagey', get_string('secondpagey', 'customdocument'), array('size' => '5'));
        $mform->setType('secondpagey', PARAM_INT);
        $mform->setDefault('secondpagey', get_config('customdocument', 'certificatetexty'));
        $mform->setAdvanced('secondpagey');
        $mform->addHelpButton('secondpagey', 'secondtextposition', 'customdocument');
        $mform->disabledIf('secondpagey', 'enablesecondpage', 'eq', 0);

        // Third page.
        $mform->addElement('header', 'thirdpageoptions', get_string('thirdpageoptions', 'customdocument'));
        // Enable back page text.

        $mform->addElement('selectyesno', 'enablethirdpage', get_string('enablethirdpage', 'customdocument'));
        $mform->setDefault('enablethirdpage', get_config('customdocument', 'enablethirdpage'));
        $mform->addHelpButton('enablethirdpage', 'enablethirdpage', 'customdocument');
        $mform->disabledIf('enablethirdpage', 'enablesecondpage', 'eq', 0);

        // Certificate thirdimage file.
        $mform->addElement('filemanager', 'thirdimage',
            get_string('thirdimage', 'customdocument'), null,
            $this->get_filemanager_options_array());
        $mform->addHelpButton('thirdimage', 'thirdimage', 'customdocument');
        $mform->disabledIf('thirdimage', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('thirdimage', 'enablethirdpage', 'eq', 0);

        // Certificate thirdText HTML editor.
        $mform->addElement('editor', 'thirdpagetext',
            get_string('thirdpagetext', 'customdocument'), null,
            customdocument_get_editor_options($this->context));
        $mform->addHelpButton('thirdpagetext', 'certificatetext', 'customdocument');
        $mform->disabledIf('thirdpagetext', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('thirdpagetext', 'enablethirdpage', 'eq', 0);

        // Certificate Position X.
        $mform->addElement('text', 'thirdpagex', get_string('thirdpagex', 'customdocument'), array('size' => '5'));
        $mform->setType('thirdpagex', PARAM_INT);
        $mform->setDefault('thirdpagex', get_config('customdocument', 'certificatetextx'));
        $mform->setAdvanced('thirdpagex');
        $mform->addHelpButton('thirdpagex', 'thirdtextposition', 'customdocument');
        $mform->disabledIf('thirdpagex', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('thirdpagex', 'enablethirdpage', 'eq', 0);

        // Certificate Position Y.
        $mform->addElement('text', 'thirdpagey', get_string('thirdpagey', 'customdocument'), array('size' => '5'));
        $mform->setType('thirdpagey', PARAM_INT);
        $mform->setDefault('thirdpagey', get_config('customdocument', 'certificatetexty'));
        $mform->setAdvanced('thirdpagey');
        $mform->addHelpButton('thirdpagey', 'thirdtextposition', 'customdocument');
        $mform->disabledIf('thirdpagey', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('thirdpagey', 'enablethirdpage', 'eq', 0);
        
        // Fourth page.
        $mform->addElement('header', 'fourthpageoptions', get_string('fourthpageoptions', 'customdocument'));
        // Enable back page text.

        $mform->addElement('selectyesno', 'enablefourthpage', get_string('enablefourthpage', 'customdocument'));
        $mform->setDefault('enablefourthpage', get_config('customdocument', 'enablefourthpage'));
        $mform->addHelpButton('enablefourthpage', 'enablefourthpage', 'customdocument');
        $mform->disabledIf('enablefourthpage', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('enablefourthpage', 'enablethirdpage', 'eq', 0);

        // Certificate fourthimage file.
        $mform->addElement('filemanager', 'fourthimage',
            get_string('fourthimage', 'customdocument'), null,
            $this->get_filemanager_options_array());
        $mform->addHelpButton('fourthimage', 'fourthimage', 'customdocument');
        $mform->disabledIf('fourthimage', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('fourthimage', 'enablethirdpage', 'eq', 0);
        $mform->disabledIf('fourthimage', 'enablefourthpage', 'eq', 0);

        // Certificate thirdText HTML editor.
        $mform->addElement('editor', 'fourthpagetext',
            get_string('fourthpagetext', 'customdocument'), null,
            customdocument_get_editor_options($this->context));
        $mform->addHelpButton('fourthpagetext', 'certificatetext', 'customdocument');
        $mform->disabledIf('fourthpagetext', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('fourthpagetext', 'enablethirdpage', 'eq', 0);
        $mform->disabledIf('fourthpagetext', 'enablefourthpage', 'eq', 0);

        // Certificate Position X.
        $mform->addElement('text', 'fourthpagex', get_string('fourthpagex', 'customdocument'), array('size' => '5'));
        $mform->setType('fourthpagex', PARAM_INT);
        $mform->setDefault('fourthpagex', get_config('customdocument', 'certificatetextx'));
        $mform->setAdvanced('fourthpagex');
        $mform->addHelpButton('fourthpagex', 'fourthtextposition', 'customdocument');
        $mform->disabledIf('fourthpagex', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('fourthpagex', 'enablethirdhpage', 'eq', 0);
        $mform->disabledIf('fourthpagex', 'enablefourthpage', 'eq', 0);

        // Certificate Position Y.
        $mform->addElement('text', 'fourthpagey', get_string('fourthpagey', 'customdocument'), array('size' => '5'));
        $mform->setType('fourthpagey', PARAM_INT);
        $mform->setDefault('fourthpagey', get_config('customdocument', 'certificatetexty'));
        $mform->setAdvanced('fourthpagey');
        $mform->addHelpButton('fourthpagey', 'fourthtextposition', 'customdocument');
        $mform->disabledIf('fourthpagey', 'enablesecondpage', 'eq', 0);
        $mform->disabledIf('fourthpagey', 'enablethirdpage', 'eq', 0);
        $mform->disabledIf('fourthpagey', 'enablefourthpage', 'eq', 0);
        
        // Variable options.
        $mform->addElement('header', 'variablesoptions', get_string('variablesoptions', 'customdocument'));
        // Certificate Alternative Course Name.
        // $mform->addElement('text', 'coursename', get_string('coursename', 'customdocument'), array('size' => '64'));
        // $mform->setType('coursename', PARAM_TEXT);
        // $mform->setAdvanced('coursename');
        // $mform->addHelpButton('coursename', 'coursename', 'customdocument');

        // Certificate Outcomes.


        // Mettre en commentaire pour enlever les menu d'options de dates
        // Certificate date options.
        $mform->addElement('select', 'certdate', get_string('printdate', 'customdocument'),
                        customdocument_get_date_options());
        $mform->setDefault('certdate', get_config('customdocument', 'certdate'));
        $mform->addHelpButton('certdate', 'printdate', 'customdocument');



        // Certificate timestart date format.
        // $mform->addElement('text', 'timestartdatefmt', get_string('timestartdatefmt', 'customdocument'));
        // $mform->setDefault('timestartdatefmt', '');
        // $mform->setType('timestartdatefmt', PARAM_TEXT);
        // $mform->addHelpButton('timestartdatefmt', 'timestartdatefmt', 'customdocument');
        // $mform->setAdvanced('timestartdatefmt');

        // Certificare grade Options.
        $mform->addElement('select', 'certgrade', get_string('printgrade', 'customdocument'),
                        customdocument_get_grade_options());
        $mform->setDefault('certgrade', -1);
        $mform->addHelpButton('certgrade', 'printgrade', 'customdocument');

        // Certificate grade format.
        $gradeformatoptions = array( 1 => get_string('gradepercent', 'customdocument'),
                                2 => get_string('gradepoints', 'customdocument'),
                                3 => get_string('gradeletter', 'customdocument')
        );
        $mform->addElement('select', 'gradefmt', get_string('gradefmt', 'customdocument'), $gradeformatoptions);
        $mform->setDefault('gradefmt', 0);
        $mform->addHelpButton('gradefmt', 'gradefmt', 'customdocument');

        // Certificate date format.
        $mform->addElement('text', 'certdatefmt', get_string('datefmt', 'customdocument'));
        $mform->setDefault('certdatefmt', '');
        $mform->setType('certdatefmt', PARAM_TEXT);
        $mform->addHelpButton('certdatefmt', 'datefmt', 'customdocument');
        $mform->setAdvanced('certdatefmt');

        $outcomeoptions = customdocument_get_outcomes();
        $mform->addElement('select', 'outcome', get_string('printoutcome', 'customdocument'), $outcomeoptions);
        $mform->setDefault('outcome', 0);
        $mform->setAdvanced('outcome');
        $mform->addHelpButton('outcome', 'printoutcome', 'customdocument');

        $mform->addElement('header', 'qrcodeoptions', get_string('qrcodeoptions', 'customdocument'));
        // QR code.

        $mform->addElement('selectyesno', 'printqrcode', get_string('printqrcode', 'customdocument'));
        $mform->setDefault('printqrcode', get_config('customdocument', 'printqrcode'));
        $mform->addHelpButton('printqrcode', 'printqrcode', 'customdocument');

        $mform->addElement('selectyesno', 'qrcodefirstpage', get_string('qrcodefirstpage', 'customdocument'));
        $mform->setDefault('qrcodefirstpage', get_config('customdocument', 'qrcodefirstpage'));
        $mform->addHelpButton('qrcodefirstpage', 'qrcodefirstpage', 'customdocument');

        $mform->addElement('text', 'codex', get_string('codex', 'customdocument'), array('size' => '5'));
        $mform->setType('codex', PARAM_INT);
        $mform->setDefault('codex', get_config('customdocument', 'codex'));
        // $mform->setAdvanced('codex');
        $mform->addHelpButton('codex', 'qrcodeposition', 'customdocument');

        $mform->addElement('text', 'codey', get_string('codey', 'customdocument'), array('size' => '5'));
        $mform->setType('codey', PARAM_INT);
        $mform->setDefault('codey', get_config('customdocument', 'codey'));
        // $mform->setAdvanced('codey');
        $mform->addHelpButton('codey', 'qrcodeposition', 'customdocument');




        // Issue options.

        $mform->addElement('header', 'issueoptions', get_string('issueoptions', 'customdocument'));

        // Email to teachers ?
        $mform->addElement('selectyesno', 'emailteachers', get_string('emailteachers', 'customdocument'));
        $mform->setDefault('emailteachers', 0);
        $mform->addHelpButton('emailteachers', 'emailteachers', 'customdocument');

        // Email Others.
        $mform->addElement('text', 'emailothers', get_string('emailothers', 'customdocument'),
                        array('size' => '40', 'maxsize' => '200'));
        $mform->setType('emailothers', PARAM_TEXT);
        $mform->addHelpButton('emailothers', 'emailothers', 'customdocument');

        // // Email From.
        // $mform->addElement('text', 'emailfrom', get_string('emailfrom', 'customdocument'),
        //                 array('size' => '40', 'maxsize' => '200'));
        // $mform->setDefault('emailfrom', $CFG->supportname);
        // $mform->setType('emailfrom', PARAM_EMAIL);
        // $mform->addHelpButton('emailfrom', 'emailfrom', 'customdocument');
        // $mform->setAdvanced('emailfrom');

        // Delivery Options (Email, Download,...).
        $deliveryoptions = array(
            0 => get_string('openbrowser', 'customdocument'),
            1 => get_string('download', 'customdocument'),
            2 => get_string('emailcertificate', 'customdocument'),
            3 => get_string('nodelivering','customdocument'),
            4 => get_string('emailoncompletion', 'customdocument'),
            5 => get_string('emailonrestriction', 'customdocument'),
        );
        $mform->addElement('select', 'delivery', get_string('delivery', 'customdocument'), $deliveryoptions);
        $mform->setDefault('delivery', 0);
        $mform->addHelpButton('delivery', 'delivery', 'customdocument');

        // Report Cert.
        // TODO acredito que seja para verificar o certificado pelo código, se for isto pode remover.
        $reportfile = "$CFG->dirroot/customdocuments/index.php";
        if (file_exists($reportfile)) {
            $mform->addElement('selectyesno', 'reportcert', get_string('reportcert', 'customdocument'));
            $mform->setDefault('reportcert', 0);
            $mform->addHelpButton('reportcert', 'reportcert', 'customdocument');
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Prepares the form before data are set
     *
     * Additional wysiwyg editor are prepared here, the introeditor is prepared automatically by core.
     * Grade items are set here because the core modedit supports single grade item only.
     *
     * @param array $data to be set
     * @return void
     */
    public function data_preprocessing(&$data) {
        global $CFG;
        require_once(dirname(__FILE__) . '/locallib.php');
        parent::data_preprocessing($data);
        if ($this->current->instance) {
            // Editing an existing certificate - let us prepare the added editor elements (intro done automatically), and files.
            // First Page.
            // Get firstimage.
            $imagedraftitemid = file_get_submitted_draft_itemid('certificateimage');
            // Get firtsimage filearea information.
            $imagefileinfo = customdocument::get_certificate_image_fileinfo($this->context);
            file_prepare_draft_area($imagedraftitemid, $imagefileinfo['contextid'],
                            $imagefileinfo['component'], $imagefileinfo['filearea'],
                            $imagefileinfo['itemid'],
                            $this->get_filemanager_options_array());

            $data['certificateimage'] = $imagedraftitemid;

            // Prepare certificate text.
            $text = $data['certificatetext'];
            $draftid_editor = file_get_submitted_draft_itemid('certificatetext');
            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }
            $textfileinfo = customdocument::get_certificate_text_fileinfo($this->context);
            $newtext = file_prepare_draft_area($draftid_editor, $textfileinfo['contextid'],
            $textfileinfo['component'], $textfileinfo['filearea'],
            $textfileinfo['itemid'], $this->get_filemanager_options_array(), $currenttext);

            $data['certificatetext'] = array('text' => $newtext , 'itemid' => $draftid_editor, 'format' => FORMAT_HTML);

            // Second page.
            // Get second image.
            $secondimagedraftitemid = file_get_submitted_draft_itemid('secondimage');
            // Get secondimage filearea info.
            $secondimagefileinfo = customdocument::get_certificate_secondimage_fileinfo($this->context);
            file_prepare_draft_area($secondimagedraftitemid, $secondimagefileinfo['contextid'],
                            $secondimagefileinfo['component'], $secondimagefileinfo['filearea'],
                            $secondimagefileinfo['itemid'],
                            $this->get_filemanager_options_array());
            $data['secondimage'] = $secondimagedraftitemid;

            $text = $data['secondpagetext'];
            $draftid_editor = file_get_submitted_draft_itemid('secondpagetext');
            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }
            $textfileinfo = customdocument::get_certificate_secondtext_fileinfo($this->context);
            $newtext = file_prepare_draft_area($draftid_editor, $textfileinfo['contextid'],
            $textfileinfo['component'], $textfileinfo['filearea'],
            $textfileinfo['itemid'], $this->get_filemanager_options_array(), $currenttext);

            $data['secondpagetext'] = array('text' => $newtext , 'itemid' => $draftid_editor, 'format' => FORMAT_HTML);

            // Third page.
            // Get third image.
            $thirdimagedraftitemid = file_get_submitted_draft_itemid('thirdimage');
            // Get thirdimage filearea info.
            $thirdimagefileinfo = customdocument::get_certificate_thirdimage_fileinfo($this->context);
            file_prepare_draft_area($thirdimagedraftitemid, $thirdimagefileinfo['contextid'],
                            $thirdimagefileinfo['component'], $thirdimagefileinfo['filearea'],
                            $thirdimagefileinfo['itemid'],
                            $this->get_filemanager_options_array());
            $data['thirdimage'] = $thirdimagedraftitemid;
            
            $text = $data['thirdpagetext'];
            $draftid_editor = file_get_submitted_draft_itemid('thirdpagetext');
            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }
            $textfileinfo = customdocument::get_certificate_thirdtext_fileinfo($this->context);
            $newtext = file_prepare_draft_area($draftid_editor, $textfileinfo['contextid'],
            $textfileinfo['component'], $textfileinfo['filearea'],
            $textfileinfo['itemid'], $this->get_filemanager_options_array(), $currenttext);

            $data['thirdpagetext'] = array('text' => $newtext , 'itemid' => $draftid_editor, 'format' => FORMAT_HTML);

            // Fourth page.
            // Get fourth image.
            $fourthimagedraftitemid = file_get_submitted_draft_itemid('fourthimage');
            // Get fourthimage filearea info.
            $fourthimagefileinfo = customdocument::get_certificate_fourthimage_fileinfo($this->context);
            file_prepare_draft_area($fourthimagedraftitemid, $fourthimagefileinfo['contextid'],
                            $fourthimagefileinfo['component'], $fourthimagefileinfo['filearea'],
                            $fourthimagefileinfo['itemid'],
                            $this->get_filemanager_options_array());
            $data['fourthimage'] = $fourthimagedraftitemid;
            
            $text = $data['fourthpagetext'];
            $draftid_editor = file_get_submitted_draft_itemid('fourthpagetext');
            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }
            $textfileinfo = customdocument::get_certificate_fourthtext_fileinfo($this->context);
            $newtext = file_prepare_draft_area($draftid_editor, $textfileinfo['contextid'],
            $textfileinfo['component'], $textfileinfo['filearea'],
            $textfileinfo['itemid'], $this->get_filemanager_options_array(), $currenttext);

            $data['fourthpagetext'] = array('text' => $newtext , 'itemid' => $draftid_editor, 'format' => FORMAT_HTML);

        } else { // Load default.
            $data['certificatetext'] = array('text' => '', 'format' => FORMAT_HTML);
            $data['secondpagetext'] = array('text' => '', 'format' => FORMAT_HTML);
            $data['thirdpagetext'] = array('text' => '', 'format' => FORMAT_HTML);
            $data['fourthpagetext'] = array('text' => '', 'format' => FORMAT_HTML);
        }

        // Completion rules.
        $data['completiontimeenabled'] = !empty($data['requiredtime']) ? 1 : 0;

    }

    // public function add_completion_rules() {
    //     $mform =& $this->_form;

    //     $group = array();

    //     $group[] =& $mform->createElement('checkbox', 'completiontimeenabled', ' ',
    //                     get_string('coursetimereq', 'customdocument'));
    //     $group[] =& $mform->createElement('text', 'requiredtime', '', array('size' => '3'));
    //     $mform->setType('requiredtime', PARAM_INT);
    //     $mform->addGroup($group, 'completiontimegroup', get_string('coursetimereq', 'customdocument'), array(' '), false);

    //     $mform->addHelpButton('completiontimegroup', 'coursetimereq', 'customdocument');
    //     $mform->disabledIf('requiredtime', 'completiontimeenabled', 'notchecked');

    //     return array('completiontimegroup');
    // }

    public function completion_rule_enabled($data) {
        return (!empty($data['completiontimeenabled']) && $data['requiredtime'] != 0);
    }

    public function data_postprocessing($data) {

        // For Completion Rules.
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completiontimeenabled) || !$autocompletion) {
                $data->requiredtime = 0;
            }
        }
        // File manager always creata a Files folder, so certimages is never empty.
        // I must check if it has a file or it's only a empty files folder reference.
        if (isset($data->certificateimage) && !empty($data->certificateimage)
            && !$this->check_has_files('certificateimage')) {
                $data->certificateimage = null;
        }

        if (isset($data->secondimage) && !empty($data->secondimage) &&
        !$this->check_has_files('secondimage')) {
                $data->secondimage = null;
        }

        if (isset($data->thirdimage) && !empty($data->thirdimage) &&
        !$this->check_has_files('thirdimage')) {
                $data->thirdimage = null;
        }

        if (isset($data->fourthimage) && !empty($data->fourthimage) &&
        !$this->check_has_files('fourthimage')) {
                $data->fourthimage = null;
        }
    }

    /**
     * Some basic validation
     *
     * @param $data
     * @param $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check that the required time entered is valid.
        if ((isset($data['requiredtime']) && $data['requiredtime'] < 0)) {
            $errors['requiredtime'] = get_string('requiredtimenotvalid', 'customdocument');
        }

        return $errors;
    }

    private function check_has_files($itemname) {
        global $USER;

        $draftitemid = file_get_submitted_draft_itemid($itemname);
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_customdocument', 'imagefilecheck', null,
                                $this->get_filemanager_options_array());

        // Get file from users draft area.
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

        return (count($files) > 0);
    }

    private function get_filemanager_options_array () {
        global $COURSE;

        return array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1,
                'accepted_types' => array('.png', '.jpg' , '.jpeg'));
    }

}