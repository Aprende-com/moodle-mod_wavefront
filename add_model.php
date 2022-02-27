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
 * Form for adding a model
 *
 * @package   mod_wavefront
 * @copyright 2022 Ian Wild
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once('edit_model_form.php');

$cmid = required_param('cmid', PARAM_INT);            // Course Module ID
if (!$cm = get_coursemodule_from_id('wavefront', $cmid)) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

require_capability('mod/wavefront:add', $context);

if (!$wavefront = $DB->get_record('wavefront', array('id'=>$cm->instance))) {
    print_error('invalidid', 'wavefront');
}

$url = new moodle_url('/mod/wavefront/add_model.php', array('cmid'=>$cm->id));
$PAGE->set_url($url);

require_login($cm->course, false, $cm);

$model = new stdClass();
$model->id = null;

$course=get_course($cm->course);

$maxfiles = 50;                // TODO: add some setting
$maxbytes = $course->maxbytes; // TODO: add some setting

$descriptionoptions = array('trusttext'=>true, 'maxfiles'=>$maxfiles, 'maxbytes'=>$maxbytes, 'context'=>$context,
    'subdirs'=>file_area_contains_subdirs($context, 'mod_wavefront', 'model', $model->id));
$modeloptions = array('subdirs'=>false, 'maxfiles'=>$maxfiles, 'maxbytes'=>$maxbytes);

$model = file_prepare_standard_editor($model, 'description', $descriptionoptions, $context, 'mod_wavefront', 'description', $model->id);
$model = file_prepare_standard_filemanager($model, 'model', $modeloptions, $context, 'mod_wavefront', 'model', $model->id);

$model->cmid = $cm->id;

// create form and set initial data
$mform = new mod_wavefront_model_form(null, array('model'=>$model, 'cm'=>$cm, 'descriptionoptions'=>$descriptionoptions, 
                                                    'modeloptions'=>$modeloptions));

if ($mform->is_cancelled()){
    redirect("view.php?id=$cm->id?editing=1");
    
} else if ($model = $mform->get_data()) {
    $timenow = time();

    $model->wavefrontid      = $wavefront->id;
    $model->userid           = $USER->id;
    $model->timecreated      = $timenow;
    $model->description      = '';          // updated later
    $model->descriptionformat = FORMAT_HTML; // updated later
    $model->definitiontrust  = 0;           // updated later
    $model->timemodified     = $timenow;
    
    $model->id = $DB->insert_record('wavefront_model', $model);
    
    // save and relink embedded images and save attachments
    $model = file_postupdate_standard_editor($model, 'description', $descriptionoptions, $context, 'mod_wavefront', 'description', $model->id);
    $model = file_postupdate_standard_filemanager($model, 'model', $modeloptions, $context, 'mod_wavefront', 'model', $model->id);
    
    wavefront_check_for_zips($context, $cm, $model);
    
    // store the updated value values
    $DB->update_record('wavefront_model', $model);

    //refetch complete entry
    $model = $DB->get_record('wavefront_model', array('id'=>$model->id));

    // Trigger event and update completion (if entry was created).
    $eventparams = array(
        'context' => $context,
        'objectid' => $model->id,
    );
    $event = \mod_wavefront\event\model_created::create($eventparams);
    
    $event->add_record_snapshot('wavefront', $wavefront);
    $event->trigger();

    // Update completion state
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && $wavefront->completionentries) {
        $completion->update_state($cm, COMPLETION_COMPLETE);
    }
    redirect("view.php?id=$cm->id&editing=1");
}

if (!empty($id)) {
    $PAGE->navbar->add(get_string('edit'));
}

$PAGE->set_title($wavefront->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($wavefront->name), 2);
if ($wavefront->intro) {
    echo $OUTPUT->box(format_module_intro('wavefront', $wavefront, $cm->id), 'generalbox', 'intro');
}

$mform->display();

echo $OUTPUT->footer();

