<?php

require_once($CFG->libdir . "/formslib.php");

class notebook_new_session_form extends moodleform {
 
    function definition() {
        global $CFG;
 
        $mform =& $this->_form; // Don't forget the underscore!
        $notebook = $this->_customdata['notebook'];

        $mform->addElement('text', 'session_name', get_string('session_name', 'notebook'));
        $mform->addRule('session_name', 'This field is required', 'required');
        $mform->addElement('textarea', 'directions', get_string('directions', 'notebook'),'wrap="virtual" rows="3" cols="65"');

        $mform->addElement('textarea', 'session_prompts', get_string('session_prompts', 'notebook'),'wrap="virtual" rows="3" cols="65"');
		$mform->setDefault('session_prompts', 'Before I thought..., now I think... | I am wondering... | I was surprised... | I felt affirmed by...');
          
        $this->add_action_buttons();
    }                           
}                               