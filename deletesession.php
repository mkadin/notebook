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
 * Creates new pieces of student work and edits existing ones.
 *
 * @package    mod
 * @subpackage notebook
 * @copyright  2012 EdTech Leaders Online
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

// Pull the pid
$sid = optional_param('sid', 0, PARAM_INT); // session id
$confirm = optional_param('confirm', 0, PARAM_INT); // 

// Get the problem from the pid
$session = $DB->get_record('notebook_sessions', array('id' => $sid));
if (!$session) {
  print_error('That session does not exist.  It cannot be deleted');
}

$notebook = $DB->get_record('notebook', array('id' => $session->nid));
$course = $DB->get_record('course', array('id' => $notebook->course));
if ($course->id) {
  $cm = get_coursemodule_from_instance('notebook', $notebook->id, $course->id, false, MUST_EXIST);
}
else {
  error('Could not find the notebook activity!');
}

// Moodley goodness.
require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
add_to_log($course->id, 'notebook', 'view', "deletesession.php?sid=$sid", "Deleting session", $cm->id);


// Only editors can see this page.
require_capability('mod/notebook:edit', $context);


if ($confirm && $sid) {
  $probes = $DB->get_records('notebook_probes',array('sid' => $session->id));
  if ($probes) {
    $DB->delete_records_list('notebook_probe_responses', 'pid', array_keys($probes));
    $DB->delete_records('notebook_probes', array('sid' => $session->id));
  }
  
  $activities = $DB->get_records('notebook_activities',array('sid' => $session->id));
  if ($activities) {
    $DB->delete_records_list('notebook_activity_responses', 'aid', array_keys($activities));
    $DB->delete_records('notebook_activities', array('sid' => $session->id));
  }
  
  $DB->delete_records('notebook_text_responses', array('sid' => $session->id));
  
  $DB->delete_records('notebook_sessions',array('id' => $session->id));
  
  redirect("view.php?n=$notebook->id");
}

// Set the page header.
$PAGE->set_url('/mod/notebook/deletesession.php', array('sid' => $sid));
$PAGE->set_title(format_string("Delete Session."));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('notebook-delete-session');

// Sort CSS styles.
$PAGE->requires->css('/mod/notebook/css/notebook.css');

notebook_set_display_type($notebook);

// Begin page output
echo $OUTPUT->header();



echo $OUTPUT->confirm("Are you sure you want to delete $session->name?  Any notebook entries will be lost.","deletesession.php?sid=$sid&confirm=1","view.php?n=$notebook->id");

echo $OUTPUT->footer();










