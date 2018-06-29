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

defined('MOODLE_INTERNAL') || die();

 /**
 * Intialise function of course lom
 */
function local_lom_initialise() {
    global $DB;
        
    $category = new stdClass();
    $new_field = new stdClass();

    $categories = array('general', 'lifecycle', 'metaMetadata', 'technical', 'educational', 'rights', 'relation', 'annotation', 'classification');

    $FORMAT_LANGSTR = 10;  // mark for lom langstring

    foreach($categories as $cat) {
        $records = $DB->get_records('local_metadata_category', ['contextlevel' => CONTEXT_COURSE, 'name' => $cat]);
        
        if (empty($records)) {
            $category->contextlevel = CONTEXT_COURSE;
            $category->name = $cat;
            $category->sortorder = $DB->count_records('local_metadata_category', ['contextlevel' => CONTEXT_COURSE]) + 1;
            $DB->insert_record('local_metadata_category', $category);  // add category
        }

        switch ($cat) {
            case 'general' :
                local_lom_add_field($cat, 'identifier', 'state', CONTEXT_COURSE, ['catalog', 'entry']);              
                local_lom_add_field($cat, 'title', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
                local_lom_add_field($cat, 'language', 'text', CONTEXT_COURSE);
                local_lom_add_field($cat, 'description', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
                local_lom_add_field($cat, 'keyword', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
/*
                local_lom_add_field($cat, 'coverage', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);*/
                local_lom_add_field($cat, 'structure', 'state', CONTEXT_COURSE, ['source', 'value']);
                local_lom_add_field($cat, 'aggregationLevel', 'text', CONTEXT_COURSE);

                break;
           
            case 'lifecycle' :
                local_lom_add_field($cat, 'version', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
                local_lom_add_field($cat, 'status', 'state', CONTEXT_COURSE, ['source', 'value']);
                local_lom_add_field($cat, 'contribute', 'state', CONTEXT_COURSE, ['role', 'entity', 'date']); // zl_temp to do, substruct
                break;
/*
            case 'metaMetadata' :
                local_lom_add_field($cat, 'identifier', 'state', CONTEXT_COURSE, ['catalog', 'entry']);
                local_lom_add_field($cat, 'contribute', 'state', CONTEXT_COURSE, ['role', 'entity', 'date']);  // zl_temp to do, substruct
              
                local_lom_add_field($cat, 'metadataSchema', 'text', CONTEXT_COURSE);

                local_lom_add_field($cat, 'language', 'text', CONTEXT_COURSE);
                break;
*/
            case 'technical' :
                local_lom_add_field($cat, 'format', 'text', CONTEXT_COURSE);
                local_lom_add_field($cat, 'size', 'text', CONTEXT_COURSE);
                local_lom_add_field($cat, 'location', 'text', CONTEXT_COURSE);
                //local_lom_add_field($cat, 'requirement', 'state', CONTEXT_COURSE, ['orComposite']);  // zl_temp to do, substruct
                // zl_temp, 'requirement' always generate subelement 'orComposite' (save one level)
                local_lom_add_field($cat, 'requirement', 'state', CONTEXT_COURSE, ['type', 'name', 'minimumVersion', 'maximumVersion']);  
/*
                local_lom_add_field($cat, 'installationRemarks', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
                local_lom_add_field($cat, 'otherplatformRequirements', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
                local_lom_add_field($cat, 'duration', 'text', CONTEXT_COURSE);
*/
                break;

            case 'educational' :
                local_lom_add_field($cat, 'interactivityType', 'state', CONTEXT_COURSE, ['source', 'value']);
                local_lom_add_field($cat, 'learningResourceType', 'state', CONTEXT_COURSE, ['source', 'value']);
/*              local_lom_add_field($cat, 'interactivityLevel', 'text', CONTEXT_COURSE);
                local_lom_add_field($cat, 'semanticDensity', 'text', CONTEXT_COURSE);*/
                local_lom_add_field($cat, 'intendedEndUserRole', 'state', CONTEXT_COURSE, ['source', 'value']);
                local_lom_add_field($cat, 'context', 'state', CONTEXT_COURSE, ['source', 'value']);
                local_lom_add_field($cat, 'typicalAgeRange', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
                local_lom_add_field($cat, 'difficulty', 'text', CONTEXT_COURSE);
                local_lom_add_field($cat, 'typicalLearningTime', 'text', CONTEXT_COURSE);
                local_lom_add_field($cat, 'description', 'textarea', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
/*                local_lom_add_field($cat, 'language', 'text', CONTEXT_COURSE);*/

                break;

            case 'rights' :
                local_lom_add_field($cat, 'cost', 'state', CONTEXT_COURSE, ['source', 'value']);
                local_lom_add_field($cat, 'copyrightAndOtherRestrictions', 'state', CONTEXT_COURSE, ['source', 'value']);
                
                local_lom_add_field($cat, 'description', 'menu', CONTEXT_COURSE);
                //local_lom_add_rights_descripion();  // special for field rights/description
                
                break;            

            case 'relation' :
                local_lom_add_field($cat, 'kind', 'state', CONTEXT_COURSE, ['source', 'value']);
/*                local_lom_add_field($cat, 'resource', 'state', CONTEXT_COURSE, ['identifier', 'description']);*/
                break;
/*
            case 'annotation' :
                local_lom_add_field($cat, 'entity', 'text', CONTEXT_COURSE);
                local_lom_add_field($cat, 'date', 'text', CONTEXT_COURSE);
                local_lom_add_field($cat, 'description', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
                break;
        
            case 'classification' :
                local_lom_add_field($cat, 'purpose', 'state', CONTEXT_COURSE, ['source', 'value']);
                local_lom_add_field($cat, 'taxonPath', 'state', CONTEXT_COURSE, ['source', 'taxon']); // zl_temp to do, substruct
                local_lom_add_field($cat, 'description', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);           
                local_lom_add_field($cat, 'keyword', 'text', CONTEXT_COURSE, null, $FORMAT_LANGSTR);
                break;
*/
            default:
                break;
        }

    }
    
}
 
 /**
 * Hook function to extend the course settings navigation.
 */
function local_lom_extend_navigation_course($parentnode, $course, $context) {
    
    if (has_capability('moodle/course:create', $context)) { 
        $strmetadata = get_string('oaipmhexport', 'local_lom');
        $url = new moodle_url('/local/lom/export.php', array('id' => $course->id));
        $metadatanode = navigation_node::create($strmetadata, $url, navigation_node::NODETYPE_LEAF,
             'lom', 'lom', new pix_icon('i/settings', $strmetadata));

        $parentnode->add_node($metadatanode);
    }
    
}
/**
 * Serves the files from the local lom areas
 *
 * @package  local_lom
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the workshop's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function local_lom_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false; 
    }
 
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'expectedfilearea' && $filearea !== 'anotherexpectedfilearea') {
        return false;
    }
 
    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);
 
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('moodle/course:view', $context)) {
        return false;
    }
 
    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.
 
    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.
 
    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }
 
    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_MYPLUGIN', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
 
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering. 
    // From Moodle 2.3, use send_stored_file instead.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * add an certain field in the database metadata_field
 *
 * @param $category category, $field, $datatype, $contextlev, $default
 * 
 */
function local_lom_add_field($category, $field, $datatype, $contextlev, $default = null, $format=FORMAT_HTML) {
    global $DB;
    $new_field = new stdClass();
    $dafault_str = '';

    $cats = $DB->get_records('local_metadata_category', ['contextlevel' => $contextlev, 'name' => $category]);   //get id

    if (empty($cats)) {
        return;
    }

    // convert to string
    if (!empty($default)) {
        $default_str = implode("\n", $default);    
    } 
    else {
        $default_str = $default;
    }
        
    foreach ($cats as $cat) {
        $id = $cat->id;
        $count = $DB->count_records('local_metadata_field', ['categoryid' => $id, 'contextlevel' => $contextlev]);

        $select = "name = '$field' AND categoryid = $id AND contextlevel = $contextlev";
        
        $result = $DB->get_records_select('local_metadata_field', $select);
    
        if (empty($result)) {

            $new_field->contextlevel = $contextlev;
            $new_field->shortname = $category .'_' .$field;
            $new_field->name = $field;
            $new_field->categoryid = $id;
            $new_field->datatype = $datatype;
            $new_field->sortorder = $count + 1;
            $new_field->visible = PROFILE_VISIBLE_ALL;
            $new_field->defaultdata = $default_str;
            $new_field->defaultdataformat = $format;
            
            if ($new_field->shortname == 'rights_description') { // zl_temp special init for menu
                $menu_entry[] = 'http://creativecommons.org/licenses/by/3.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-nd/3.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-nc-nd/3.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-nc/3.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-sa/3.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by/4.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-nd/4.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-nc-nd/4.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-nc/4.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-nc-sa/4.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/by-sa/4.0/';
                $menu_entry[] = 'http://creativecommons.org/licenses/publicdomain';
                $menu_entry[] = 'http://en.wikipedia.org/wiki/All_rights_reserved';
                $param1 = implode("\n", $menu_entry);
                $new_field->param1 = $param1;
            }
            $DB->insert_record('local_metadata_field', $new_field);
            
            // zl_temp: special handling for lifecycle_contribute, add a second field for 'publisher'
            if ($cat->name=='lifecycle' && $field=='contribute') {
                $new_field->contextlevel = $contextlev;
                $new_field->shortname = $category .'_' .$field .'_1';
                $new_field->name = $field;
                $new_field->categoryid = $id;
                $new_field->datatype = $datatype;
                $new_field->sortorder = $count + 1;
                $new_field->visible = PROFILE_VISIBLE_ALL;
                $new_field->defaultdata = $default_str;
                $new_field->defaultdataformat = $format;
                $DB->insert_record('local_metadata_field', $new_field);
            }
        }

    }
}

/**
 * set the start tag for category
 *
 * @param $categoryname, 
 * @return xml start-tag for category in $temp_tag 
 */
function local_lom_set_category_start($catname, &$temp_tag) {
    $output = '  <' .$catname . '>';
    $output .= "\r\n";
    $temp_tag = $output;
}

/**
 * set the close tag for category
 *
 * @param $categoryname
 * @return xml close-tag for category in $temp_tag 
 */
function local_lom_set_category_close($catname, &$temp_tag) {
    $add = '  </' .$catname . '>';
    $add .= "\r\n";
    $temp_tag .= $add;
}

/**
 * set the xml for the lom normal field
 *
 * @param $categoryname, $fieldname, $data
 * @return xml-wrapped metadata
 */
function local_lom_set_field($categoryname, $fieldname, $data) {
    $output = '    <' .$fieldname . '>';
    $output .= $data;
    $output .= '</' .$fieldname . '>';
    $output .= "\r\n";

    return $output;
}

/**
 * set the xml for lom fiel with data type langstring
 *
 * @param $categoryname, $fieldname, $data
 * @return xml-wrapped metadata
 */
function local_lom_set_field_langstring($categoryname, $fieldname, $data) {

    $output = '    <' .$fieldname . '>';
    $output .= "\r\n";

    $output .= '      <string language="de">' .$data . '</string>';
    $output .= "\r\n";

    $output .= '    </' .$fieldname . '>';
    $output .= "\r\n";

    return $output;
}

/**
 * set the xml for lom fiel with data type state
 *
 * @param $categoryname, $fieldname, $keys $value
 * @return xml-wrapped metadata
 */
function local_lom_set_field_state($fieldname, $keys, $data) {
    $index = 0;

    $output = '    <' .$fieldname . '>';
    $output .= "\r\n";
    $values = explode('|', $data);

    $fields = array();
    
    // zl_temp filter our those field with source=>'LOMv1.0 and value=>''
    // array fields is just built for this purpose. To handle nestet 'state' element, this is not enough
    for($i = 0; $i < sizeof($keys); $i++) {
        $fields[$keys[$i]] = $values[$i];
    }
    if (!empty($fields['source'])) {
        if (($fields['source'] == 'LOMv1.0') && ($fields['value'] == '')) {
            return '';
        }
    }
    
    // the nested 'state' element must be specially handled
    // zl_temp handle field 'requirement', insert additionally  an 'orComposite' subelement
    if ($fieldname == 'requirement') {
        $output .= '      <orComposite>';
        $output .= "\r\n";
    }
    
    foreach ($keys as $key) {
        // zl_temp, handle 4th level lifecycle_contribute_role
        if (($key=='type') || ($key=='name')){  // zl_temp these 2 fields under requirement/orComposite have no source/value in form. add here
            $output .= '        <' .$key . '>';
            $output .= "\r\n";
            $output .= '          <source>LOMv1.0</source>';
            $output .= "\r\n";
            $output .= '          <value>' .$values[$index]. '</value>';
            $output .= "\r\n";
            $output .= '        </' .$key . '>';
            $output .= "\r\n";
        }
        else if ($key=='role') { // zl_temp role special handling
            
            $output .= '      <' .$key . '>';
            $output .= "\r\n";
            if (!empty($values[0])) {
                $output .= '        <source>' .$values[0] .'</source>';
                $output .= "\r\n";
            }
            if (!empty($values[1])) {
                $output .= '        <value>' .$values[1] .'</value>';
                $output .= "\r\n";
            }
            $output .= '      </' .$key . '>';
            $output .= "\r\n";
            $index++;
        }
        // zl_temp datetime special handling
        elseif ($key=='date') {
            
            $printtime = strftime("%F", $values[$index]); // zl_temp index+1 because 'role' takes 2 position as an exception
            $output .= '      <' .$key . '>' .$printtime . '</' .$key . '>';
            $output .= "\r\n";
        }
        else { // normal case

            if (!empty($values[$index])){
                
                if ($key == 'source') {
                    if (!empty($values[$index+1])) {  // zl_temp avoid the default source 'LOMv1.0' with value empty 
                        $output .= '      <' .$key . '>' .$values[$index] . '</' .$key . '>';
                        $output .= "\r\n";
                    } 
                } else {
                    $output .= '      <' .$key . '>' .$values[$index] . '</' .$key . '>';
                    $output .= "\r\n";
                }
            }
        }
        $index++;
    }
    
    // zl_temp handle field 'requirement', insert additionally  an 'orComposite' subelement
    if ($fieldname == 'requirement') {
        $output .= '      </orComposite>';
        $output .= "\r\n";
    }

    $output .= '    </' .$fieldname . '>';
    $output .= "\r\n";

    
    return $output;
}

/**
 * Retrieve the metadata from database
 *
 * @param $courseid, $categoryname, $fieldname, $contextlev
 * @return data entry in the database local_metadata 
 */
function local_lom_get_field($courseid, $categoryname, $fieldname, $contextlev=CONTEXT_COURSE) {
    global $DB;
    $metadata = [];
    $results = '';

    $categoryid = $DB->get_field('local_metadata_category', 'id', ['name' => $categoryname]);
   
    // zl_temp handle duplicated fieldname

    $fields = $DB->get_records('local_metadata_field', ['contextlevel' => $contextlev, 'categoryid' => $categoryid]);
    if (!empty($fields)) {
        foreach ($fields as $field) {
            // fields can have multiple instance
            if (strpos($field->name, $fieldname)!== false) {
                $databaseentry = $DB->get_field('local_metadata', 'data', ['instanceid' => $courseid, 'fieldid' => $field->id]);
                $metadata[] = $databaseentry;
            }
        }
    }
                               
    return $metadata;
}

/**
 * Handle the metadata field with data-type 'state' 
 *
 * @param $courseid, $categoryname, $fieldname, $state_keys, $contextlev
 * @return data entry in the database for this field 
 */
function local_lom_handle_state_field($id, $cat, $fieldname, $state_keys, $contextlev, &$temp_tag) {
    $result = false;
    
    if (empty($datas = local_lom_get_field($id, $cat, $fieldname, $contextlev))) {
        return $result;
    } else {
        foreach ($datas as $data) {
            if (!empty($data)) {
                $text = '';
                $text = local_lom_set_field_state($fieldname, $state_keys, $data);
                if ($text) {
                    $result = true;
                }
                $temp_tag .= $text;
            }
        }

        return $result;
    }
}

/**
 * Handle the metadata field with data-type 'langstring' 
 *
 * @param $courseid, $categoryname, $fieldname, $contextlev
 * @return data entry in the database for this field 
 */
function local_lom_handle_langstring_field($id, $cat, $fieldname, $contextlev, &$temp_tag) {
    $result = false;
    
    if (empty($datas = local_lom_get_field($id, $cat, $fieldname, $contextlev))) {
        return $result;
    } else {
        // type 'langstring', nees special handling
        foreach ($datas as $data) {
            if (!empty($data)) {
                $text = local_lom_set_field_langstring($cat, $fieldname, $data);
                $temp_tag .= $text;
                $result = true;
            }
        }
        return $result;
    }
}

/**
 * Handle the metadata field with normal data type
 *
 * @param $courseid, $categoryname, $fieldname, $contextlev
 * @return data entry in the database for this field 
 */
function local_lom_handle_field($id, $cat, $fieldname, $contextlev, &$temp_tag) {
    $result = false;

    if (empty($datas = local_lom_get_field($id, $cat, $fieldname, $contextlev))) {
        return $result;
    } else {
        foreach ($datas as $data) {
            if (!empty($data)) {
                $text = local_lom_set_field($cat, $fieldname, $data);
                $temp_tag .= $text;
                $result = true;
            }
        }
        return $result;
    }
}


function local_lom_generate_xml($id) {

    $categories = array('general', 'lifecycle', 'metaMetadata', 'technical', 'educational', 'rights', 'relation', 'annotation', 'classification');

    $text = '<lom xmlns="http://ltsc.ieee.org/xsd/LOM" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ltsc.ieee.org/xsd/LOM http://ltsc.ieee.org/xsd/lomv1.0/lom.xsd">';
    $text .= "\r\n";

    // get metadata
    foreach($categories as $cat) {
        switch ($cat) {
            case 'general' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);
                if (local_lom_handle_state_field($id, $cat, 'identifier', ['catalog', 'entry'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'title', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'language', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'description', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'keyword', CONTEXT_COURSE, $temp)) { $cat_valid = true; }

                if (local_lom_handle_langstring_field($id, $cat, 'coverage', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'structure', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'aggregationLevel', CONTEXT_COURSE, $temp)) { $cat_valid = true; }

                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }
                break;

            case 'lifecycle' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);
                if (local_lom_handle_langstring_field($id, $cat, 'version', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'status', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'contribute', ['role', 'entity', 'date'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }
                break;

            case 'metaMetadata' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);

                if (local_lom_handle_state_field($id, $cat, 'identifier', ['catalog', 'entry'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'contribute', ['role', 'entity', 'date'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'metadataSchema', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'language', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }
                break;

            case 'technical' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);
                if (local_lom_handle_field($id, $cat, 'format', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'size', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'location', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'requirement', ['type', 'name', 'minimumVersion', 'maximumVersion'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }

                if (local_lom_handle_langstring_field($id, $cat, 'installationRemarks', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'otherplatformRequirements', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'duration', CONTEXT_COURSE, $temp)) { $cat_valid = true; }

                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }
                break;

            case 'educational' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);              
                if (local_lom_handle_state_field($id, $cat, 'interactivityType', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'learningResourceType', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }      

                if (local_lom_handle_field($id, $cat, 'interactivityLevel', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'semanticDensity', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'intendedEndUserRole', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'context', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'typicalAgeRange', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'difficulty', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'typicalLearningTime', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'description', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'language', CONTEXT_COURSE, $temp)) { $cat_valid = true; }

                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }
                break;

            case 'rights' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);
                if (local_lom_handle_state_field($id, $cat, 'cost', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'copyrightAndOtherRestrictions', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'description', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }

                break;

            case 'relation' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);
                if (local_lom_handle_state_field($id, $cat, 'kind', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'resource', ['identifier', 'description'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }
                break;
/*
            case 'annotation' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);
                if (local_lom_handle_field($id, $cat, 'entity', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_field($id, $cat, 'date', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'description', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }
                break;

            case 'classification' :
                $temp = '';
                $cat_valid = false;
                local_lom_set_category_start($cat, $temp);

                if (local_lom_handle_state_field($id, $cat, 'purpose', ['source', 'value'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_state_field($id, $cat, 'taxonPath', ['source', 'taxon'], CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'description', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if (local_lom_handle_langstring_field($id, $cat, 'keyword', CONTEXT_COURSE, $temp)) { $cat_valid = true; }
                if ($cat_valid) {
                    local_lom_set_category_close($cat, $temp);
                    $text .= $temp;
                }
                break;
*/
            default:
                break;
        }
    }

    $text .= '</lom>';

    return $text;

}

/**
 * delete all the records in local_metadata_category, local_metadata_field, local_metadata
 */
function local_lom_clearall() {
    global $DB;

    $DB->delete_records('local_metadata_field');
    $DB->delete_records('local_metadata');
    $DB->delete_records('local_metadata_category');

}