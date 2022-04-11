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
 * Wavefront module renderer
 *
 * @package   mod_wavefront
 * @copyright 2017 onward Ian Wild
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_wavefront_renderer extends plugin_renderer_base {

    /**
     * Render a wavefront model.
     *
     * @param \mod_wavefront\output\wavefront_model $wavefront The Wavefront model area.
     * @return string The rendered model's html.
     */
    public function render_wavefront_model(\mod_wavefront\output\wavefront_model $wavefront): string {
        $context = $wavefront->export_for_template($this);
        if(count($context) == 0) {
            // an error has occured
            return $this->output->heading(get_string("errornomodel", "wavefront"));
        }
        return $this->render_from_template('mod_wavefront/wavefront_model', $context);
    }
    
    /**
     * Render a collada model.
     *
     * @param \mod_wavefront\output\collada_model $collada The Collada model area.
     * @return string The rendered model's html.
     */
    public function render_collada_model(\mod_wavefront\output\collada_model $collada): string {
        $context = $collada->export_for_template($this);
        if(count($context) == 0) {
            // an error has occured
            return $this->output->heading(get_string("errornomodel", "wavefront"));
        }
        return $this->render_from_template('mod_wavefront/collada_model', $context);
    }
    
    /**
     * Render a model's configuration buttons.
     *
     * @param \mod_wavefront\output\model_controls $controls The model's config buttons.
     * @return string The rendered edit action area.
     */
    public function render_model_controls(\mod_wavefront\output\model_controls $controls): string {
        $context = $controls->export_for_template($this);
        return $this->render_from_template('mod_wavefront/model_controls', $context);
    }
    
    /**
     * Returns html to display a 3D model
     * @param object $wavefront The wavefront activity with which the model is associated
     * @param boolean $editing true if the current user can edit the model, else false.
     */
    public function display_model($context, $model, $stagename, $editing = false) {
        
        $output = $this->output->box_start('wavefront');
        
        // Display model
        if ($model->type === 'obj') {
            $wavefrontmodel = new \mod_wavefront\output\wavefront_model($context, $model, $stagename);
            $output .= $this->render($wavefrontmodel);
        } elseif ($model->type === 'dae') {
            $colladamodel = new \mod_wavefront\output\collada_model($context, $model, $stagename);
            $output .= $this->render($colladamodel);
        }
        
        // Display controls
        $wavefrontcontrols = new \mod_wavefront\output\model_controls($context, $model, $editing, $this->page->cm->id);
        $output .= $this->render($wavefrontcontrols);
        
        $output .= $this->output->box_end();
        
        return $output;
    }
    
    /**
     * Output the HTML for a comment in the given context.
     * @param object $comment The comment record to output
     * @param object $context The context from which this is being displayed
     */
    private function print_comment($comment, $context) {
        global $DB, $CFG, $COURSE;
    
        $output = '';
        
        $user = $DB->get_record('user', array('id' => $comment->userid));
    
        $deleteurl = new moodle_url('/mod/wavefront/comment.php', array('id' => $comment->wavefrontid, 'delete' => $comment->id));
    
        $output .= '<table cellspacing="0" width="50%" class="boxaligncenter datacomment forumpost">'.
                '<tr class="header"><td class="picture left">'.$this->output->user_picture($user, array('courseid' => $COURSE->id)).'</td>'.
                '<td class="topic starter" align="left"><a name="c'.$comment->id.'"></a><div class="author">'.
                '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$COURSE->id.'">'.
                fullname($user, has_capability('moodle/site:viewfullnames', $context)).'</a> - '.userdate($comment->timemodified).
                '</div></td></tr>'.
                '<tr><td class="left side">'.
                // TODO: user_group picture?
        '</td><td class="content" align="left">'.
        format_text($comment->commenttext, FORMAT_MOODLE).
        '<div class="commands">'.
        (has_capability('mod/wavefront:edit', $context) ? html_writer::link($deleteurl, get_string('delete')) : '').
        '</div>'.
        '</td></tr></table>';
        
        return $output;
    }

    public function display_comments($wavefront, $editing = false) {
        global $DB;
        
        $output = '';
        
        $options = array();
        
        $context = context_module::instance($this->page->cm->id);
        
        if ($wavefront->comments && has_capability('mod/wavefront:addcomment', $context)) {
            $opturl = new moodle_url('/mod/wavefront/comment.php', array('id' => $wavefront->id));
            $options[] = html_writer::link($opturl, get_string('addcomment', 'wavefront'));
        }
        
        if (count($options) > 0) {
            $output .= $this->output->box(implode(' | ', $options), 'center');
        }
        
        if (!$editing && $wavefront->comments && has_capability('mod/wavefront:viewcomments', $context)) {
            if ($comments = $DB->get_records('wavefront_comments', array('wavefrontid' => $wavefront->id), 'timemodified ASC')) {
                foreach ($comments as $comment) {
                    $output .= $this->print_comment($comment, $context);
                }
            }
        }

        return $output;
    }
    
    /**
     * Returns html to display the Wavefront model
     * @param boolean $editing true if the current user can edit the model, else false.
     */
    public function display_model_in_ar($context, $model) {
        
        $fs = get_file_storage();
        $fs_files = $fs->get_area_files($context->id, 'mod_wavefront', 'model', $model->id, "itemid, filepath, filename", false);
        
        // A Wavefront model contains two files
        $modelerr = true;
        $mtl_file = null;
        $obj_file = null;
        $baseurl = null;
        
        foreach ($fs_files as $f) {
            // $f is an instance of stored_file
            $pathname = $f->get_filepath();
            $filename = $f->get_filename();
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            // what type of file is this?
            if($ext === "mtl") {
                $mtl_file = moodle_url::make_pluginfile_url($context->id, 'mod_wavefront', 'model', $model->id, $pathname, $filename);
            } elseif ($ext === "obj") {
                $obj_file = moodle_url::make_pluginfile_url($context->id, 'mod_wavefront', 'model', $model->id, $pathname, $filename);
                $baseurl = moodle_url::make_pluginfile_url($context->id, 'mod_wavefront', 'model', $model->id, $pathname, '');
            }
        }
        
        if($mtl_file != null && $obj_file != null) {
            $modelerr = false;
        }
        
        if (!$modelerr) {
            $attr = array('data-camerafar' =>$model->camerafar,
                          'data-cameraangle' =>  $model->cameraangle,
                          'data-camerax' => $model->camerax,
                          'data-cameray' => $model->cameray,
                          'data-cameraz' => $model->cameraz,            
                          'data-baseurl' => urlencode($baseurl),
                          'data-mtl' => urlencode($mtl_file),
                          'data-obj' => urlencode($obj_file),
                          'id' => 'stage');
            
            $output = html_writer::div('', 'ar-stage', $attr);
        }
            
        return $output;
    }

}