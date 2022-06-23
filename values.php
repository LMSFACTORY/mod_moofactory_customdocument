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
 * This page lists all the instances of certificate in a particular course
 *
 * @package    mod
 * @subpackage customdocument
 * @copyright  Abbas Mohed aka theCodeman
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL');
require_once('../../config.php');
require_once('lib.php');
global $CFG, $PAGE;

$PAGE->requires->css('/mod/customdocument/styles/style.css');
require_once($CFG->libdir . '/moodlelib.php');

// Requires a login.
require_course_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/customdocument/values.php');
$PAGE->requires->js('/mod/customdocument/utils.js');
echo $OUTPUT->header();
?>

<body>
    <div class="container" id="champs_table_container">
        <h1 class="text-center"><?php echo get_string('moduledoctitle', 'customdocument') ?></h1>
        <table class="table table-striped table-bordered " id="table-course">
            <h2><?php echo get_string('modulecoursetitle', 'customdocument');?></h2>
            <tr>
                <th scope="col"><?php echo get_string('thnom', 'customdocument'); ?></th>
                <th scope="col"><?php echo get_string('thdescription', 'customdocument');?></th>
            </tr>
            <tbody>
                <!-- Début de Champs de cours -->
                <tr>
                    <td>{COURSENAME}</td>
                    <td>
                        <?php echo get_string('coursenamedesc', 'customdocument') ;?>
                    </td>
                </tr>
                <tr>
                    <td>{COURSESTARTDATE}</td>
                    <td><?php echo get_string('coursestartdatedesc', 'customdocument') ;?></td>
                </tr>
                <tr>
                    <td>{COURSEENDDATE}</td>
                    <td><?php echo get_string('courseenddatedesc', 'customdocument') ;?></td>
                </tr>
                <!-- Fin de Champs de cours -->

            </tbody>
        </table>

        <table class="table table-striped table-bordered " id="table-user-profile">
            <h2><?php echo get_string('userprofilefield', 'customdocument') ?></h2>
            <tr>

                <th scope="col"><?php echo get_string('thnom', 'customdocument'); ?></th>
                <th scope="col"><?php echo get_string('thdescription', 'customdocument');?></th>
            </tr>
            <tbody>
                <!-- Début de Champs de profil de l'utilisateur -->
                <tr>

                    <td>{FULLNAME}</td>
                    <td><?php echo get_string('usernamedesc', 'customdocument') ?></td>
                </tr>

                <tr>
                    <td>{FIRSTNAME}</td>
                    <td><?php echo get_string('firstnamedesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{LASTNAME}</td>
                    <td><?php echo get_string('lastnamedesc', 'customdocument') ?></td>
                </tr>

                <tr>
                    <td>{EMAIL}</td>
                    <td><?php echo get_string('emaildesc', 'customdocument') ?></td>
                </tr>

                <tr>
                    <td>{CITY}</td>
                    <td><?php echo get_string('citydesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{COUNTRY}</td>
                    <td><?php echo get_string('countrydesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{USERIMAGE}</td>
                    <td><?php echo get_string('userimage', 'customdocument') ?></td>
                </tr>

                <tr>
                    <td>{IDNUMBER}</td>
                    <td><?php echo get_string('idnumberdesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{INSTITUTION}</td>
                    <td><?php echo get_string('institutiondesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{DEPARTMENT}</td>
                    <td><?php echo get_string('departmentdesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{PHONE1}</td>
                    <td><?php echo get_string('phone1desc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{PHONE2}</td>
                    <td><?php echo get_string('phone2desc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{ADDRESS}</td>
                    <td><?php echo get_string('addressdesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{PROFILE_XXX}</td>
                    <td>
                        <?php echo get_string('profile_xxxx_desc', 'customdocument') ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="table table-striped table-bordered " id="table-carnet">
            <h2><?php echo get_string('resulttitle', 'customdocument')?></h2>
            <tr>

                <th scope="col"><?php echo get_string('thnom', 'customdocument'); ?></th>
                <th scope="col"><?php echo get_string('thdescription', 'customdocument');?></th>
            </tr>
            <tbody>
                <tr>

                    <td>{GRADE}</td>
                    <td><?php echo get_string('gradesdesc', 'customdocument')?></td>
                </tr>
                <tr>
                    <td>{ACTIVITYGRADES}</td>
                    <td><?php echo get_string('activitygradesdesc', 'customdocument')?></td>
                </tr>
                <tr>
                    <td>{OUTCOME}</td>
                    <td>
                        <?php echo get_string('outcomedesc', 'customdocument'); ?>
                    </td>

                </tr>
            </tbody>
        </table>

        <table class="table table-striped table-bordered" id="table-dates">
            <h2>Dates</h2>
            <tr>
                <th scope="col"><?php echo get_string('thnom', 'customdocument'); ?></th>
                <th scope="col"><?php echo get_string('thdescription', 'customdocument');?></th>
            </tr>

            <tbody>
                <tr>
                    <td>{ACTIVITYCOMPLETIONDATE}</td>
                    <td><?php echo get_string('activitycompletiondesc', 'customdocument'); ?></td>
                </tr>
                <tr>
                    <td>{DELIVERANCEDATE}</td>
                    <td><?php echo get_string('deliverancedatedesc', 'customdocument'); ?></td>
                </tr>
                <tr>
                    <td>{COURSECOMPLETIONDATE}</td>
                    <td><?php echo get_string('coursecompletiondesc', 'customdocument'); ?></td>
                </tr>
                <tr>
                    <td>{COURSEENDDATE}</td>
                    <td><?php echo get_string('courseenddatedesc', 'customdocument'); ?></td>
                </tr>
                <tr>
                    <td>{COURSESTARTDATE}</td>
                    <td><?php echo get_string('coursestartdatedesc', 'customdocument'); ?></td>
                </tr>
                <tr>
                    <td>{COURSEFIRSTACCESS}</td>
                    <td><?php echo get_string('coursefirstaccessdesc', 'customdocument'); ?></td>
                </tr>
                <tr>
                    <td>{COURSELASTACCESS}</td>
                    <td><?php echo get_string('courselastaccessdesc', 'customdocument'); ?></td>
                </tr>
                <tr>
                    <td>{ENROLMENTTIMESTART}</td>
                    <td><?php echo get_string('enrolmentstartdesc', 'customdocument'); ?></td>
                </tr>
                <tr>
                    <td>{ENROLMENTTIMEEND}</td>
                    <td><?php echo get_string('enrolmentenddesc', 'customdocument'); ?></td>
                </tr>
            </tbody>
        </table>

        <table class="table table-striped table-bordered " id="table-time">
            <h2><?php echo get_string('moduletimetitle', 'customdocument'); ?></h2>
            <tr>
                <th scope="col"><?php echo get_string('thnom', 'customdocument'); ?></th>
                <th scope="col"><?php echo get_string('thdescription', 'customdocument');?></th>
            </tr>
            <tbody>
                <tr>
                    <td rowspan="2">{USERMOOFACTORYTIME}</td>
                    <td><?php echo get_string('usermoofactorytimedesc', 'customdocument');?></td>
                </tr>
            </tbody>

        </table>
        <div>
            <span>

                <?php if(!array_key_exists('moofactory', \core_component::get_plugin_list('format'))){
                            echo '<div class="alert alert-warning fade-in pt-3">'. get_string('formatwarning', 'customdocument') .'</div>';
                        }else{
                            echo '<div class="alert alert-info fade-in pt-3">'. get_string('formatfound', 'customdocument') .'</div>';
                        }
                        ?>
            </span>
        </div>
        <table class="table table-striped table-bordered " id="table-attribution">
            <h2><?php echo get_string('moduleattributiontitle', 'customdocument') ?></h2>
            <tr>
                <th scope="col"><?php echo get_string('thnom', 'customdocument'); ?></th>
                <th scope="col"><?php echo get_string('thdescription', 'customdocument');?></th>
            </tr>
            <tbody>
                <tr>
                    <td>{TEACHERS}</td>
                    <td><?php echo get_string('teachersdesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{USERROLENAME}</td>
                    <td><?php echo get_string('userroledesc', 'customdocument') ?></td>
                </tr>
                <tr>
                    <td>{GROUPNAMES}</td>
                    <td><?php echo get_string('groupnamedesc', 'customdocument') ?></td>
                </tr>
            </tbody>
        </table>

        <table class="table table-striped table-bordered  " id="table-document-info">
            <h2><?php echo get_string('moduleinfotitle', 'customdocument') ?></h2>
            <tr>
                <th scope="col"><?php echo get_string('thnom', 'customdocument'); ?></th>
                <th scope="col"><?php echo get_string('thdescription', 'customdocument');?></th>
            </tr>
            <tbody>
                <tr>
                    <td>{CERTIFICATECODE}</td>
                    <td><?php echo get_string('certcodedesc', 'customdocument') ?></td>
                </tr>

            </tbody>
        </table>
    </div>
</body>
<?php
echo $OUTPUT->footer();