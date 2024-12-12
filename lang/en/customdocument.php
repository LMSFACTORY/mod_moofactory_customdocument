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
 * Language strings for the customdocument module
 *
 * @package     mod_customdocument
 * @copyright   2024 Patrick ROCHET <patrick.r@lmsfactory.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'Custom Document';
$string['modulenameplural'] = 'Custom Documents';
$string['pluginname'] = 'Custom Document';
$string['viewcertificateviews'] = 'View {$a} issued documents';
$string['summaryofattempts'] = 'Summary of previously received documents';
$string['issued'] = 'Issued';
$string['coursegrade'] = 'Course Grade';
$string['awardedto'] = 'Awarded To';
$string['receiveddate'] = 'Issued Date';
$string['issueddate'] = 'Déliverance Date';
$string['grade'] = 'Grade';
$string['code'] = 'Code';
$string['report'] = 'Report';
$string['hours'] = 'hours';
$string['keywords'] = 'document, certificate, course, pdf, moodle';
$string['pluginadministration'] = 'Document administration';
$string['deletissuedcertificates'] = 'Delete issued documents';
$string['nocertificatesissued'] = 'There are no issued document';
// $string['awardedsubject'] = 'Awarded document notification: {$a->document} issued to {$a->student}';
$string['awardedsubject'] = '{$a->student} got a document from {$a->sitefullname}';
$string['modulename_help'] = 'The custom document activity module enables the teacher to create a custom document that can be issued to participants who have completed the teacher’s specified requirements.';
$string['certificatecopy'] = 'COPY';

// General
$string['certificatename'] = 'Document Name';
$string['certificatename_help'] = 'Nom du document affiché sur la page de cours';
$string['certificatename_help'] = 'Document Name as display on the course page';
$string['intro'] = 'Description';

//Front page design
$string['designoptions'] = 'Front page design';
$string['certificateimage'] = 'Front page background image';
$string['certificateimage_help'] = 'Picture that will be used in the front page background';
$string['certificatetext'] = 'Front page content';
$string['certificatetext_help'] = 'Here input and format the front page content : texts, tables, merge fields (special fields that substitute variable between {} with student datas), images (logo, signature)...';
$string['infochamps'] = '<a href="{$a}" target="_blank" >Click to see all merger fields</a>';

$string['height'] = 'Document Height';
$string['width'] = 'Document Width';
$string['size'] = 'Document Size';
$string['size_help'] = 'Width and height size (in millimetres) of the document. Default size is A4 Portrait';
$string['textposition'] = 'Front page content position';
$string['textposition_help'] = 'The XY coordinates (in millimetres) of the front page content';
$string['certificatetextx'] = 'Front page content horizontal start position (Top margin)';
$string['certificatetexty'] = 'Front page content vertical start position (Left margin)';

// Second page design
$string['secondpageoptions'] = 'Second page design';
$string['enablesecondpage'] = 'Enable document second page';
$string['enablesecondpage_help'] = 'Enable document second page edition, if is disabled, only document QR code will be printed in second page (if the QR code is enabled)';
$string['secondimage'] = 'Second page background image';
$string['secondimage_help'] = 'Picture that will be used in the second page background';
$string['secondpagetext'] = 'Second page content';

$string['secondpagex'] = 'Second page content left margin';
$string['secondpagey'] = 'Second page content top margin';
$string['secondtextposition'] = 'Second page content position';
$string['secondtextposition_help'] = 'The XY coordinates (in millimetres) of the second page content';

// Third page design
$string['thirdpageoptions'] = 'Third page design';
$string['enablethirdpage'] = 'Enable document third page';
$string['enablethirdpage_help'] = 'Enable document third page edition, if is disabled, only document QR code will be printed in third page (if the QR code is enabled)';
$string['thirdimage'] = 'Third page background image';
$string['thirdimage_help'] = 'Picture that will be used in the third page background';
$string['thirdpagetext'] = 'Third page content';

$string['thirdpagex'] = 'Third page content left margin';
$string['thirdpagey'] = 'Third page content top margin';
$string['thirdtextposition'] = 'Third page content position';
$string['thirdtextposition_help'] = 'The XY coordinates (in millimetres) of the third page content';

// Fourth page design
$string['fourthpageoptions'] = 'Fourth page design';
$string['enablefourthpage'] = 'Enable document fourth page';
$string['enablefourthpage_help'] = 'Enable document fourth page edition, if is disabled, only document QR code will be printed in fourth page (if the QR code is enabled)';
$string['fourthimage'] = 'Fourth page background image';
$string['fourthimage_help'] = 'Picture that will be used in the fourth page background';
$string['fourthpagetext'] = 'Fourth page content';

