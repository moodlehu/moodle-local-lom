<?php
require_once('oai2exception.php');
require_once('oai2xml.php');
//require_once('../lib.php');
/**
 * This is an implementation of OAI Data Provider version 2.0.
 * @see http://www.openarchives.org/OAI/2.0/openarchivesprotocol.htm
 */
class OAI2Server {

    public $errors = array();
    private $args = array();
    private $verb = '';
    private $token_prefix = '/tmp/oai_pmh-';
    private $token_valid = 86400;

    function __construct($uri, $args, $identifyResponse, $callbacks) {

        $this->uri = $uri;

        if (!isset($args['verb']) || empty($args['verb'])) {
            $this->errors[] = new OAI2Exception('badVerb');
        } else {
            $verbs = array('Identify', 'ListMetadataFormats', 'ListSets', 'ListIdentifiers', 'ListRecords', 'GetRecord');
            if (in_array($args['verb'], $verbs)) {

                $this->verb = $args['verb'];

                unset($args['verb']);

                $this->args = $args;

                $this->identifyResponse = $identifyResponse;

                $this->listMetadataFormatsCallback = $callbacks['ListMetadataFormats'];
                $this->listSetsCallback = $callbacks['ListSets'];
                $this->listRecordsCallback = $callbacks['ListRecords'];
                $this->getRecordCallback = $callbacks['GetRecord'];

                $this->response = new OAI2XMLResponse($this->uri, $this->verb, $this->args);

                call_user_func(array($this, $this->verb));

            } else {
                $this->errors[] = new OAI2Exception('badVerb');
            }
        }

    }

    public function response() {
        if (empty($this->errors)) {
            return $this->response->doc;
        } else {
            $errorResponse = new OAI2XMLResponse($this->uri, $this->verb, $this->args);
            $oai_node = $errorResponse->doc->documentElement;
            foreach($this->errors as $e) {
                $node = $errorResponse->addChild($oai_node,"error",$e->getMessage());
                $node->setAttribute("code",$e->getOAI2Code());
            }
            return $errorResponse->doc;
        }
    }

    public function Identify() {

        if (count($this->args) > 0) {
            foreach($this->args as $key => $val) {
                $this->errors[] = new OAI2Exception('badArgument');
            }
        } else {
            foreach($this->identifyResponse as $key => $val) {
                $this->response->addToVerbNode($key, $val);
            }
        }
    }

    public function ListMetadataFormats() {

        foreach ($this->args as $argument => $value) {
            if ($argument != 'identifier') {
                $this->errors[] = new OAI2Exception('badArgument');
            }
        }
        if (isset($this->args['identifier'])) {
            $identifier = $this->args['identifier'];
        } else {
            $identifier = '';
        }
        if (empty($this->errors)) {
            try {
                if ($formats = call_user_func($this->listMetadataFormatsCallback, $identifier)) {
                    foreach($formats as $key => $val) {
                        $cmf = $this->response->addToVerbNode("metadataFormat");
                        $this->response->addChild($cmf,'metadataPrefix',$key);
                        $this->response->addChild($cmf,'schema',$val['schema']);
                        $this->response->addChild($cmf,'metadataNamespace',$val['metadataNamespace']);
                    }
                } else {
                    $this->errors[] = new OAI2Exception('noMetadataFormats');
                }
            } catch (OAI2Exception $e) {
                $this->errors[] = $e;
            }
        }
    }

    public function ListSets() {

        if (isset($this->args['resumptionToken'])) {
            if (count($this->args) > 1) {
                $this->errors[] = new OAI2Exception('badArgument');
            } else {
                if ((int)$val+$this->token_valid < time()) {
                    $this->errors[] = new OAI2Exception('badResumptionToken');
                }
            }
            $resumptionToken = $this->args['resumptionToken'];
        } else {
            $resumptionToken = null;
        }
        if (empty($this->errors)) {
            if ($sets = call_user_func($this->listSetsCallback, $resumptionToken)) {

                foreach($sets as $set) {

                    $setNode = $this->response->addToVerbNode("set");

                    foreach($set as $key => $val) {
                        if($key=='setDescription') {
                            $desNode = $this->response->addChild($setNode,$key);
                            $des = $this->response->doc->createDocumentFragment();
                            $des->appendXML($val);
                            $desNode->appendChild($des);
                        } else {
                            $this->response->addChild($setNode,$key,$val);
                        }
                    }
                }
            } else {
                $this->errors[] = new OAI2Exception('noSetHierarchy');
            }
        }
    }

