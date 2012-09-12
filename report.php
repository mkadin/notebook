<?php

// This script uses installed report plugins to print notebook reports

    require_once('../../config.php');
    require_once($CFG->dirroot.'/mod/notebook/locallib.php');
    //require_once($CFG->dirroot.'/mod/notebook/report/reportlib.php');

    $id = optional_param('id',0,PARAM_INT);    // Course Module ID, or
    $n = optional_param('n',0,PARAM_INT);     // notebook ID

    $mode = optional_param('mode', '', PARAM_ALPHA);        // Report mode

    if ($id) {
        if (! $cm = get_coursemodule_from_id('notebook', $id)) {
            print_error('invalidcoursemodule');
        }

        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error('coursemisconf');
        }

        if (! $notebook = $DB->get_record('notebook', array('id' => $cm->instance))) {
            print_error('invalidcoursemodule');
        }

    } else {
        if (! $notebook = $DB->get_record('notebook', array('id' => $n))) {
            print_error('invalidnotebookid', 'notebook');
        }
        if (! $course = $DB->get_record('course', array('id' => $notebook->course))) {
            print_error('invalidcourseid');
        }
        if (! $cm = get_coursemodule_from_instance("notebook", $notebook->id, $course->id)) {
            print_error('invalidcoursemodule');
        }
    }
	
	$url = new moodle_url('/mod/notebook/report.php', array('id' => $cm->id));
    if ($mode !== '') {
        $url->param('mode', $mode);
    }
    $PAGE->set_url($url);

    require_login($course, false, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $PAGE->set_pagelayout('report');
    
    add_to_log($course->id, "notebook", "report", "report.php?id=$cm->id", "$notebook->id", "$cm->id");

	echo $OUTPUT->header();
	echo $OUTPUT->heading('Notebook grade report');	
	
	// make some easy ways to access the entries.
	if ( $notebook_entries = $DB->get_records("notebook_entries", array("notebook" => $notebook->id))) {
	    foreach ($notebook_entries as $entry) {
	        $entrybyuser[$entry->uid] = $entry;
	        $entrybyentry[$entry->id]  = $entry;
	    }
	
	} else {
	    $entrybyuser  = array () ;
	    $entrybyentry = array () ;
	}
	
	// Group mode
	$groupmode = groups_get_activity_groupmode($cm);
	$currentgroup = groups_get_activity_group($cm, true);
	

	add_to_log($course->id, "notebook", "view responses", "report.php?id=$cm->id", "$notebook->id", $cm->id);
	
	/// Print out the notebook entries
	

	if ($currentgroup) {
	    $groups = $currentgroup;
	} else {
	    $groups = '';
	}

	$users = get_users_by_capability($context, 'mod/notebook:addentries', '', '', '', '', $groups);
	
	if (!$users) {
		echo $OUTPUT->heading(get_string("nousersyet"));
	
	} else {
	    
	    groups_print_activity_menu($cm, $CFG->wwwroot . "/mod/notebook/report.php?id=$cm->id");
	
	    $grades = make_grades_menu($notebook->grade);
	    if (!$teachers = get_users_by_capability($context, 'mod/notebook:edit')) {
	        print_error('noentriesmanagers', 'journal');
	    }
	
	    $allowedtograde = (groups_get_activity_groupmode($cm) != VISIBLEGROUPS OR groups_is_member($currentgroup));
	
	    /*
if ($allowedtograde) {
	        echo '<form action="report.php" method="post">';
	    }
*/
	
	    if ($usersdone = notebook_get_users_done($notebook, $currentgroup)) {
	        foreach ($usersdone as $user) {
	            notebook_print_user_entry($course, $user, $entrybyuser[$user->id], $teachers, $grades);
	            unset($users[$user->id]);
	        }
	    }
	
	    foreach ($users as $user) {       // Remaining users
	        notebook_print_user_entry($course, $user, NULL, $teachers, $grades);
	    }
	
	    /*
if ($allowedtograde) {
	        echo "<center>";
	        echo "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />";
	        echo "<input type=\"submit\" value=\"".get_string("saveallfeedback", "notebook")."\" />";
	        echo "</center>";
	        echo "</form>";
	    }
*/
	}
		

/// Print footer

    echo $OUTPUT->footer();


