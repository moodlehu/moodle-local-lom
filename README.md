moodle-local_lom
-------------------
The purpose of this plugin (together with local_metadata) is to generate LOM-based matadata for moodle course. With the oai-pmh interface which is included in this 
plugin, it is possible to act as a oai-pmh data provider. The end point for oai-pmh is: my-moodle-server/local/lom/oai_pmh/moodle.php. (you can access it via
my-moodle-server/local/lom/oai_pmh/index.html)

It is tested under Moodle-3.4.

The integrated oai-pmh interface is derived from Daniel Neis Araujo's oai-pmh. I have modified it to be integrated here, use moodle platform and add lom metadata.

Requirements
------------

This plugin requires Moodle 3.2+. Earlier version may also work.(not tested) 

It also requires the installation of plugin 'local_metadata'. ( It is released together with this plugin. I have modified the original local_metadata from 
Mike Churchward <mike.churchward@poetgroup.org> )


Installation and usage
----------------------
Install the both plugins local_metadata and local_lom to the folder /local
See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins

For the moment, the oai-pmh interface supports 2 metadata types: oai_dc and lom.


Proposed steps
--------------
1. Initialise the default course metadata:  

   Admin - Course - Course metadata - 'Course LOM profile init'
   I have initialised a certain set of fields as basic lom profile.
   You can also add more fields to the LOM metadata. just choose 'Create a new profile field:', and specify the category.
   Field's shortnames have naming convention '$categoryname_$fieldname', e.g. 'general_identifier'.
   Certain LOM fields can have multiple instances. To add duplicated fields, use the shortname '$categoryname_$fieldname_nn', e.g. 'lifecycle_contribute_1'.
   The sequence of LOM can be moved up/down according.
  
2. The Course LOM matadata can be viewed and edited: 'course editing' - 'Course metadata'

3. The xml file of metadata can be generated over 'course editing' - 'OAI-PMH Export'. 
   The generated xml file is saved in moodle filesystem. Once this xml file is generated, this course will be included in the oai-pmh interface's data provider list.
   Once the course metadata is modified, the xml file should be generated again to show the modified metadata info.

4. Those courses which have generated xml files can be exposed to the oai-pmh harvester.

5. Working as oai-pmh data provider: 
   endpoint: $moodle-server/local/lom/oai_pmh/index.html.
   



