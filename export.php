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

//defined('MOODLE_INTERNAL') || die();
require_once('../../config.php');
//require_once($CFG->dirroot.'/local/lom/classes/export_form.php');
require_once($CFG->dirroot.'/local/lom/export_form.php');

global $PAGE, $OUTPUT, $DB;

$id = required_param('id', PARAM_INT);

$PAGE->set_url($CFG->wwwroot.'/local/lom/export.php', array('id' => $id));
$PAGE->set_context(context_course::instance($id));
$title = get_string('oaipmhexport', 'local_lom');

$PAGE->set_title($title);
$PAGE->set_heading($title);

require_login($id);

echo $OUTPUT->header();

$coursename = $DB->get_field('course', 'fullname', ['id' => $id]);

echo $OUTPUT->heading('Generate LOM metadata export file for course '.'"'.$coursename.'"' .'. This file will be harvested through oai-pmh. Are you ready to export your course metadata?');

$mform = new export_form('/local/lom/xml_output.php', array('id'=>$id));

 
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    
  echo "xml file generated";
        
  //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.
 
  //Set default data (if any)
  //$mform->set_data($toform);
  //displays the form

  $mform->set_data(array('id' => $id));
  $mform->display();
}

echo $OUTPUT->footer();
