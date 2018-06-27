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

$string['pluginname'] = 'Course LOM';

$string['lom_metadata'] = 'LOM Metadata';
$string['metadatafor'] = 'Instance metadata';
$string['oaipmhexport'] = 'OAI-PMH Export';
$string['setdefault'] = 'Set the default course LOM profile';

// category
$string['general'] = 'Allgemeine Informationen';
$string['lifecycle'] = 'Lebenszyklus';
$string['technical'] = 'Technische Informationen';
$string['educational'] = 'Pädagogische Informationen';
$string['rights'] = 'Urheberechte';
$string['relation'] = 'Beziehung';
$string['annotation'] = 'Kommentar';
$string['classification'] = 'Klassifikation';

// fields
$string['identifier'] = 'Bezeichnung';
$string['title'] = 'Kurstitel';
$string['language'] = 'Sprache';
$string['general_description'] = 'Kursbeschreibung';
$string['keyword'] = 'Schlagwort';
$string['structure'] = 'Kursformat';
$string['aggregationLevel'] = 'Schwierigkeitsgrad';

$string['version'] = 'Version';
$string['status'] = 'Status';
$string['contribute'] = 'Mitwirkende';

$string['format'] = 'Format';
$string['size'] = 'Datengrösse';
$string['location'] = 'Standort';
$string['requirement'] = 'Technische Anforderungen';

$string['interactivityType'] = 'InteraktivitatTyp';
$string['learningResourceType'] = 'Ressourcentyp';
$string['interactivityLevel'] = 'Interaktivitateben';
$string['intendedEndUserRole'] = 'Zielgruppe';
$string['context'] = 'Bildungsstufe';
$string['typicalAgeRange'] = 'Alter';
$string['difficulty'] = 'Anforderung';
$string['typicalLearningTime'] = 'Lernzeit';
$string['educational_description'] = 'Pädagogische Hinweise';

$string['cost'] = 'Kosten';
$string['copyrightAndOtherRestrictions'] = 'UrheberrechtUndEinschränkung';
$string['description'] = 'Lizenz';

$string['kind'] = 'Beziehungsart';
$string['resource'] = 'Quelle';

// sub-fields
$string['catalog'] = 'Katalog';
$string['entry'] = 'Kurzbezeichnung';
//$string['source'] = 'Quelle';
//$string['value'] = 'Wert';
$string['role'] = 'Rolle';
$string['entity'] = 'Profil';
$string['date'] = 'Erstellungsdatum';
$string['orComposite'] = 'Verbundsstoff';

$string['all metadata records are deleted'] = 'Alle Kurse-metadata Einträge sind gelöscht';
$string['Course metadata is now set to default LOM profile'] = 'Kurse-metadaten sind nun voreingestellt(LOM Profil)';

$string['back'] = 'Zurück';

// requirement/orComposite
$string['type'] = 'type';
$string['name'] = 'name';
$string['minimumVersion'] = 'minimumVersion';
$string['maximumVersion'] = 'maximumVersion';


$string['help_general_identifier'] = 'general_identifier ';
$string['help_general_identifier_help'] = 'help text for general identifier';

$string['help_general_identifier_catalog'] = 'general_identifier_catalog';
$string['help_general_identifier_catalog_help'] = 'help text for general identifier catalog';

$string['help_general_identifier_entry'] = 'general_identifier_entry';
$string['help_general_identifier_entry_help'] = 'help text for general identifier entry';

$string['help_general_title'] = 'general_title';
$string['help_general_title_help'] = 'help text for general title';

$string['help_general_language'] = 'general_language';
$string['help_general_language_help'] = 'help text for general language';

$string['help_general_description'] = 'general_description';
$string['help_general_description_help'] = 'help text for general description';

$string['help_general_keyword'] = 'general_keyword';
$string['help_general_keyword_help'] = 'help text for general keyword';

$string['help_general_structure_value'] = 'general_structure';
$string['help_general_structure_value_help'] = 'help text for general structure';

$string['help_general_aggregationLevel'] = 'general_aggregationLevel';
$string['help_general_aggregationLevel_help'] = 'help text for general aggregationLevel';

