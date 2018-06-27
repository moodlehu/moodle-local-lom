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
 * @package local_lom
 * @copyright Zhifen Lin 2018 project OER Humboldt University Berlin 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/lom/lib.php');
global $PAGE, $OUTPUT, $DB;

$id   = optional_param('id', '', PARAM_INT);


$PAGE->set_url($CFG->wwwroot.'/local/lom/reset.php');
$PAGE->set_context(context_system::instance());
$title = get_string('pluginname', 'local_lom');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

local_lom_clearall();

echo get_string('all metadata records are deleted', 'local_lom');

echo '<br>';
echo '<br>';

$back = get_string('back', 'local_lom');
$returnbutton_html = '<input type="button" value="' .$back .'" onclick="location=\'/local/metadata/index.php?contextlevel=50\'" />';
echo $returnbutton_html;

echo $OUTPUT->footer();
die;
