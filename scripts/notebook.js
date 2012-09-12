function display_confirm(url, linkID) {

	var r=confirm("Did you save your work? Click cancel to return and save.");
	if (r==true) {
	  window.location.href = url;
	} else {
		document.getElementById(linkID).href="#";
	}
	return false;
}
