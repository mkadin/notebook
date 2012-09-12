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
 * Prints a particular instance of notebook
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage notebook
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// (Replace notebook with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // notebook instance ID - it should be named as the first character of the module
$sid = optional_param('sid', 0, PARAM_INT); // the session where the notebook is being printed from
if ($id) {
    $cm         = get_coursemodule_from_id('notebook', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $notebook  = $DB->get_record('notebook', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $notebook  = $DB->get_record('notebook', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $notebook->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('notebook', $notebook->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}



require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);


add_to_log($course->id, 'notebook', 'view', "view.php?id={$cm->id}", $notebook->name, $cm->id);

/// Print the page header

$PAGE->set_url('/mod/notebook/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($notebook->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/notebook/css/notebook.css');

notebook_set_display_type($notebook);

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('notebook-'.$somevar);

// Output starts here
echo $OUTPUT->header();
echo $OUTPUT->heading('Ideas to Take Away Notebook');
echo "<div id='back-link'><p><a href='" . $CFG->wwwroot . '/mod/notebook/session.php?id=' . $sid ."'>Back to notebook</a></p></div>"; 
echo "<p><strong>$notebook->name</strong></p>";

$nid = $notebook->id;

$sessions = $DB->get_records('notebook_sessions', array('nid' => $nid));

if ($sessions) {
  //echo $OUTPUT->heading($notebook->name);
  echo "<div class='notebook-session-wrapper'>";
  if ($notebook->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('notebook', $notebook, $cm->id), 'generalbox mod_introbox', 'notebookintro');
  }
  echo "<div class='notebook-print'>";
  foreach ($sessions as $session) {
    echo '<div class="session"><h3><a href="' . $CFG->wwwroot . '/mod/notebook/session.php?id=' . $session->id . '">' . $session->name . '</a></h3>';
    
    $probes = $DB->get_records('notebook_probes', array('sid' => $session->id));
	$pids = array_keys($probes);
	
	$activities = $DB->get_records('notebook_activities', array('sid' => $session->id));
	$aids = array_keys($activities);
			
	$prev_probe_responses = array();
	$prev_activity_responses = array();
	$prev_text_responses = array();
	
	if ($pids) {
		$prev_probe_responses = $DB->get_records_select('notebook_probe_responses', "uid = $USER->id AND pid IN (" . implode(",",$pids) . ") ");
	} 
	
	if ($aids) {
		$prev_activity_responses = $DB->get_records_select('notebook_activity_responses', "uid = $USER->id AND aid IN (" . implode(",",$aids) . ") ");
	} 
		
	$prev_text_responses = $DB->get_record_select('notebook_text_responses', "uid = $USER->id AND sid = $session->id");
	
	echo "<ol>";
	
	echo "<li> Ideas about the MATH CONTENT that I want to remember from this session:";  
	
	if ($prev_text_responses) echo "<p>$prev_text_responses->math</p>";    
    echo '</li>';
  
  	echo "<li> Ideas that I want to apply in my work with my students:";  
	if ($prev_text_responses) echo "<p>$prev_text_responses->students</p>";    
    echo '</li>';
    
    echo "<li>Ideas for using the probes:";
    
    if ($prev_probe_responses) {
    	echo "<table class='print'>";
    	echo "<tr><th>Probe</th><th>Use</th><th>Plans</th></tr>";
    	foreach ($prev_probe_responses as $response) {
    		
    		echo "<tr>";
    		echo "<td class='name'>" . $probes[$response->pid]->name . "</td>";
    		echo "<td class='use'>$response->useradio</td>";
    		echo "<td class='plans'>$response->plans</td>";
    		echo "</tr>";
    	}
    	echo "</table>";   	
    }
    echo "</li>";
    
    echo "<li>Ideas for using the activities:";
    
    if ($prev_activity_responses) {
    	echo "<table class='print'>";
    	echo "<tr><th>Activity</th><th>Use</th><th>Plans</th></tr>";
    	foreach ($prev_activity_responses as $response) {
    		echo "<tr>";
    		echo "<td class='name'>" . $activities[$response->aid]->name . "</td>";
    		echo "<td class='use'>$response->useradio</td>";
    		echo "<td class='plans'>$response->plans</td>";
    		echo "</tr>";
    	}
    	echo "</table>";   	
    }
    echo "</li>";
  
    echo "<li> Closing thoughts on this session:";  
	if ($prev_text_responses) echo "<p>$prev_text_responses->thoughts</p>";    
    echo '</li>';

  	echo "</ol>";
  	echo "</div>";
  }
  
  echo "</div>";
 
  echo "</div>";
}
else {
  echo $OUTPUT->heading('There are no sessions in this notebook.');
}

// Finish the page
echo $OUTPUT->footer();
