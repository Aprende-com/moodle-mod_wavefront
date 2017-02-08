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
 * Define all the backup steps that will be used by the backup_model_activity_task
 */

/**
 * Define the complete model structure for backup, with file and id annotations
 */
class backup_model_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $model = new backup_nested_element('model', array('id'), array(
            'course', 'folder', 'name', 'perpage', 'comments', 'extinfo',
            'timemodified', 'ispublic', 'rss', 'autoresize', 'resize', 'perrow',
            'captionfull', 'captionpos', 'intro', 'introformat'
        ));

        $comments = new backup_nested_element('usercomments');
        $comment = new backup_nested_element('comment', array('id'), array(
            'model', 'userid', 'commenttext', 'timemodified'
        ));

        $modelmetas = new backup_nested_element('model_metas');
        $modelmeta = new backup_nested_element('model_meta', array('id'), array(
            'model', 'model', 'description', 'metatype'
        ));

        // Build the tree.

        $model->add_child($comments);
        $comments->add_child($comment);
        $model->add_child($modelmetas);
        $modelmetas->add_child($modelmeta);

        // Define sources.
        $model->set_source_table('model', array('id' => backup::VAR_ACTIVITYID));
        $modelmeta->set_source_table('model_model_meta', array('model' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $comment->set_source_table('model_comments', array('model' => backup::VAR_PARENTID));
        }

        // Define file annotations.
        $lightboxgallery->annotate_files('mod_model', 'model_files', null);
        $lightboxgallery->annotate_files('mod_model', 'model_thumbs', null);
        $lightboxgallery->annotate_files('mod_model', 'model_index', null);
        $lightboxgallery->annotate_files('mod_model', 'intro', null);

        $comment->annotate_ids('user', 'userid');

        // Return the root element (model), wrapped into standard activity structure.
        return $this->prepare_activity_structure($model);
    }
}
