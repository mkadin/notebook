
<?php

require_once($CFG->libdir . "/formslib.php");

class notebook_edit_probe_form extends moodleform {
 
    function definition() {
        global $CFG;
 
        $mform =& $this->_form; // Don't forget the underscore! 
        
        //$probes = $this->_customdata['probes'];
        //$this_probe = $this->_customdata['this_probe'];
        

        $mform->addElement('text', 'probename', 'Name');
        $mform->addRule('probename', 'This field is required', 'required');
        
        $this->add_action_buttons(false);    
    }                           
}                               
