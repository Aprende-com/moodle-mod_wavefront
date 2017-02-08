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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_lightboxgallery_activity_task
 */

/**
 * Structure step to restore one model activity
 */
class restore_model_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $model = new restore_path_element('model', '/activity/model');
        $paths[] = $model;

        $meta = new restore_path_element('model_image_meta', '/activity/model/model_metas/model_meta');
        $paths[] = $meta;

        if ($userinfo) {
            $comment = new restore_path_element('model_comment', '/activity/model/usercomments/comment');
            $paths[] = $comment;
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_model($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        // Insert the model record.
        $newitemid = $DB->insert_record('model', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_model_comment($data) {
        global $DB;

        $data = (object)$data;

        $data->model = $this->get_new_parentid('model');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        if (isset($data->comment)) {
            $data->commenttext = $data->comment;
        }
        $DB->insert_record('model_comments', $data);
    }

    protected function process_model_model_meta($data) {
        global $DB;

        $data = (object)$data;

        $data->model = $this->get_new_parentid('model');
        // TODO: image var to match model.
        $DB->insert_record('model_model_meta', $data);
    }

    protected function after_execute() {
        $this->add_related_files('mod_model', 'model_images', null);
        $this->add_related_files('mod_model', 'model_thumbs', null);
        $this->add_related_files('mod_model', 'model_index', null);
        $this->add_related_files('mod_model', 'intro', null);
    }
}
