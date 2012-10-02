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
 * Prints a particular instance of a session in the notebook module.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage notebook
 * @copyright  2012 EdTech Leaders Online
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

// Include the new problem form.
require_once(dirname(__FILE__) . '/notebook_new_session_form.php');

// Get the notebook activity id from the url.
$nid = optional_param('nid', 0, PARAM_INT); // notebook ID
// Load the notebook activity.
$notebook = $DB->get_record('notebook', array('id' => $nid));
if (!$notebook) {
  print_error('That notebook does not exist!');
}

// Get the course and cm.
$course = $DB->get_record('course', array('id' => $notebook->course));
if ($course->id) {
  $cm = get_coursemodule_from_instance('notebook', $notebook->id, $course->id, false, MUST_EXIST);
}
else {
  error('Could not find the course!');
}

// Moodley goodness.
require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
add_to_log($course->id, 'notebook', 'view', "newsession.php?nid=$nid", $notebook->name, $cm->id);

// Make sure we have an editor.
require_capability('mod/notebook:edit',$context);

// Page header.
$PAGE->set_url('/mod/notebook/newsession.php', array('nid' => $notebook->id));
$PAGE->set_title(format_string("Adding a new session"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('notebook-session-add-form');
$PAGE->requires->css('/mod/notebook/css/notebook.css');

notebook_set_display_type($notebook);

// Output starts here
$mform = new notebook_new_session_form("/mod/notebook/newsession.php?nid={$notebook->id}",array('notebook'=>$notebook));



// If the form was cancelled, return to the problem page.
if ($mform->is_cancelled()) {
  redirect("view.php?s={$notebook->id}");
}
// Otherwise, if there are results from the form ...
else if ($results = $mform->get_data()) {
  // Load the data into a problem object and save it to the DB.
  $session->nid = $nid;
  $session->name = $results->session_name;
  $session->prompts = $results->session_prompts;
  $session->directions = $results-directions;
   
  $DB->insert_record('notebook_sessions', $session);
  redirect("view.php?n={$notebook->id}");
}
else {
  echo $OUTPUT->header();
  echo $OUTPUT->heading("Adding a new session to {$notebook->name}");
  echo "<div class='notebook-session-wrapper'>";
  // Display the form.
  $mform->display();

  echo "</div>";

  // Finish the page
  echo $OUTPUT->footer();
}







