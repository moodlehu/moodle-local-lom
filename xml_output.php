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
require_once($CFG->dirroot.'/local/lom/lib.php');
global $PAGE, $OUTPUT, $DB;

$id = required_param('id', PARAM_INT);

$PAGE->set_url($CFG->wwwroot.'/local/lom/xml_output.php', array('id' => $id));
$PAGE->set_context(context_course::instance($id));

require_login($id);

$coursename = $DB->get_field('course', 'fullname', ['id' => $id]);

$filename = 'OER-' . $coursename;

$PAGE->set_heading($filename);
//echo $OUTPUT->header();

//echo "<br>";

$text = local_lom_generate_xml($id);

// handle file
$fs = get_file_storage();
$context = get_system_context();

// Prepare file record object
$fileinfo = array(
    'contextid' => $context->id, // ID of context
    'component' => 'local_lom',     // usually = table name
    'filearea' => 'myarea',     // usually = table name
    'itemid' => 0,               // usually = ID of row in table
    'filepath' => '/temp/oai/',           // any path beginning and ending in /
    'filename' => $filename); // any filename
 
// Get file
$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                      $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

if ($file) {// already exist delete the old
    $file->delete();
}

// create file
$file = $fs->create_file_from_string($fileinfo, $text);

// Read contents
echo $OUTPUT->header();
echo "<br>";

if ($file) {
    $contents = $file->get_content();

    // for echo the output 
    $content_output = str_replace(" ", "&nbsp;", htmlspecialchars($contents));
    echo nl2br($content_output);

} else {
    // file doesn't exist - do something
}

echo $OUTPUT->footer();


die;