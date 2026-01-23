$(function() {
	var message = $("#login_redirect_message").val() || "";
	var target = $("#login_redirect_target").val() || "";

	if (message !== "") {
		alert(message);
	}
	if (target !== "") {
		window.location = target;
	}
});
