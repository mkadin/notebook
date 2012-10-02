function display_confirm(url) {

	var r=confirm("Did you save your work? Click cancel to return and save.");
	if (r==true) {
	  window.location.href = url;
	} 
	
	return false;
}

