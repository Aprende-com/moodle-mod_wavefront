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
 * A page for uploading new models
 *
 * @package   mod_model
 * @copyright 2017 Ian Wild
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/modeladd_form.php');
require_once(dirname(__FILE__).'/modelclass.php');

$id = required_param('id', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'model');
$model = $DB->get_record('model', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/model:addmodel', $context);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/model/view.php', array('id' => $cm->id));
$PAGE->set_title($model->name);
$PAGE->set_heading($course->shortname);

$mform = new mod_model_modeladd_form(null, array('id' => $cm->id));

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/mod/model/view.php?id='.$cm->id);

} else if (($formdata = $mform->get_data()) && confirm_sesskey()) {
    require_once($CFG->dirroot . '/lib/uploadlib.php');

    $fs = get_file_storage();
    $draftid = file_get_submitted_draft_itemid('model');
    if (!$files = $fs->get_area_files(
        context_user::instance($USER->id)->id, 'user', 'draft', $draftid, 'id DESC', false)) {
        redirect($PAGE->url);
    }

    if ($model->autoresize == AUTO_RESIZE_UPLOAD || $model->autoresize == AUTO_RESIZE_BOTH) {
        $resize = $model->resize;
    } else if (isset($formdata->resize)) {
        $resize = $formdata->resize;
    } else {
        $resize = 0; // No resize.
    }

    model_add_model($files, $context, $cm, $model, $resize);
    redirect($CFG->wwwroot.'/mod/model/view.php?id='.$cm->id);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
