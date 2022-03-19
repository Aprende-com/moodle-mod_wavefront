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
 * Prints a particular instance of a 3d model
 *
 * @package   mod_wavefront
 * @copyright 2022 Ian Wild
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/filelib.php');

global $DB;

$id = optional_param('id', 0, PARAM_INT);
$w = optional_param('w', 0, PARAM_INT);
//$editing = optional_param('editing', 0, PARAM_BOOL);

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'wavefront');
    if (!$wavefront = $DB->get_record('wavefront', array('id' => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
} else {
    if (!$wavefront = $DB->get_record('wavefront', array('id' => $w))) {
        print_error('invalidwavefrontid', 'wavefront');
    }
    list($course, $cm) = get_course_and_cm_from_instance($wavefront, 'wavefront');
}


if ($wavefront->ispublic) {
    $PAGE->set_cm($cm, $course);
    $PAGE->set_pagelayout('incourse');
} else {
    require_login($course, true, $cm);
}

$context = context_module::instance($cm->id);


require_login();

/*
if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    notice(get_string("activityiscurrentlyhidden"));
}
*/
wavefront_config_defaults();
/*
$params = array(
    'context' => $context,
    'objectid' => $wavefront->id
);
$event = \mod_wavefront\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('wavefront', $wavefront);
$event->trigger();

// Mark viewed.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);
*/
$PAGE->set_cm($cm);
$PAGE->set_url('/mod/wavefront/ar.php', array('id' => $cm->id));
$PAGE->set_title($wavefront->name);
$PAGE->set_pagelayout('popup');

$output = $PAGE->get_renderer('mod_wavefront');

// send page header
$output->header();

$js_params = array();

$PAGE->requires->js_call_amd('mod_wavefront/ar_renderer', 'init', $js_params);

// get first model for now    
echo $output->display_model_in_ar();