$string['fourthpagex'] = 'Fourth page content left margin';
$string['fourthpagey'] = 'Fourth page content top margin';
$string['fourthtextposition'] = 'Fourth page content position';
$string['fourthtextposition_help'] = 'The XY coordinates (in millimetres) of the fourth page content';

//Merge fields references and formats
$string['variablesoptions'] = 'Merge fields references and formats';
$string['printdate'] = 'Date of activity completion for {{ACTIVITYCOMPLETIONDATE}} merge field';
$string['printdate_help'] = 'Select an activity to substitute the {{ACTIVITYCOMPLETIONDATE}] merge field with the activity completion date';

$string['printgrade'] = 'Referenced grade for {{GRADE}} tables and merge field';
$string['printgrade_help'] = 'You can choose any available course grade items from the gradebook to print it : on the document in place of {{GRADE}} merge field, on the "Issued documents summary" and "Bulk operations" tab.';
$string['gradefmt'] = 'Grade Format';
$string['gradefmt_help'] = 'There are three available grade formats: as a percentage, as the point value of the grade and as a letter.';
$string['gradeletter'] = 'Letter Grade';
$string['gradepercent'] = 'Percentage Grade';
$string['gradepoints'] = 'Points Grade';

$string['datefmt'] = 'Date Format';
$string['datefmt_help'] = 'Enter a valid PHP date format pattern (<a href="http://www.php.net/manual/en/function.strftime.php"> Date Formats</a>). Or, leave it empty to use the format of the user\'s chosen language.';
$string['printoutcome'] = 'Print Outcome';
$string['printoutcome_help'] = 'You can choose any course outcome to print the name of the outcome and the user\'s received outcome on the document (<a href="https://docs.moodle.org/311/en/Outcomes">Outcomes</a>).  An example might be: Assignment Outcome: Proficient. ';

// QR Code options
$string['qrcodeoptions'] = 'QR Code options';
$string['printqrcode'] = 'Print Document QR Code';
$string['printqrcode_help'] = 'Select "Yes" to print document QR Code which is unique to each issued document';
$string['qrcodefirstpage'] = 'Print QR Code on the first page';
$string['qrcodefirstpage_help'] = 'Select "yes" to print QR Code on the first page. Select "no" to print it on the last page.';
$string['codex'] = 'QR Code horizontal start position';
$string['codey'] = 'QR Code vertical start position';
$string['qrcodeposition'] = 'QR Code position on the document';
$string['qrcodeposition_help'] = 'The XY coordinates (in millimeters) of the QR Code on the document';

//Issue Options
$string['issueoptions'] = 'Issue Options';
$string['emailteachers'] = 'Notify and email a copy to the learner\'s teachers and supervisors';
$string['emailothers'] = 'Other e-mail addresses to notify';
$string['emailteachers_help'] = 'If enabled, each time a document is generated, roles with the "mod/customdocument:canreceivenotifications" capability will receive an email with the document attached.';
$string['emailothers_help'] = 'Enter the email addresses here, separated by a comma, for those who should be alerted with an email whenever a document is issued.';

$string['delivery'] = 'Delivery behaviour when a student issues his own document';
$string['delivery_help'] = 'Choose here how you would like your students to get their document. when users receive their document, they will see the date it has been issued.';
$string['openbrowser'] = 'Open in new window';
$string['download'] = 'Force download';
$string['emailcertificate'] = 'Send to student by email';
$string['nodelivering'] = 'No delivering, user will receive this document using others ways';
$string['emailoncompletion'] = 'Send to student email upon course completion';
$string['emailonrestriction'] = 'Send to student email after lifting the access restriction';
$string['generateoncompletion'] = 'Generate document upon course completion, without send';
$string['generateonrestriction'] = 'Generate document after lifting the access restriction, without send';

// Issue a document or test document
$string['standardview'] = 'Issue a test document';
$string['getcertificate'] = 'Get your document';
$string['opendownload'] = 'Click the button below to save your document to your computer.';
$string['openemail'] = 'Click the button below and your document will be sent to you as an email attachment.';
$string['openwindow'] = 'Click the button below to open your document in a new browser window.';
$string['issueddownload'] = 'Issued document [id: {$a}] downloaded';
$string['emailsent'] = 'Email has been sent';

