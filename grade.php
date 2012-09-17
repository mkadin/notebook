<?php

require_once("../../config.php");

$id   = required_param('id', PARAM_INT);          // Course module ID

$PAGE->set_url('/mod/notebook/grade.php', array('id'=>$id));
if (! $cm = get_coursemodule_from_id('notebook', $id)) {
    print_error('invalidcoursemodule');
}

if (! $notebook = $DB->get_record("notebook", array("id"=>$cm->instance))) {
    print_error('invalidid', 'notebook');
}

if (! $course = $DB->get_record("course", array("id"=>$notebook->course))) {
    print_error('coursemisconf', 'notebook');
}

require_login($course, false, $cm);

if (has_capability('mod/notebook:grade', get_context_instance(CONTEXT_MODULE, $cm->id))) {
    redirect('report.php?id='.$cm->id);
} else {
    redirect('view.php?id='.$cm->id);
}