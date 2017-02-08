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
 * Internal library of functions for module model
 *
 * All the newmodule specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package   mod_model
 * @copyright 2017 Ian Wild
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/lib.php');
require_once("$CFG->libdir/filelib.php");

define('WAVEFRONT_MAX_MODEL_LABEL', 13);
define('WAVEFRONT_MAX_COMMENT_PREVIEW', 20);

/**
 * Add a set of uploaded files to the model.
 *
 * @param array $files A list of stored_file objects.
 * @param context $context
 * @param cm_info $cm
 * @param $model
 * @param int $resize
 * @access public
 * @return void
 */
function wavefront_add_files($files, $context, $cm, $model, $resize = 0) {
    require_once(dirname(__FILE__).'/modelclass.php');

    $fs = get_file_storage();

    $files = array();
    foreach ($files as $storedfile) {
        if ($storedfile->get_mimetype() == 'application/zip') {
            // Unpack.
            $packer = get_file_packer('application/zip');
            $fs->delete_area_files($context->id, 'mod_model', 'unpacktemp', 0);
            $storedfile->extract_to_storage($packer, $context->id, 'mod_model', 'unpacktemp', 0, '/');
            $files = $fs->get_area_files($context->id, 'mod_model', 'unpacktemp', 0);
            $storedfile->delete();
        } else {
            $files[] = $storedfile;
        }
    }

    foreach ($files as $storedfile) {
        if ($storedfile->is_valid_image()) {
            $filename = $storedfile->get_filename();
            $fileinfo = array(
                'contextid'     => $context->id,
                'component'     => 'mod_model',
                'filearea'      => 'model_files',
                'itemid'        => 0,
                'filepath'      => '/',
                'filename'      => $filename
            );
            if (!$fs->get_file($context->id, 'mod_model', 'model_files', 0, '/', $filename)) {
                $storedfile = $fs->create_file_from_storedfile($fileinfo, $storedfile);
                $image = new model_file($storedfile, $model, $cm);

                if ($resize > 0) {
                    $resizeoptions = model_resize_options();
                    list($width, $height) = explode('x', $resizeoptions[$resize]);
                    $image->resize_model($width, $height);
                }

                $model->set_caption($filename);
            }
        }
    }
    $fs->delete_area_files($context->id, 'mod_model', 'unpacktemp', 0);
}

function wavefront_config_defaults() {
    $defaults = array(
        'disabledplugins' => '',
    );

    $localcfg = get_config('model');

    foreach ($defaults as $name => $value) {
        if (! isset($localcfg->$name)) {
            set_config($name, $value, 'model');
        }
    }
}

function wavefront_edit_types($showall = false) {
    $result = array();

    $disabledplugins = explode(',', get_config('model', 'disabledplugins'));

    // TODO: Remove this once crop functionality is working.
    $disabledplugins[] = 'crop';

    $edittypes = get_list_of_plugins('mod/model/edit');

    foreach ($edittypes as $edittype) {
        if ($showall || !in_array($edittype, $disabledplugins)) {
            $result[$edittype] = get_string('edit_' . $edittype, 'model');
        }
    }

    return $result;
}

function wavefront_print_tags($heading, $tags, $courseid, $modelid) {
    global $OUTPUT;

    echo $OUTPUT->box_start();

    echo '<form action="search.php" style="float: right; margin-left: 4px;">'.
         ' <fieldset class="invisiblefieldset">'.
         '  <input type="hidden" name="id" value="'.$courseid.'" />'.
         '  <input type="hidden" name="model" value="'.$modelid.'" />'.
         '  <input type="text" name="search" size="8" />'.
         '  <input type="submit" value="'.get_string('search').'" />'.
         ' </fieldset>'.
         '</form>'.
         $heading.': ';

    $tagarray = array();
    foreach ($tags as $tag) {
        $tagparams = array('id' => $courseid, 'model' => $modelid, 'search' => stripslashes($tag->description));
        $tagurl = new moodle_url('/mod/model/search.php', $tagparams);
        $tagarray[] = html_writer::link($tagurl, s($tag->description), array('class' => 'taglink'));
    }

    echo implode(', ', $tagarray);

    echo $OUTPUT->box_end();
}

function wavefront_resize_options() {
    return array(1 => '1280x1024', 2 => '1024x768', 3 => '800x600', 4 => '640x480');
}

function wavefront_index_thumbnail($courseid, $model, $newimage = null) {
    global $CFG;

    require_once(dirname(__FILE__).'/modelclass.php');
    $cm = get_coursemodule_from_instance("model", $model->id, $courseid);
    $context = context_module::instance($cm->id);

    $modelid = 'Model Index Image';

    $fs = get_file_storage();
    $storedfile = $fs->get_file($context->id, 'mod_model', 'model_index', '0', '/', 'index.png');

    if (!is_null($newimage) && is_object($storedfile)) { // Delete any existing index.
        $storedfile->delete();
    }
    if (is_object($storedfile) && is_null($newimage)) {
        // Grab the index.
        $index = $storedfile;
    } else {
        // Get first image and create an index for that.
        if (is_null($newimage)) {
            $files = $fs->get_area_files($context->id, 'mod_model', 'model_files');
            $file = array_shift($files);
            while (substr($file->get_mimetype(), 0, 6) != 'image/') {
                $file = array_shift($files);
            }
            $image = new model_image($file, $model, $cm);
        } else {
            $image = $newimage;
        }
        $index = $image->create_index();
    }
    $path = $CFG->wwwroot.'/pluginfile.php/'.$context->id.'/mod_model/_index/'.
                $index->get_itemid().$index->get_filepath().$index->get_filename();

    return '<img src="' . $path . '" alt="" ' . (! empty($imageid) ? 'id="' . $imageid . '"' : '' )  . ' />';
}


/**
 * File browsing support class
 */
class wavefront_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}