//Issued documents summary
$string['issuedview'] = 'Issued documents summary';
$string['firstname'] = "Firstname";
$string['lastname'] = "Lastname";
$string['fullname'] = "Full name";
$string['receptiondate'] = "Reception date";
$string['deleteall'] = "Delete All";
$string['deleteselected'] = "Delete Selected";

// Bulk operations
$string['bulkview'] = 'Bulk operations';
$string['showusers'] = 'Show';
$string['completedusers'] = 'Users that met the activity conditions';
$string['allusers'] = 'All users';
$string['bulkaction'] = 'Choose a Bulk Operation';
$string['onepdf'] = 'Download documents in one pdf file';
$string['multipdf'] = 'Download documents in a zip file';
$string['sendtoemail'] = 'Send to user\'s email';
$string['bulkbuttonlabel'] = 'Download or send selected documents';
$string['bulkwarning'] = "If no selection, all the students from the list will have their document issued";

// Emails text.
$string['emailstudentsubject'] = 'Your document is available on {$a->sitefullname}';

$string['emailstudenttext'] = '
Hello {$a->username},

Attached you will find your document "{$a->certificate}" concerning the course "{$a->course}".
 
THIS IS AN AUTOMATED MESSAGE - PLEASE DO NOT REPLY';

$string['emailteachermail'] = '
Hello{$a->username},

{$a->student} obtained the document "{$a->document}" for the course "{$a->course}".

Attached you will find the document.
You can also consult it at: {$a->url}.

THIS IS AN AUTOMATIC MESSAGE - PLEASE DO NOT REPLY';

$string['emailteachermailhtml'] = '
Hello{$a->username},

{$a->student} obtained the document "<i>{$a->document}</i>" for the course "{$a->course}".

Attached you will find the document.
You can also consult it at: <a href="{$a->url}">Document Report</a>.

THIS IS AN AUTOMATIC MESSAGE - PLEASE DO NOT REPLY';

// Admin settings page
$string['defaultwidth'] = 'Default Width';
$string['defaultheight'] = 'Default Height';
$string['defaultcertificatetextx'] = 'Front page content left margin';
$string['defaultcertificatetexty'] = 'Front page content top margin';
$string['defaultcodex'] = 'Default QR Code horizontal start position';
$string['defaultcodey'] = 'Default QR Code vertical start position';
$string['certlifetime'] = 'Keep issued documents for: (in months)';
$string['certlifetime_help'] = 'This specifies the length of time in month you want to keep issued documents. Issued documents that are older than this age are automatically deleted.';
$string['defaultperpage'] = 'Number of items per page';
$string['defaultperpage_help'] = 'Number of students or documents to show per page (Max. 200) in "Issued documents summary" and "Bulk operations"';

// Verify document page
$string['certificateverification'] = 'Document Verification';
$string['neverdeleteoption'] = 'Never delete';
$string['verifycertificate'] = 'Verify Document';
$string['eventcertificate_verified'] = 'Document verified';
$string['eventcertificate_verified_description'] = 'The user with id {$a->userid} verified the document with id {$a->certificateid}, issued to user with id {$a->document_userid}.';

// For Capabilities
$string['customdocument:addinstance'] = "Add Custom document Activity";
$string['customdocument:manage'] = "Manage Custom document Activity";
$string['customdocument:view'] = "View Custom document Activity";
$string['customdocument:canreceivenotifications'] = "Receive notifications for teachers";

// Erreurs
$string['filenotfound'] = 'File not Found : {$a}';
$string['invalidcode'] = 'Invalid document code';
$string['cantdeleteissue'] = 'Error removing issued documents';
$string['cantissue'] = 'The document can\'t be issued, because the user hasn\'t met activity conditions';

$string['usercontextnotfound'] = 'User context not found';
$string['usernotfound'] = 'User not found';
$string['coursenotfound'] = 'Course not found';
$string['issuedcertificatenotfound'] = 'Issued document not found';
$string['certificatenot'] = 'Custom document instance not found';

$string['upgradeerror'] = 'Error while upgrading $a';
$string['notreceived'] = 'No issued document';

$string['customhelp_help'] = "Contact the teacher if needed";


// Tableau des champs de fusion
$string['moduledoctitle'] = "Custom document merge fields";
$string['thnom'] = "Name";
$string['thdescription'] = "Description";
$string['colon'] = ": ";

