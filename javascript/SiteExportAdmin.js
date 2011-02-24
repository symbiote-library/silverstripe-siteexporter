;(function($) {
	$("#ExportSiteBaseUrlType input").live("change", function() {
		$("#ExportSiteBaseUrl").toggle($(this).val() == "fixed");
	});

	$("#action_doExport").live("click", function() {
		var $button = $(this).addClass("loading");
		var $form   = $(this).parents("form");
		var $table  = $form.find("#Form_EditForm_SiteExports");
		var action  = $form.attr("action") + '?action_doExport=1';

		var origText = $button.text();
		$button.text("Exporting...");

		$.ajax({
			type: "POST",
			url: action,
			data: $form.serialize(),
			success: function(data) {
				$table.replaceWith(data);
				Behaviour.apply("Form_EditForm_SiteExports", true);
			},
			error: function() {
				statusMessage("Could not export site.", "bad");
			},
			complete: function(xhr, status) {
				if (status == 'success') {
					statusMessage(xhr.statusText, "good");
				}

				$button.removeClass("loading").text(origText);
			}
		});

		return false;
	});
})(jQuery);