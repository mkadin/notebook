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
 * Prints a particular instance of a problem in the sort module.
 *
 * @package    mod
 * @subpackage notebook
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

// Add in the classify form.
require_once(dirname(__FILE__) . '/notebook_edit_form.php');

// Grab the sid from the url
$id = optional_param('id', 0, PARAM_INT); // session ID

// Load the session from the url ID
$session = $DB->get_record('notebook_sessions', array('id' => $id));

// If the session is not found, throw an error
if (!$session) {
  error('That session does not exist!');
}

// Load the notebook activity, course, and cm context from the problem, and up the chain.
$notebook = $DB->get_record('notebook', array('id' => $session->nid));
$sessions = $DB->get_records('notebook_sessions', array('nid' => $notebook->id));

$entry = $DB->get_record("notebook_entries", array("uid" => $USER->id, "notebook" => $notebook->id));

$course = $DB->get_record('course', array('id' => $notebook->course));
if ($course->id) {
  $cm = get_coursemodule_from_instance('notebook', $notebook->id, $course->id, false, MUST_EXIST);
}
else {
  error('Could not find the course!');
}

// This is some moodle stuff that seems to be necessary :)
require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

// Log this page view.
add_to_log($course->id, 'notebook', 'view', "session.php?id={$cm->id}", $session->name, $cm->id);

/// Print the page header

  $PAGE->set_url('/mod/notebook/session.php', array('id' => $session->id));
  $PAGE->set_title(format_string($session->name));
  $PAGE->set_heading(format_string($course->fullname));
  $PAGE->set_context($context);
  $PAGE->add_body_class('notebook-session-view');
  $PAGE->set_pagelayout('standard');
  notebook_set_display_type($notebook);


// Add the necssary CSS and javascript

  $PAGE->requires->css('/mod/notebook/css/notebook.css');
  $PAGE->requires->js('/mod/notebook/scripts/notebook.js');


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
		
	$prev_text_responses = $DB->get_records_select('notebook_text_responses', "uid = $USER->id AND sid = $session->id");
	
	
 $mform = new notebook_edit_form("/mod/notebook/session.php?id={$session->id}", array('probes' => $probes, 'activities' => $activities, 'session' => $session));
 
 if ($responses = $mform->get_data()) {
 
 	$timenow = time();
    $newentry->modified = $timenow;
 
 	if ($entry) {
        $newentry->id = $entry->id;
        if (!$DB->update_record("notebook_entries", $newentry)) {
            print_error("Could not update your notebook");
        }
        $logaction = "update entry";
        
    } else {
        $newentry->uid = $USER->id;
        $newentry->notebook = $notebook->id;
        if (!$newentry->id = $DB->insert_record("notebook_entries", $newentry)) {
            print_error("Could not insert a new notebook entry");
        }
        $logaction = "add entry";
    } 
 
  
  if ($pids) {
  	$DB->delete_records_select('notebook_probe_responses',"pid IN (" . implode(",",$pids) . ") AND uid = $USER->id");
  }
  
  if ($aids) {
  	$DB->delete_records_select('notebook_activity_responses',"aid IN (" . implode(",",$aids) . ") AND uid = $USER->id");
  }
  
  $DB->delete_records_select('notebook_text_responses',"sid = $session->id AND uid = $USER->id");
 
  $form_items = array();
  $form_text = array();
  
  foreach ($responses as $key => $response) {
    
    $exploded_key = explode("-",$key);
    
    $keysize = sizeof($exploded_key);    
    
    if ($keysize == 3) {
  
    	list($table, $field, $item_id) = $exploded_key; 		
    	$form_items[$table][$item_id][$field] = $response;
    	
    }  else if ($keysize == 2) {
    		list($table, $field) = $exploded_key;
    		$form_text[$field] = $response;
    }

   }
   
   foreach  ($form_items as $table => $item_ids) {
   		foreach ($item_ids as $item_id => $fields) {
		      $new_response = new stdClass();

		      if ($table == 'probe') { 
		      	$new_response->pid = $item_id;
		      } else {
		      	$new_response->aid = $item_id;
		      }
		      notebook_debug($fields);
		      if (array_key_exists("useradio",$fields))  $new_response->useradio = $fields['useradio'];
			  $new_response->plans = $fields['plans'];
      		  $new_response->uid = $USER->id;

		      $DB->insert_record('notebook_' . $table .'_responses',$new_response);
    	}
    
    }
    

      $new_response = new stdClass();
      $new_response->uid = $USER->id;
      $new_response->sid = $id;
      $new_response->math = $form_text['math'];
      $new_response->students = $form_text['students'];
      $new_response->thoughts = $form_text['thoughts'];
      if (array_key_exists("submit_session",$form_text)) $new_response->submit_session = $form_text['submit_session'];

      $DB->insert_record('notebook_text_responses',$new_response);
  
  echo 'Got data';
  redirect("session.php?id=$id&newSave=1");
}

// set existing data.
$form_data = array();


foreach ($prev_text_responses as $response) {
  
	$form_data['text-math'] = $response->math;  
	$form_data['text-students'] = $response->students; 
	$form_data['text-thoughts'] = $response->thoughts;  
	$form_data['text-submit_session'] = $response->submit_session;  


}

foreach ($prev_probe_responses as $response) {
  
	$form_data['probe-plans-' . $response->pid] = $response->plans; 
	$form_data['probe-useradio-' . $response->pid] = $response->useradio;  

}

foreach ($prev_activity_responses as $response) {
  
	$form_data['activity-plans-' . $response->aid] = $response->plans; 
	$form_data['activity-useradio-' . $response->aid] = $response->useradio;  

}

$mform->set_data($form_data);

 
  // Output starts here
  
echo $OUTPUT->header();
echo $OUTPUT->heading('Ideas to Take Away Notebook');
echo "<h3>$notebook->name</h3>";

$mform->display();

echo "<div class='print-notebook'>";
echo '<a id="print" href="' . $CFG->wwwroot . '/mod/notebook/print.php?n=' . $notebook->id . '&amp;sid=' . $session->id .'" onclick="display_confirm(\'' . $CFG->wwwroot . '/mod/notebook/print.php?n=' . $notebook->id . '&amp;sid=' . $session->id . '\',\'print\'); return false;">Print my notebook</a>';
echo "</div>";

$i = 0;
$num_sessions = count($sessions);
echo "<div class='notebook-session-list'>";
echo "<p><strong>My notebook sessions:</strong></p>";
echo "<ul>";
foreach ($sessions as $session) {
	$class = 'session';
	if ($i == 0) {
        $class = $class . ' first';
    } else if ($i == $num_sessions - 1) {
        $class = 'last';
    }
    $i++;
  echo '<li class="'.$class.'"><a href="' . $CFG->wwwroot . '/mod/notebook/session.php?id=' . $session->id . '">' . $session->name . '</a>';
  echo '</li>';
}
echo "</ul>";
echo "</div>";

   
// Finish the page
echo $OUTPUT->footer();