//Course settings
// $string['coursefield'] = "Course settings";
$string['modulecoursetitle'] = "Course settings";
$string['coursenamedesc'] = "Full course name";
$string['coursestartdatedesc'] = "Course start date (as defined in course settings)";
$string['courseenddatedesc'] = "Course end date (as defined in course settings)";
$string['courseversiondesc'] = "Course current version";
$string['coursecustomfielddesc'] = "Course custom fields (can add / edit from admin > courses > Course custom fields), the XXX is the short name of the field and must be capitalized. <br> Example: COURSECUSTOMFIELD_COURSEVERSION for the 'Version' field";

//User profile fields
$string['userprofilefield'] = "User Profile fields";
$string['usernamedesc'] = "Full user name (last name + first name)";
$string['firstnamedesc'] = "User first name";
$string['lastnamedesc'] = "User last name";
$string['emaildesc'] = "User email address";
$string['citydesc'] = "User city";
$string['countrydesc'] = "User country";
$string['userimage'] = "User profile picture";
$string['idnumberdesc'] = "ID number as defined in user profile in the 'Optional' fields";
$string['institutiondesc'] = "User institution";
$string['departmentdesc'] = "User departement";
$string['phone1desc'] = "User phone";
$string['phone2desc'] = "User mobile phone";
$string['addressdesc'] = "User Address";
$string['profile_xxxx_desc'] = "User profile custom fields (can add / edit from admin > users > accounts > Profile fields), the XXX is the short name of the field and must be capitalized. <br> Example: PROFILE_ORGA for the 'My organization' field";

// strings notebook and result
$string['resulttitle'] = "Grades and outcome";
$string['gradesdesc'] = "Grade selected in 'Merge fields references and formats' options (class grade, no grade or grade of the selected activity)";
$string['activitygradesdesc'] = "List of all user grades on course activities";
$string['outcomedesc'] = "Displays the learner's outcome based on the settings in the course: <a href='https://docs.moodle.org/311/en/Outcomes'>Outcomes</a>";

// Strings Dates
$string['activitycompletiondesc'] = "Completion date of selected activity in 'Merge fields references and formats' options";
$string['deliverancedatedesc'] = "Certificate issue date";
$string['coursecompletiondesc'] = "Course completion date";
$string['coursefirstaccessdesc'] = "Date of first course access";
$string['courselastaccessdesc'] = "Date of last course access";
$string['enrolmentstartdesc'] = " Enrolment start date";
$string['enrolmentenddesc'] = "Enrolment end date";

// Strings Time
$string['moduletimetitle'] = "Time";
$string['usermoofactorytimedesc'] = "Time spent in hours and minutes according to moofactory course format option (logs, Intelliboard ou estimated)";

// Strigns Course attribution
$string['moduleattributiontitle'] = "In-course role and group";
$string['teachersdesc'] = "List of course contacts, as defined at the site administration level on the page /admin/settings.php?section=coursecontact";
$string['userroledesc'] = "User role in course";
$string['groupnamedesc'] = "User group list (display in line, separated by a comma)";

// String Certificate/Document Info
$string['moduleinfotitle'] = "Document information";
$string['certcodedesc'] = "Document unique code";

$string['formatnotfound'] = "Warning: Plugin 'moofactory course format' is neccessary";
$string['formatfound'] = "Plugin 'moofactory course format' is installed. You can fully use moofactory custom document. For more information, visit our site: <a href='https://moofactory.fr/' target='_blank'>moofactory.fr</a>";
$string['formatwarning'] = "Warning : To use the {USERMOOFACTORYTIME} merge field you need to install the 'Moofactory course format' plugin. More information about this plugin: <a href='https://moofactory.fr/' target='_blank'>moofactory.fr</a>";

// Validity.
$string['validity'] = 'Period of validity of the document, in months.';
$string['validity_help'] = 'Duration the document is valid.';
$string['defaultvalidity'] = 'Default period of validity of the document, in months.';
$string['renewalperiod'] = 'Document renewal time, in weeks.';
$string['renewalperiod_help'] = 'Period during which the user can renew his document. The start date is calculated according to the validity period. From this date, the user will again have to lift any restrictions for obtaining a new document.';
$string['defaultrenewalperiod'] = 'Default document renewal time, in weeks.';
$string['resetall'] = 'Reset all course activities.';
$string['resetall_help'] = 'If checked, all course activities will be reset from the start date of the renewal period (completion and grade). Otherwise, only activities subject to restrictions for obtaining a new document will be.';
$string['expired'] = ' <span class="expired">(expired)</span>';
$string['expiredtxt'] = ' (expired)';

$string['version_category'] = 'Version';
$string['courseversion'] = 'Course current version';

// cron task
$string['generaterestriction'] = 'Sending emails after restrictions are lifted';
