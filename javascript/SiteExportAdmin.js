;(function($) {
	$("#ExportSiteBaseUrlType input").live("change", function() {
		$("#ExportSiteBaseUrl").toggle($(this).val() == "fixed");
	});

	$("button#action_doExport").live("click", function() {
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
				var $holder = $("<div></div>").html(data);
				var $el     = $holder.children();

				// If the response does not contain the site exports table, then
				// assume we have a JS response and evaluate it.
				if ($el.length == 1 && $el.is("#Form_EditForm_SiteExports")) {
					$table.replaceWith($el);
					Behaviour.apply("Form_EditForm_SiteExports", true);
				} else {
					eval(data);
				}
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