    public function GetRecord() {

        if (!isset($this->args['metadataPrefix'])) {
            $this->errors[] = new OAI2Exception('badArgument');
        } else {
            $metadataFormats = call_user_func($this->listMetadataFormatsCallback);
            if (!isset($metadataFormats[$this->args['metadataPrefix']])) {
                $this->errors[] = new OAI2Exception('cannotDisseminateFormat');
            }
        }
        if (!isset($this->args['identifier'])) {
            $this->errors[] = new OAI2Exception('badArgument');
        }

        if (empty($this->errors)) {
            try {
                if ($record = call_user_func($this->getRecordCallback, $this->args['identifier'], $this->args['metadataPrefix'])) {

                    $identifier = $record['identifier'];

                    $datestamp = $this->formatDatestamp($record['datestamp']);

                    $set = $record['set'];

                    $status_deleted = (isset($record['deleted']) && ($record['deleted'] == 'true') &&
                                       (($this->identifyResponse['deletedRecord'] == 'transient') ||
                                        ($this->identifyResponse['deletedRecord'] == 'persistent')));

                    $cur_record = $this->response->addToVerbNode('record');
                    $cur_header = $this->response->createHeader($identifier, $datestamp, $set, $cur_record);
                    if ($status_deleted) {
                        $cur_header->setAttribute("status","deleted");
                    } else {
                        if ($this->args['metadataPrefix'] == 'lom') {
                            $this->add_metadata_lom($cur_record, $record);
                        } 
                        else if ($this->args['metadataPrefix'] == 'oai_dc') {
                            $this->add_metadata_dc($cur_record, $record);
                        }
                    }
                } else {
                    $this->errors[] = new OAI2Exception('idDoesNotExist');
                }
            } catch (OAI2Exception $e) {
                $this->errors[] = $e;
            }
        }
    }

    public function ListIdentifiers() {
        $this->ListRecords();
    }

