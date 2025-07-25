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
 * Defines the version details.
 *
 * @package     mod_customdocument
 * @copyright   2024 Patrick ROCHET <patrick.r@lmsfactory.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
$plugin->version  = 2025071500;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2019111800;  // Requires this Moodle version (moodle 3.8.x).
$plugin->cron     = 4 * 3600;    // Period for cron to check this module (secs).
$plugin->component = 'mod_customdocument';
$plugin->release  = '3.8';     // Human-friendly version name.
$plugin->maturity = MATURITY_STABLE;