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
 * Creates new probes and edits existing ones.
 *
 * @package    mod
 * @subpackage notebook
 * @copyright  2012 EdTech Leaders Online
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

// Include the edit form.
require_once(dirname(__FILE__) . '/notebook_edit_probe_form.php');

// Pull the sid and/or pid from the url.
$sid = optional_param('sid', 0, PARAM_INT); // session ID
$pid = optional_param('pid', 0, PARAM_INT); // probe ID
// Get the session from the sid.
$session = $DB->get_record('notebook_sessions', array('id' => $sid));
if (!$session) {
  print_error('That session does not exist!');
}

// Get the notebook activity, course, etc from the problem.
$notebook = $DB->get_record('notebook', array('id' => $session->nid));
$course = $DB->get_record('course', array('id' => $notebook->course));
if ($course->id) {
  $cm = get_coursemodule_from_instance('notebook', $notebook->id, $course->id, false, MUST_EXIST);
}
else {
  error('Could not find the course / notebook activity!');
}

// Moodley goodness.
require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
add_to_log($course->id, 'notebook', 'view', "editprobes.php?sid=$sid", $session->name, $cm->id);


// Only editors can see this page.
require_capability('mod/notebook:edit', $context);

// Set the page header. Needs to happen before the form code in order to stick, but I'm not sure why - CR
$PAGE->set_url('/mod/notebook/editprobes.php', array('sid' => $sid, 'pid' => $pid));
$PAGE->set_title(format_string("Editing Probes."));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('notebook-probe-edit-form');

// Sort CSS styles.
$PAGE->requires->css('/mod/notebook/css/notebook.css');

notebook_set_display_type($notebook);

// All probes for the session.
$probes = $DB->get_records('notebook_probes', array('sid' => $session->id));

$probe = NULL;

// If there's a pid in the url, we're editing an exisitng probe
if ($pid != 0) {
  // Get a probe to load 
  $probe = $DB->get_record('notebook_probes', array('id' => $pid));
  // If there are no probes, the pid is funky.
  if (!$probe) {
    print_error('Can not find any probes');
  }
  // This helps with the form.  probename is the form element's name
  $probe->probename = $probe->name;
  $probename = $probe->name;
}


// Load the form.
$mform = new notebook_edit_probe_form("/mod/notebook/editprobes.php?sid=$sid&pid=$pid", array('probes' => $probes, 'this_probe' => $probe));

// If the form was cancelled, redirect.
if ($mform->is_cancelled()) {
  redirect("session.php?id=$sid");
}
else {

  
  if ($probe) {
  //Set up the draft area.
  
    // Put the existing data into the form.
  $mform->set_data($probe);
  }
  // If there's data in the form...
  if ($results = $mform->get_data()) {
    
    // If the the data is for a new probe...
    if ($pid == 0) {
      // Save the probe as a new record.
      $probe->sid = $sid;
      $probe->name = $results->probename;
      $new_record = $DB->insert_record('notebook_probes', $probe);
    }
    else {
      // We're updaing existing work.
      $probe->name = $results->probename;
      $updated_record = $DB->update_record('notebook_probes', $probe);
    }
    // Now redirect back to the problem page with the new / updated data.
    redirect("editprobes.php?sid=$sid");
  }
}

// Begin page output
echo $OUTPUT->header();
echo $OUTPUT->heading("Manage Probes for {$session->name}");

echo "<div class='notebook-probe-wrapper'>";

echo "<div class='notebook-probe-pager'>";
echo "<h4>Select a probe to edit,<br /> or click \"Add New\" to create a new probe.</h4>";
echo "<ul>";
foreach ($probes as $probe) {
  $class = ($pid == $probe->id) ? "class=\"notebook-pager-current\"" : ""; 
  echo '<li ' . $class . '><a href="' . $CFG->wwwroot . '/mod/notebook/editprobes.php?sid=' . $probe->sid . '&amp;pid=' . $probe->id . '">' . $probe->name . '</a></li>';
}
$class = (!$pid) ? ' class="notebook-pager-current" ' : "";
echo '<li' . $class . '><a href="' . $CFG->wwwroot . '/mod/notebook/editprobes.php?sid=' . $session->id . '">Add New</a></li>';
echo "</ul>";
echo "</div>";

echo "<div class='notebook-manage-form-wrapper'>";
if ($pid) echo "<p class='notebook-delete-link'><a href='deleteprobe.php?pid=$pid'>Delete this probe [not implemented yet]</a></p>";
if ($pid) echo "<h4>Editing $probename</h4>";
else echo "<h4>Adding a new Probe</h4>";

//displays the form
$mform->display();


echo "</div>";
echo "<div class='notebook-action-links'>";
echo '<span class="notebook-back-link-box"><a href="view.php?id=' . $cm->id . '">Back to the notebook</a></span>';
echo "</div>";
echo "</div>";

// Finish the page
echo $OUTPUT->footer();









