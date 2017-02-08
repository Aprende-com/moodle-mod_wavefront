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
 * Prints a particular instance of a model
 *
 * @package   mod_model
 * @author    Ian Wild
 * @copyright 2017 Ian Wild
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__).'/modelclass.php');

class mod_model_modeladd_form extends moodleform {

    public function definition() {

        global $COURSE, $cm;

        $mform =& $this->_form;
        $model = $this->_customdata;

        $handlecollisions = !get_config('model', 'overwritefiles');
        $mform->addElement('header', 'general', get_string('addmodel', 'model'));

        $mform->addElement('filemanager', 'model', get_string('file'), '0',
                           array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('web_image', 'archive')));
        $mform->addRule('model', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('model', 'addmodel', 'model');

        if ($this->can_resize()) {
            $resizegroup = array();
            $resizegroup[] = &$mform->createElement('select', 'resize', get_string('edit_resize', 'model'),
                                                    model_resize_options());
            $resizegroup[] = &$mform->createElement('checkbox', 'resizedisabled', null, get_string('disable'));
            $mform->setType('resize', PARAM_INT);
            $mform->addGroup($resizegroup, 'resizegroup', get_string('edit_resize', 'model'), ' ', false);
            $mform->setDefault('resizedisabled', 1);
            $mform->disabledIf('resizegroup', 'resizedisabled', 'checked');
            $mform->setAdvanced('resizegroup');
        }

        $mform->addElement('hidden', 'id', $cm->id);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('addmodel', 'model'));

    }

    public function validation($data, $files) {
        global $USER;

        if ($errors = parent::validation($data, $files)) {
            return $errors;
        }

        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();

        if (!$files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['model'], 'id', false)) {
            $errors['model'] = get_string('required');
            return $errors;
        } else {
            $file = reset($files);
            if ($file->get_mimetype() != 'application/zip') {
                $errors['model'] = get_string('invalidfiletype', 'error', $file->get_filename());
                // Better delete current file, it is not usable anyway.
                $fs->delete_area_files($usercontext->id, 'user', 'draft', $data['model']);
            }
        }

        return $errors;
    }


    private function can_resize() {
        global $model;

        return !in_array($model->autoresize, array(AUTO_RESIZE_UPLOAD, AUTO_RESIZE_BOTH));
    }
}
