jQuery(document).ready(function($) {
	$("#list_locations_accordion").accordion({
		heightStyle: "content",
		active: false,
		collapsible: true
	});
	$(".list_locations_service_body_select").chosen({
		inherit_select_classes: true,
		width: "50%"
	});
});