    public function ListRecords() {

        $maxItems = 1000;
        $deliveredRecords = 0;
        $metadataPrefix = $this->args['metadataPrefix'];
        $from = isset($this->args['from']) ? $this->args['from'] : '';
        $until = isset($this->args['until']) ? $this->args['until'] : '';
        $set = isset($this->args['set']) ? $this->args['set'] : '';

        if (isset($this->args['resumptionToken'])) {
            if (count($this->args) > 1) {
                $this->errors[] = new OAI2Exception('badArgument');
            } else {
                if ((int)$val+$this->token_valid < time()) {
                    $this->errors[] = new OAI2Exception('badResumptionToken');
                } else {
                    if (!file_exists($this->token_prefix.$this->args['resumptionToken'])) {
                        $this->errors[] = new OAI2Exception('badResumptionToken');
                    } else {
                        if ($readings = $this->readResumptionToken($this->token_prefix.$this->args['resumptionToken'])) {
                            list($deliveredRecords, $metadataPrefix, $from, $until, $set) = $readings;
                        } else {
                            $this->errors[] = new OAI2Exception('badResumptionToken');
                        }
                    }
                }
            }
        } else {
            if (!isset($this->args['metadataPrefix'])) {
                $this->errors[] = new OAI2Exception('badArgument');
            } else {
                $metadataFormats = call_user_func($this->listMetadataFormatsCallback);
                if (!isset($metadataFormats[$this->args['metadataPrefix']])) {
                    $this->errors[] = new OAI2Exception('cannotDisseminateFormat');
                }
            }
            if (isset($this->args['from'])) {
                if(!$this->checkDateFormat($this->args['from'])) {
                    $this->errors[] = new OAI2Exception('badArgument');
                }
            }
            if (isset($this->args['until'])) {
                if(!$this->checkDateFormat($this->args['until'])) {
                    $this->errors[] = new OAI2Exception('badArgument');
                }
            }
        }

        if (empty($this->errors)) {
            try {

                $records_count = call_user_func($this->listRecordsCallback, $metadataPrefix, $from, $until, $set, true);

                $records = call_user_func($this->listRecordsCallback, $metadataPrefix, $from, $until, $set, false, $deliveredRecords, $maxItems);

                foreach ($records as $record) {

                    $identifier = $record['identifier'];
                    $datestamp = $this->formatDatestamp($record['datestamp']);
                    $setspec = $record['set'];

                    $status_deleted = (isset($record['deleted']) && ($record['deleted'] === true) &&
                                        (($this->identifyResponse['deletedRecord'] == 'transient') ||
                                         ($this->identifyResponse['deletedRecord'] == 'persistent')));

                    if($this->verb == 'ListRecords') {
                        $cur_record = $this->response->addToVerbNode('record');
                        $cur_header = $this->response->createHeader($identifier, $datestamp,$setspec,$cur_record);
                        if (!$status_deleted) {
                            
                            if ($metadataPrefix =='lom') {
                                $this->add_metadata_lom($cur_record, $record);
                            }
                            else if ($metadataPrefix =='oai_dc') {
                                $this->add_metadata_dc($cur_record, $record);
                            }
                        }	
                    } else { // for ListIdentifiers, only identifiers will be returned.
                        $cur_header = $this->response->createHeader($identifier, $datestamp,$setspec);
                    }
                    if ($status_deleted) {
                        $cur_header->setAttribute("status","deleted");
                    }
                }

                // Will we need a new ResumptionToken?
                if ($records_count - $deliveredRecords > $maxItems) {

                    $deliveredRecords +=  $maxItems;
                    $restoken = $this->createResumptionToken($deliveredRecords);

                    $expirationDatetime = gmstrftime('%Y-%m-%dT%TZ', time()+$this->token_valid);	

                } elseif (isset($args['resumptionToken'])) {
                    // Last delivery, return empty ResumptionToken
                    $restoken = null;
                    $expirationDatetime = null;
                }

                if (isset($restoken)) {
                    $this->response->createResumptionToken($restoken,$expirationDatetime,$records_count,$deliveredRecords);
                }

            } catch (OAI2Exception $e) {
                $this->errors[] = $e;
            }
        }
    }

