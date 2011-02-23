;(function($) {
	$("#ExportSiteBaseUrlType input").live("change", function() {
		$("#ExportSiteBaseUrl").toggle($(this).val() == "fixed");
	});
})(jQuery);