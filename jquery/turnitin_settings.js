jQuery(document).ready(function($) {
	$('input[name="errors_select_all"]').click(function() {
        if ($(this).prop('checked')) {
            $('.errors_checkbox').prop('checked', true);
        } else {
            $('.errors_checkbox').prop('checked', false);
        }
    });

	$(".pp-resubmit-files").click(function() {
		var submission_ids = [];
        $('.errors_checkbox:checked').each(function(i){
            submission_ids[i] = $(this).val();
        });

		$.ajax({
			type: "POST",
            url: "ajax.php",
            dataType: "json",
            data: {action: "resubmit_events", submission_ids: submission_ids, sesskey: M.cfg.sesskey},
            success: function(data) {
                window.location.href = window.location.href+"&resubmitted=success";
            },
            error: function(data, response) {
                window.location.href = window.location.href+"&resubmitted=errors";
            }
        });
	})

    //Disable/enable resubmit selected files when one or more are selected.
    $(document).on('click', '.errors_checkbox, input[name="errors_select_all"]', function() {
        if ($('.errors_checkbox:checked').length > 0) {
            $('.pp-resubmit-files').removeAttr('disabled');
        } else {
            $('.pp-resubmit-files').attr('disabled', 'disabled');
        }
    });
});