$string['help_lifecycle_version'] = 'lifecycle_version';
$string['help_lifecycle_version_help'] = 'help text for lifecycle version';

$string['help_lifecycle_status_value'] = 'lifecycle_status';
$string['help_lifecycle_status_value_help'] = 'help text for lifecycle status';

$string['help_lifecycle_contribute_role'] = 'lifecycle_contribute_role';
$string['help_lifecycle_contribute_role_help'] = 'help text for lifecycle contribute role';

$string['help_lifecycle_contribute_entity'] = 'lifecycle_contribute_entity';
$string['help_lifecycle_contribute_entity_help'] = 'help text for lifecycle contribute entity';

$string['help_lifecycle_contribute_1_entity'] = 'lifecycle_contribute_entity';
$string['help_lifecycle_contribute_1_entity_help'] = 'help text for lifecycle contribute entity';

$string['help_lifecycle_contribute_date'] = 'lifecycle_contribute_date';
$string['help_lifecycle_contribute_date_help'] = 'help text for lifecycle contribute date';

$string['help_lifecycle_contribute_1_date'] = 'lifecycle_contribute_1_date';
$string['help_lifecycle_contribute_1_date_help'] = 'help text for lifecycle contribute 1 date';

$string['help_technical_format'] = 'technical_format';
$string['help_technical_format_help'] = 'help text for technical format';

$string['help_technical_size'] = 'technical_size';
$string['help_technical_size_help'] = 'help text for technical size';

$string['help_technical_location'] = 'technical_location';
$string['help_technical_location_help'] = 'help text for technical location';


$string['help_technical_requirement_type'] = 'technical_requirement_type';
$string['help_technical_requirement_type_help'] = 'help text for technical requirement type';

$string['help_technical_requirement_name'] = 'technical_requirement_name';
$string['help_technical_requirement_name_help'] = 'help text for technical requirement name';

$string['help_technical_requirement_minimumVersion'] = 'technical_requirement_minimumVersion';
$string['help_technical_requirement_minimumVersion_help'] = 'help text for technical requirement minimumVersion';

$string['help_technical_requirement_maximumVersion'] = 'technical_requirement_maximumVersion';
$string['help_technical_requirement_maximumVersion_help'] = 'help text for technical requirement maximumVersion';

$string['help_educational_interactivityType_value'] = 'educational_interactivityType_value';
$string['help_educational_interactivityType_value_help'] = 'help text for reducational interactivityType value';

$string['help_educational_learningResourceType_value'] = 'educational_learningResourceType_value';
$string['help_educational_learningResourceType_value_help'] = 'help text for educational learningResourceType value';

$string['help_educational_intendedEndUserRole_value'] = 'educational_intendedEndUserRole_value';
$string['help_educational_intendedEndUserRole_value_help'] = 'help text for educational intendedEndUserRole value';

$string['help_educational_context_value'] = 'educational_context_value';
$string['help_educational_context_value_help'] = 'help text for educational context value';

$string['help_educational_typicalAgeRange'] = 'educational_typicalAgeRange';
$string['help_educational_typicalAgeRange_help'] = 'help text for educational typicalAgeRange';

$string['help_educational_difficulty'] = 'educational_difficulty';
$string['help_educational_difficulty_help'] = 'help text for educational difficulty';

$string['help_educational_typicalLearningTime'] = 'educational__typicalLearningTime';
$string['help_educational_typicalLearningTime_help'] = 'help text for educational typicalLearningTime';

$string['help_rights_cost_value'] = 'rights_cost_value';
$string['help_rights_cost_value_help'] = 'help text for rights_cost value';

$string['help_rights_copyrightAndOtherRestrictions_value'] = 'rights_copyrightAndOtherRestrictions_value';
$string['help_rights_copyrightAndOtherRestrictions_value_help'] = 'help text for rights copyrightAndOtherRestrictions_value';

$string['help_rights_description'] = 'rights_description';
$string['help_rights_description_help'] = 'help text for rights description';

$string['help_relation_kind_value'] = 'relation_kind_value';
$string['help_relation_kind_value_help'] = 'help text for relation kind value';
