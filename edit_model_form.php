<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/lib/formslib.php');

class mod_wavefront_model_form extends moodleform {

    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $model              = $this->_customdata['model'];
        $cm                 = $this->_customdata['cm'];
        $descriptionoptions = $this->_customdata['descriptionoptions'];
        $modeloptions       = $this->_customdata['modeloptions'];

        $context  = context_module::instance($cm->id);
        // Prepare format_string/text options
        $fmtoptions = array(
            'context' => $context);

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('editor', 'description_editor', get_string('modeldescription', 'wavefront'), null, $descriptionoptions);
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addRule('description_editor', get_string('required'), 'required', null, 'client');

        $mform->addElement('filemanager', 'model_filemanager', get_string('modelfiles', 'wavefront'), null, $modeloptions);
        $mform->addHelpButton('model_filemanager', 'modelfiles', 'wavefront');
        
        // Advanced options
        $mform->addElement('header', 'modeloptions', get_string('advanced'));
        
        // Width.
        $mform->addElement('text', 'width', get_string('width', 'wavefront'), 'maxlength="5" size="5"');
        $mform->setDefault('width', 400);
        $mform->setType('width', PARAM_INT);
        
        // Height.
        $mform->addElement('text', 'height', get_string('height', 'wavefront'), 'maxlength="5" size="5"');
        $mform->setDefault('height', 400);
        $mform->setType('height', PARAM_INT);
        
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

//-------------------------------------------------------------------------------
        $this->add_action_buttons();

//-------------------------------------------------------------------------------
        $this->set_data($model);
    }

    function validation($data, $files) {
        global $CFG, $USER, $DB;
        $errors = parent::validation($data, $files);

        return $errors;
    }
}

