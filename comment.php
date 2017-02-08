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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/comment_form.php');

$id      = required_param('id', PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

if (!$model = $DB->get_record('model', array('id' => $id))) {
    print_error('invalidmodelid', 'model');
}
list($course, $cm) = get_course_and_cm_from_instance($model, 'model');

if ($delete && ! $comment = $DB->get_record('model_comments', array('model' => $model->id, 'id' => $delete))) {
    print_error('Invalid comment ID');
}

require_login($course, true, $cm);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/model/view.php', array('id' => $id));
$PAGE->set_title($model->name);
$PAGE->set_heading($course->shortname);

$context = context_module::instance($cm->id);

$modelurl = $CFG->wwwroot.'/mod/model/view.php?id='.$cm->id;

if ($delete && has_capability('mod/model:edit', $context)) {
    if ($confirm && confirm_sesskey()) {
        $DB->delete_records('model_comments', array('id' => $comment->id));
        redirect($modelurl);
    } else {
        echo $OUTPUT->header();
        model_print_comment($comment, $context);
        echo('<br />');
        $paramsyes = array('id' => $model->id, 'delete' => $comment->id, 'sesskey' => sesskey(), 'confirm' => 1);
        $paramsno = array('id' => $cm->id);
        echo $OUTPUT->confirm(get_string('commentdelete', 'model'),
                              new moodle_url('/mod/model/comment.php', $paramsyes),
                              new moodle_url('/mod/model/view.php', $paramsno));
        echo $OUTPUT->footer();
        die();
    }
}

require_capability('mod/model:addcomment', $context);

if (! $model->comments) {
    print_error('Comments disabled', $modelurl);
}

$mform = new mod_model_comment_form(null, $model);

if ($mform->is_cancelled()) {
    redirect($modelurl);
} else if ($formadata = $mform->get_data()) {
    $newcomment = new stdClass;
    $newcomment->model = $model->id;
    $newcomment->userid = $USER->id;
    $newcomment->commenttext = $formadata->comment['text'];
    $newcomment->timemodified = time();
    if ($DB->insert_record('model_comments', $newcomment)) {
        $params = array(
            'context' => $context,
            'other' => array(
                'modelid' => $model->id,
            ),
        );
        $event = \mod_model\event\model_comment_created::create($params);
        $event->trigger();

        redirect($model, get_string('commentadded', 'model'));
    } else {
        print_error('Comment creation failed');
    }
}


echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
