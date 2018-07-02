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
                window.location.href = window.location.href + "&resubmitted=success";
            },
            error: function(data, response) {
                window.location.href = window.location.href + "&resubmitted=errors";
            }
        });
    })

    // Disable/enable resubmit selected files when one or more are selected.
    $(document).on('click', '.errors_checkbox, input[name="errors_select_all"]', function() {
        if ($('.errors_checkbox:checked').length > 0) {
            $('.pp-resubmit-files').removeAttr('disabled');
        } else {
            $('.pp-resubmit-files').attr('disabled', 'disabled');
        }
    });

    // Show/hide student privacy if the field is selected.
    $("#id_plagiarism_turnitin_enablepseudo").change(function() {
        if ($("#id_plagiarism_turnitin_enablepseudo").val() == "1") {
            $(".studentprivacy").show();
        } else {
            $(".studentprivacy").hide();
        }
    });

    // Store connection test selector.
    var ct = $('#id_connection_test');

    if (ct.length > 0) {

        ct.hide();
        if ($('#id_plagiarism_turnitin_accountid').val() != '' || $('#id_plagiarism_turnitin_secretkey').val() != '') {
            ct.show();
        }

        $('#id_plagiarism_turnitin_accountid, #id_plagiarism_turnitin_secretkey, #id_plagiarism_turnitin_apiurl').keyup(function () {
            ct.hide();

            var accountid = $('#id_plagiarism_turnitin_accountid').val();
            var accountshared = $('#id_plagiarism_turnitin_secretkey').val();

            // Make sure they aren't empty strings.
            accountid = accountid.trim();
            accountshared = accountshared.trim();
            if (accountid.length == 0 || accountshared.length == 0) {
                ct.hide();
            } else {
                ct.show();
            }
        });

        $('#id_connection_test').click(function() {
            // Change Url depending on Settings page.
            var url = "ajax.php";
            if ($('.settingsform fieldset div.formsettingheading').length > 0) {
                url = "../plagiarism/turnitin/ajax.php";
            }

            var accountid = $('#id_plagiarism_turnitin_accountid').val();
            var accountshared = $('#id_plagiarism_turnitin_secretkey').val();
            var accounturl = $('#id_plagiarism_turnitin_apiurl').val();

            $.ajax({
                type: "POST",
                url: url,
                dataType: "json",
                data: {
                    action: "test_connection",
                    sesskey: M.cfg.sesskey,
                    accountid: accountid,
                    accountshared: accountshared,
                    url: accounturl
                },
                success: function (data) {
                    eval(data);

                    if (data.connection_status === 200) {
                        ct.removeClass("connection-test-failed");
                        ct.addClass("connection-test-success");

                        changeString(ct, M.str.plagiarism_turnitin.connecttestsuccess);
                    } else {
                        ct.removeClass("connection-test-success");
                        ct.addClass("connection-test-failed");

                        changeString(ct, M.str.plagiarism_turnitin.connecttestfailed);
                    }

                    // Fade out classes and swap back values.
                    ct.delay(1000).fadeOut("slow", function() {
                        $(this).removeClass("connection-test-failed");
                        $(this).removeClass("connection-test-success");

                        changeString(ct, M.str.plagiarism_turnitin.connecttest);
                    }).fadeIn("slow");
                    $('#testing_container').hide();
                }
            });
        });
    }

    // Configure datatables language settings.
    var dataTablesLang = {
        "nointegration": M.str.plagiarism_turnitin.nointegration,
        "sProcessing": M.str.plagiarism_turnitin.sprocessing,
        "sZeroRecords": M.str.plagiarism_turnitin.szerorecords,
        "sInfo": M.str.plagiarism_turnitin.sinfo,
        "sSearch": M.str.plagiarism_turnitin.ssearch,
        "sLengthMenu": M.str.plagiarism_turnitin.slengthmenu,
        "sInfoEmpty": M.str.plagiarism_turnitin.semptytable,
        "oPaginate": {
            "sNext": M.str.plagiarism_turnitin.snext,
            "sPrevious": M.str.plagiarism_turnitin.sprevious
        }
    };

    // Configure the unlink and relink users datatable in the plugin settings area.
    $('#unlinkUserTable').dataTable({
        "bDestroy": true,
        "bProcessing": true,
        "bServerSide": true,
        "oLanguage": dataTablesLang,
        "aaSorting": [[ 2, "asc" ]],
        "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "sAjaxSource": "ajax.php?action=get_users",
        "aoColumns": [
            {"bSortable": false,
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    $(nTd).addClass('centered_cell');
                }},
            null,
            {"aDataSort": [ 2, 3 ]},
            {"aDataSort": [ 3, 2 ]},
            null
        ],
        "fnDrawCallback": function () {
            $('input[name="selectallcb"]').attr('checked', false);
        }
    });

    /**
     * Helper function to change the button text depending on which type of element we're handling.
     * @param {jQuery} ct - The button element - may be input or button depending on the Moodle theme.
     * @param {String} langString  - The language string we're setting.
     */
    function changeString(ct, langString) {
        if (ct.get(0).tagName === "BUTTON") {
            ct.text(langString);
        } else {
            ct.attr('value', langString);
        }
    }
});