    private function add_metadata_lom($cur_record, $record) {
        global $DB;
        $temp = '';
        $valid_cats = array();

        $meta_node =  $this->response->addChild($cur_record ,"metadata");

        $schema_node = $this->response->addChild($meta_node, $record['metadata']['container_name']);
        
        foreach ($record['metadata']['container_attributes'] as $name => $value) {
            $schema_node->setAttribute($name, $value);
        }

        // zl_temp ab now get the course metadata and build DOM element
        $courseid = $record['metadata']['fields'];
        
        $lom_categories = array('general', 'lifecycle', 'metaMetadata', 'technical', 'educational', 'rights', 'relation', 'annotation', 'classification');
        
        // check whether they are in metadata datebase defined
        foreach($lom_categories as $lom_cat) {
            $records = $DB->get_records('local_metadata_category', ['contextlevel' => CONTEXT_COURSE, 'name' => $lom_cat]);
            if (!empty($records)) {
                $valid_cats[] = $lom_cat;
            }
        }

        foreach($valid_cats as $cat) {
            switch ($cat) {
                case 'general':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);

                    if (local_lom_handle_state_field($courseid, $cat, 'identifier', ['catalog', 'entry'], CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $idents = explode("</identifier>", $temp);
                        foreach ($idents as $ident) {
                            $ident_trim = trim($ident, " \t\n\r\0\x0B");
                            if (!empty(trim($ident_trim))) {
                                $result = strip_tags($ident_trim, '<catalog><catalog/><entry><entry/>');
                                if (!empty($result)) {
                                    $container_node = $this->response->addChild($cat_node, 'identifier');
                                    $this->generate_sub_node_two($result, $container_node, ['catalog', 'entry']);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
        
                    if (local_lom_handle_langstring_field($courseid, $cat, 'title', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<string><string/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'title', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'language', CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $langs = explode("</language>", $temp);
                        foreach ($langs as $lang) {
                            $lang_trim = trim($lang, " \t\n\r\0\x0B");
                            if (!empty($lang_trim)) {
                                $result = strip_tags($lang_trim);
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'language', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'description', CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $descs = explode("</description>", $temp);
                        foreach ($descs as $desc) {
                            $desc_trim = trim($desc, " \t\n\r\0\x0B");
                            if (!empty($desc_trim)) {
                                $result = strip_tags($desc_trim, '<string><string/>');
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'description', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'keyword', CONTEXT_COURSE, $temp))  {
                        // zl_temp handle more field instance
                        $keywords = explode("</keyword>", $temp);
                        foreach ($keywords as $keyword) {
                            $keyword_trim = trim($keyword, " \t\n\r\0\x0B");
                            if (!empty($keyword_trim)) {
                                $result = strip_tags($keyword_trim, '<string><string/>');
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'keyword', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'coverage', CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $coverages = explode("</coverage>", $temp);
                        foreach ($coverages as $coverage) {
                            $coverage_trim = trim($coverage, " \t\n\r\0\x0B");
                            if (!empty($coverage_trim)) {
                                $result = strip_tags($coverage_trim, '<string><string/>');
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'coverage', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'structure', ['source', 'value'], CONTEXT_COURSE, $temp)) { 
                        $result = trim(strip_tags($temp, '<source><source/><value><value/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'structure');
                            $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'aggregationLevel', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'aggregationLevel', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    break;
                    
                case 'lifecycle':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'version', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<string><string/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'version', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                
                    if (local_lom_handle_state_field($courseid, $cat, 'status', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<source><source/><value><value/>')," \t\n\r\0\x0B");
                        
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'status');
                            $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                
                    if (local_lom_handle_state_field($courseid, $cat, 'contribute', ['role', 'entity', 'date'], CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $contribs = explode("</contribute>", $temp);
                        foreach ($contribs as $contrib)
                        {
                            $contrib_trim = trim($contrib, " \t\n\r\0\x0B");
                            if (!empty($contrib_trim)) {
                                $result = strip_tags($contrib_trim, '<role><role/><entity><entity/><date><date/><source></source><value></value>');
                                $container_node = $this->response->addChild($cat_node, 'contribute');
                                $this->generate_sub_node_three($result, $container_node, ['role', 'entity', 'date']);
                                $cat_valid = true;
                            }
                        }
                        $temp='';
                    }
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    break;
                     
                case 'metaMetadata':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'identifier', ['catalog', 'entry'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<catalog><catalog/><entry><entry/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'identifier');
                            $this->generate_sub_node_two($result, $container_node, ['catalog', 'entry']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'contribute', ['role', 'entity', 'date'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<role><role/><entity><entity/><date><date/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'contribute');
                            $this->generate_sub_node_three($result, $container_node, ['role', 'entity', 'date']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }

                    if (local_lom_handle_field($courseid, $cat, 'metadataSchema', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'metadataSchema', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'language', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'language', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    break;
                    
                case 'technical':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);
                    
                    if (local_lom_handle_field($courseid, $cat, 'format', CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $formats = explode("</format>", $temp);
                        foreach ($formats as $format) {
                            $format_trim = trim($format, " \t\n\r\0\x0B");
                            if (!empty($format_trim)) {
                                $result = strip_tags($format_trim);
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'format', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'size', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'size', $result);
                            $cat_valid = true;
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'location', CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $locations = explode("</location>", $temp);
                        foreach ($locations as $location) {
                            $location_trim = trim($location, " \t\n\r\0\x0B");
                            if (!empty($location_trim)) {
                                $result = strip_tags($location_trim);
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'location', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'requirement', ['type', 'name', 'minimumVersion', 'maximumVersion'], CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $requirements = explode("</requirement>", $temp);
                        foreach ($requirements as $requirement) {
                            
                            $requirement_trim = trim($requirement, " \t\n\r\0\x0B");
                            
                            if (!empty($requirement_trim)) {
                                $req_node = $this->response->addChild($cat_node, 'requirement', '');
                                $com_node = $this->response->addChild($req_node, 'orComposite', '');

                                $result = strip_tags($requirement_trim, '<source><source/><value><value/><type><type/><name><name/><minimumVersion><minimumVersion/><maximumVersion><maximumVersion/>');
                        
                                //type
                                $part1 = strstr($result, '</type>', true);
                                $part2 = strstr($part1, '<value>');
                                $type = trim(strip_tags($part2),  " \t\n\r\0\x0B");

                                if (!empty($type)) {
                                    $type_node = $this->response->addChild($com_node, 'type', '');
                                    $this->response->addChild($type_node, 'source', 'LOMv1.0');
                                    $this->response->addChild($type_node, 'value', $type);
                                }

                                //name
                                $after_name = strstr($result, '<name>');
                                $part1 = strstr($after_name, '</name>', true);
                                $part2 = strstr($part1, '<value>');
                                $name = trim(strip_tags($part2),  " \t\n\r\0\x0B");

                                if (!empty($name)) {
                                    $type_node = $this->response->addChild($com_node, 'name', '');
                                    $this->response->addChild($type_node, 'source', 'LOMv1.0');
                                    $this->response->addChild($type_node, 'value', $name);
                                }
                                
                                //minimumVersion
                            
                                $after_mini = strstr($result, '<minimumVersion>');
                                $before_mini = strstr($after_mini, '</minimumVersion>', true);
                                $mini = substr($before_mini, 16);
                                if (!empty($mini)) {
                                    $this->response->addChild($com_node, 'minimumVersion', $mini);
                                }
                            
                                //maxmumVersion
                                $after_maxi = strstr($result, '<maximumVersion>');
                                $before_max = strstr($after_maxi, '</maximumVersion>', true);
                                $maxi = substr($before_max, 16);
                                if (!empty($maxi)) {
                                    $this->response->addChild($com_node, 'maximumVersion', $maxi);
                                }

                                $cat_valid = true;
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'installationRemarks', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<string><string/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'installationRemarks', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'otherplatformRequirements', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<string><string/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'otherplatformRequirements', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'duration', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'duration', $result);
                            $cat_valid = true;
                        }
                        $temp=''; 
                    }
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    break;
                    
                case 'educational':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'interactivityType', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<source><source/><value><value/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'interactivityType');
                            $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }

                    if (local_lom_handle_state_field($courseid, $cat, 'learningResourceType', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $res_types = explode("</learningResourceType>", $temp);
                        foreach ($res_types as $res_type) {
                            $res_type_trim = trim($res_type, " \t\n\r\0\x0B");
                            if (!empty($res_type_trim)) {
                                $result = strip_tags($res_type_trim, '<source><source/><value><value/>');
                                if (!empty($result)) {
                                    $container_node = $this->response->addChild($cat_node, 'learningResourceType');
                                    $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'interactivityLevel', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'interactivityLevel', $result);
                            $cat_valid = true;
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'semanticDensity', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'semanticDensity', $result);
                            $cat_valid = true;
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'intendedEndUserRole', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $user_roles = explode("</intendedEndUserRole>", $temp);
                        foreach ($user_roles as $user_role) {
                            $user_role_trim = trim($user_role, " \t\n\r\0\x0B");
                            if (!empty($user_role_trim)) {
                                $result = strip_tags($user_role_trim, '<source><source/><value><value/>');
                                if (!empty($result)) {
                                    $container_node = $this->response->addChild($cat_node, 'intendedEndUserRole');
                                    $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'context', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $contexts = explode("</context>", $temp);
                        foreach ($contexts as $context) {
                            $context_trim = trim($context, " \t\n\r\0\x0B");
                            if (!empty($context_trim)) {
                                $result = strip_tags($context_trim, '<source><source/><value><value/>');
                                if (!empty($result)) {
                                    $container_node = $this->response->addChild($cat_node, 'context');
                                    $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'typicalAgeRange', CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $ages = explode("</typicalAgeRange>", $temp);
                        foreach ($ages as $age) {
                            $age_trim = trim($age, " \t\n\r\0\x0B");
                            if (!empty($age_trim)) {
                                $result = strip_tags($age_trim, '<string><string/>');
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'typicalAgeRange', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'difficulty', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'difficulty', $result);
                            $cat_valid = true;
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'typicalLearningTime', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'typicalLearningTime', $result);
                            $cat_valid = true;
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'description', CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $descriptions = explode("</description>", $temp);
                        foreach ($descriptions as $description) {
                            $description_trim = trim($description, " \t\n\r\0\x0B");
                            if (!empty($description_trim)) {
                                $result = strip_tags($description_trim, '<string><string/>');
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'description', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'language', CONTEXT_COURSE, $temp)) {
                        // zl_temp handle more field instance
                        $languages = explode("</language>", $temp);
                        foreach ($languages as $language) {
                            $language_trim = trim($language, " \t\n\r\0\x0B");
                            if (!empty($language_trim)) {
                                $result = strip_tags($language_trim);
                                if (!empty($result)) {
                                    $this->response->addChild($cat_node, 'language', $result);
                                    $cat_valid = true;
                                }
                            }
                        }
                        $temp=''; 
                    }
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    break;

                case 'rights':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'cost', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<source><source/><value><value/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'cost');
                            $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'copyrightAndOtherRestrictions', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<source><source/><value><value/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'copyrightAndOtherRestrictions');
                            $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'description', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<string><string/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'description', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    break;
                    
                case 'relation':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'kind', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<source><source/><value><value/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'kind');
                            $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'resource', ['identifier', 'description'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<identifier><identifier/><description><description/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'resource');
                            $this->generate_sub_node_two($result, $container_node, ['identifier', 'description']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    break;
                    
                case 'annotation':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);
                    
                    if (local_lom_handle_field($courseid, $cat, 'entity', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'entity', $result);
                            $cat_valid = true;
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_field($courseid, $cat, 'date', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'date', $result);
                        }
                        $temp=''; 
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'description', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<string><string/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'description', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    
                    break;
                    
                case 'classification':
                    $cat_valid = false;
                    $cat_node = $this->response->addChild($schema_node, $cat);
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'purpose', ['source', 'value'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<source><source/><value><value/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'purpose');
                            $this->generate_sub_node_two($result, $container_node, ['source', 'value']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_state_field($courseid, $cat, 'taxonPath', ['source', 'taxon'], CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<source><source/><value><value/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $container_node = $this->response->addChild($cat_node, 'taxonPath');
                            $this->generate_sub_node_two($result, $container_node, ['source', 'taxon']);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'description', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<string><string/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'description', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (local_lom_handle_langstring_field($courseid, $cat, 'keyword', CONTEXT_COURSE, $temp)) {
                        $result = trim(strip_tags($temp, '<string><string/>'), " \t\n\r\0\x0B");
                        if (!empty($result)) {
                            $this->response->addChild($cat_node, 'keyword', $result);
                            $cat_valid = true;
                        }
                        $temp='';
                    }
                    
                    if (!$cat_valid)
                        $this->response->removeChild($schema_node, $cat_node);
                    break;
                    
                default:
                    break;
            }
        }
    }

    private function add_metadata_dc($cur_record, $record) {
        global $DB;
        $temp = '';
        $valid_cats = array();
        $dc_metadata=[];

        /*
        y dc:identifier  1.1.2: /lom/general/identifier/entry
        y dc:title       1.2: /lom/general/title
        y dc:language    1.3: /lom/general/language
        y dc:description 1.4: /lom/general/description
        n dc:subject     1.5: /lom/general/keyword or 9: /lom/classification with 9.1: /lom/classification/purpose equals "discipline" or "idea".
        n dc:coverage    1.6: /lom/general/coverage
        y dc:type        5.2: /lom/educational/learningResourceType
        y dc:date        2.3.3: /lom/lifeCycle/contribute/date when 2.3.1: /lom/lifeCycle/contribute/role has a value of "publisher".
        n dc:creator     2.3.2: /lom/lifeCycle/contribute/entity when 2.3.1: /lom/lifeCycle/contribute/role has a value of "author".
        n dc:otherContributor 2.3.2: /lom/lifeCycle/contribute/entity with the type of contribution specified in 2.3.1: /lom/lifeCycle/contribute/role
        n dc:publisher   2.3.2: /lom/lifeCycle/contribute/entity when 2.3.1: /lom/lifeCycle/contribute/role has a value of "publisher".
        y dc:format      4.1: /lom/technical/format
        y dc:rights      6.3: /lom/rights/description
        n dc:relation    7.2.2: /lom/relation/resource/description
        n dc:source      7.2: /lom/relation/resource when the value of 7.1: /lom/relation/kind is "isBasedOn". 
        */
        
        $meta_node =  $this->response->addChild($cur_record ,"metadata");

        $schema_node = $this->response->addChild($meta_node, $record['metadata']['container_name']);
        
        foreach ($record['metadata']['container_attributes'] as $name => $value) {
            $schema_node->setAttribute($name, $value);
        }

        // zl_temp ab now get the course metadata and build DOM element
        $courseid = $record['metadata']['fields'];

        $lom_categories = array('general', 'lifecycle', 'metaMetadata', 'technical', 'educational', 'rights', 'relation', 'annotation', 'classification');
        
        // check whether they are in metadata datebase defined
        foreach($lom_categories as $lom_cat) {
            $records = $DB->get_records('local_metadata_category', ['contextlevel' => CONTEXT_COURSE, 'name' => $lom_cat]);
            if (!empty($records)) {
                $valid_cats[] = $lom_cat;
            }
        }
        
        foreach($valid_cats as $cat) {
            switch ($cat) {
                case 'general': 
                    // set dc:identifier
                    if (!empty($datas=local_lom_get_field($courseid, $cat, 'identifier'))) {
                        
                        foreach ($datas as $data) {
                            if (!empty($data)) {
                                $text = '';
                                $values = explode('|', $data);
                                $dc_metadata['dc:identifier'] = $values[1];
                            }
                        }
                    }
                    // set dc:title
                    if (!empty($datas=local_lom_get_field($courseid, $cat, 'title'))) {
                        $dc_metadata['dc:title'] = $datas[0];
                    }
                    // set dc:language
                    if (!empty($datas=local_lom_get_field($courseid, $cat, 'language'))) {
                        $dc_metadata['dc:language']= $datas[0];
                    }
                    // set dc:decsription
                    if (!empty($datas=local_lom_get_field($courseid, $cat, 'description'))) {
                        $dc_metadata['dc:description'] = $datas[0];
                    }
                    break;
                
                case 'lifecycle':
                    // set dc:date
                    if (!empty($datas=local_lom_get_field($courseid, $cat, 'contribute'))) {
                        
                        foreach ($datas as $data) {
                            if (!empty($data)) {
                                $text = '';
                                $values = explode('|', $data);
                                if ($values[1] == 'author') {
                                    $dc_metadata['dc:creator'] = $values[2];
                                }
                                if ($values[1] == 'publisher') {
                                    $dc_metadata['dc:publisher'] = $values[2];
                                }
                                $dc_metadata['dc:date'] = strftime("%F",$values[3]);
                            }
                        }
                    }
                    break;
                
                case 'technical': 
                    // set dc:format
                    if (!empty($datas=local_lom_get_field($courseid, $cat, 'format'))) {
                        $dc_metadata['dc:format'] = $datas[0];
                    }
                    break;
                
                case 'educational': 
                    //set dc:type
                    if (!empty($datas=local_lom_get_field($courseid, $cat, 'learningResourceType'))) {
                        
                        foreach ($datas as $data) {
                            if (!empty($data)) {
                                $text = '';
                                $values = explode('|', $data);
                                $dc_metadata['dc:type'] = $values[1];
                            }
                        }
                    }
                    break;
                
                case 'rights': // set dc:rights
                    //set dc:right
                    if (!empty($datas=local_lom_get_field($courseid, $cat, 'description'))) {
                        $dc_metadata['dc:rights'] = $datas[0];
                    }
                    break;

                default:
                    break;
            }
        }
        
        foreach($dc_metadata as $key=>$value) {
            $this->response->addChild($schema_node, $key, $value);
        }
        
        
    }
        
    private function createResumptionToken($delivered_records) {

        list($usec, $sec) = explode(" ", microtime());
        $token = ((int)($usec*1000) + (int)($sec*1000));

        $fp = fopen ($this->token_prefix.$token, 'w');
        if($fp==false) {
            exit("Cannot write. Writer permission needs to be changed.");
        }	
        fputs($fp, "$delivered_records#");
        fputs($fp, "$metadataPrefix#");
        fputs($fp, "{$this->args['from']}#");
        fputs($fp, "{$this->args['until']}#");
        fputs($fp, "{$this->args['set']}#");
        fclose($fp);
        return $token;
    }

    private function readResumptionToken($resumptionToken) {
        $rtVal = false;
        $fp = fopen($resumptionToken, 'r');
        if ($fp != false) {
            $filetext = fgets($fp, 255);
            $textparts = explode('#', $filetext);
            fclose($fp);
            unlink($resumptionToken);
            $rtVal = array_values($textparts);
        }
        return $rtVal;
    }

    /**
     * All datestamps used in this system are GMT even
     * return value from database has no TZ information
     */
    private function formatDatestamp($datestamp) {
        return date("Y-m-d\TH:i:s\Z",strtotime($datestamp));
    }

    /**
     * The database uses datastamp without time-zone information.
     * It needs to clean all time-zone informaion from time string and reformat it
     */
    private function checkDateFormat($date) {
        $date = str_replace(array("T","Z")," ",$date);
        $time_val = strtotime($date);
        if(!$time_val) return false;
        if(strstr($date,":")) {
            return date("Y-m-d H:i:s",$time_val);
        } else {
            return date("Y-m-d",$time_val);
        }
    }
    
    /**
     * add nodes for substructure which contain 2 entries
     */
    private function generate_sub_node_two($text, $parent_node, $subs) {
        $res = false;
        $parts = explode("\n", $text);

        foreach ($parts as $part) {
            $trimmed = trim($part, " \t\n\r\0\x0B");
            if (!empty($trimmed)) {
          
                    if (strpos($trimmed, $subs[0]) !== false) {
                        $result_0 = strip_tags($trimmed, "'<' .$subs[1] . '><' . $subs[1] . '/>'");
                        if (!empty($result_0)) {
                            $this->response->addChild($parent_node, $subs[0] , $result_0);
                            $res = true;
                        }
                    }
                    if (strpos($trimmed, $subs[1]) !== false) {
                        $result_1 = strip_tags($trimmed,  "'<' .$subs[0] . '><' . $subs[0] . '/>'");
                        if (!empty($result_1)) {
                            $this->response->addChild($parent_node, $subs[1], $result_1);
                            $res = true;
                        }
                    }
            }
        }
        return $res;
    }
    
    function trim_value(&$value) {
         $value = trim($value, " \t\n\r\0\x0B");
    }
    
    /**
     * add nodes for substructure which contain 3 entries
     * return true if one of the entries is true
     */
    private function generate_sub_node_three($text, $parent_node, $subs) {
        $res = false;
        $parts = explode("\n", $text);
        
        foreach ($parts as $part) {
            $trimmed = trim($part, " \t\n\r\0\x0B");
            
            if (!empty($trimmed)) {

                    if ($trimmed == '<role>') {
                        $role_node = $this->response->addChild($parent_node, 'role');
                    }
                    
                    if (strpos($trimmed,'<source>') !== false) {
                        $result_source = strip_tags($trimmed,  '');
                        if (!empty($result_source) && !empty($role_node)) {
                            $this->response->addChild($role_node, 'source' , $result_source);
                            $res = true;
                        }
                    }
                    if (strpos($trimmed,'<value>') !== false) {
                        $result_value = strip_tags($trimmed,  '');
                        if (!empty($result_value) && !empty($role_node)) {
                            $this->response->addChild($role_node, 'value' , $result_value);
                            $res = true;
                        }
                    }

                    if (strpos($trimmed, $subs[1]) !== false) {
                        $result_1 = strip_tags($trimmed,  '');
                        if (!empty($result_1)) {
                            $this->response->addChild($parent_node, $subs[1], $result_1);
                            $res = true;
                        }
                    }
                    if (strpos($trimmed, $subs[2]) !== false) {
                        $result_2 = strip_tags($trimmed,  '');
                        if (!empty($result_2)) {
                            $this->response->addChild($parent_node, $subs[2], $result_2);
                            $res = true;
                        }
                    }
            }
        }
        return $res;
    }

}
