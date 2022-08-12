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
 * Code fragment to define the version of the customdocument module
 *
 * @package    mod
 * @subpackage customdocument
 * @author     Carlos Alexandre S. da Fonseca
 * @copyright  2013 - Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */


defined('MOODLE_INTERNAL') || die();
$plugin->version  = 2022081200;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2019111800;  // Requires this Moodle version (moodle 3.8.x).
$plugin->cron     = 4 * 3600;    // Period for cron to check this module (secs).
$plugin->component = 'mod_customdocument';
// $plugin->dependencies = array('block_moofactory_estimated_time' => 2021040900);

$plugin->release  = '2.2.21';     // Human-friendly version name.
// MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE.
$plugin->maturity = MATURITY_STABLE;