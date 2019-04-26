jQuery(document).ready(function($) {
    $(".pp_turnitin_eula").show();

    $(document).on('mouseover', '.tii_links_container .tii_tooltip', function() {
        $(this).tooltipster({ multiple: true });
        return false;
    });

    // Open an iframe light box containing the Peermark Manager.
    if ($('.plagiarism_turnitin_peermark_manager_pp_launch').length > 0) {
        $('.plagiarism_turnitin_peermark_manager_pp_launch').colorbox({
            iframe:true, width:"802px", height:"772px", opacity: "0.7", className: "peermark_manager",
            onLoad: function() { getLoadingGif(); },
            onCleanup: function() { hideLoadingGif(); },
            onClosed: function() {
                refreshPPPeermarkAssignments();
            }
        });
    }

    // Refresh submissions from settings page.
    $(document).on('click', '.plagiarism_turnitin_refresh_grades', function() {
        $('.plagiarism_turnitin_refresh_grades').hide();
        $('.plagiarism_turnitin_refreshing_grades').show();

        $.ajax({
            type: "POST",
            url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
            dataType: "json",
            data: {action: "update_grade", cmid: $('input[name="coursemodule"]').val(), sesskey: M.cfg.sesskey},
            success: function(data) {
                $('.plagiarism_turnitin_refresh_grades').show();
                $('.plagiarism_turnitin_refreshing_grades').hide();
            }
        });
    });

    // Open an iframe light box containing the Peermark reviews.
    $(document).on('click', '.peermark_reviews_pp_launch', function() {
        $('.peermark_reviews_pp_launch').colorbox({
            open:true, iframe:true, width:"802px", height:"772px", opacity: "0.7", className: "peermark_reviews",
            onLoad: function() {
                getLoadingGif();
            },
            onCleanup: function() { hideLoadingGif(); }
        });
        return false;
    });

    // Open an iframe light box containing the Rubric Manager.
    if ($('.rubric_manager_launch').length > 0) {
        $('.rubric_manager_launch').colorbox({
            iframe: true, width: "832px", height: "682px", opacity: "0.7", className: "rubric_manager", transition: "none",
            onLoad: function () {
                lightBoxCloseButton();
                getLoadingGif();
            },
            onCleanup: function () {
                hideLoadingGif();
                // Refresh Rubric drop down in add/update form.
                if ($(this).attr("id") != 'rubric_manager_inbox_launch') {
                    refreshRubricSelect();
                }
                $('#tii_close_bar').remove();
            }
        });
    }

    // Open an iframe light box containing the Rubric View.
    $(document).on('click', '.rubric_view_pp_launch', function() {
        $('.rubric_view_pp_launch').colorbox({
            open:true, iframe:true, width:"832px", height:"772px", opacity: "0.7", className: "rubric_view",
            onLoad: function() {
                lightBoxCloseButton();
                getLoadingGif();
            },
            onCleanup: function() {
                $('#tii_close_bar').remove();
                hideLoadingGif();
            }
        });
        return false;
    });

    // Create new event for submission to be re-sent to Turnitin.
    $(document).on('click', '.pp_resubmit_link', function() {
        $(this).hide();
        $(this).siblings('.pp_resubmitting').removeClass('hidden');
        var that = $(this);

        var submissionid = $(this).prop('id').split("_")[2];
        var forumpost = $('#content_' + submissionid).html();
        var forumdata = $('#forumdata_' + submissionid).html();

        $.ajax({
            type: "POST",
            url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
            dataType: "json",
            data: {action: "resubmit_event", submissionid: submissionid, forumpost: forumpost,
                forumdata: forumdata, sesskey: M.cfg.sesskey},
            success: function(data) {
                that.siblings('.turnitin_status').removeClass('hidden');
                that.siblings('.pp_resubmitting').addClass('hidden');
            },
            error: function(data) {
                that.show();
                that.siblings('.pp_resubmitting').addClass('hidden');
            }
        });

        return false;
    });

    $('.launch_form form').submit();

    // Open an iframe light box containing the EULA View.
    if ($('.pp_turnitin_eula_link').length > 0) {
        $(document).on('click', '.pp_turnitin_eula_link', function(e) {
            e.preventDefault();
            $.colorbox({
                iframe:true, href:this.href, width:"766px", height:"596px", opacity: "0.7", className: "eula_view", scrolling: "false",
                onOpen: function() { getLoadingGif(); },
                onComplete: function() {
                    $(window).on("message", function(ev) {
                        var message = typeof ev.data === 'undefined' ? ev.originalEvent.data : ev.data;

                        // Only make ajax request if message is one of the expected responses.
                        if (message == 'turnitin_eula_declined' || message == 'turnitin_eula_accepted') {
                            $.ajax({
                                type: "POST",
                                url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
                                dataType: "json",
                                data: {action: "actionuseragreement", message: message, sesskey: M.cfg.sesskey},
                                success: function(data) { window.location.reload(); },
                                error: function(data) { window.location.reload(); }
                            });
                        }
                    });
                },
                onCleanup: function() { hideLoadingGif(); }
            });
        });
    }

    // Hide the submission form if the user has never accepted or declined the Turnitin EULA.
    if ($(".pp_turnitin_eula_ignored").length > 0) {
        if ($('.editsubmissionform').length > 0) {
            $('.editsubmissionform').hide();
        }
        if ($('.pp_turnitin_eula').siblings('.mform').length > 0) {
            $('.pp_turnitin_eula').siblings('.mform').hide();
        }
    }

    function lightBoxCloseButton() {
        $('body').append('<div id="tii_close_bar"><a href="#" onclick="jQuery(\'#cboxClose\').click(); return false;">' + M.str.plagiarism_turnitin.closebutton + '</a></div>');
    }

    function getLoadingGif() {
        var img = '<div class="loading_gif"></div>';
        $('#cboxOverlay').after(img);
        var top = $(window).scrollTop() + ($(window).height() / 2);
        $('.loading_gif').css('top', top + 'px');
    }

    function hideLoadingGif() {
        $('.loading_gif').remove();
    }

    // Refresh Peermark assignments stored locally for this module.
    function refreshPPPeermarkAssignments() {
        $.ajax({
            type: "POST",
            url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
            dataType: "json",
            data: {action: "refresh_peermark_assignments", cmid: $('input[name="coursemodule"]').val(), sesskey: M.cfg.sesskey},
            success: function(data) {}
        });
    }

    // Open an iframe light box containing the Peermark Manager.
    if ($('.peermark_manager_launch').length > 0) {
        $('.peermark_manager_launch').colorbox({
            iframe:true, width:"802px", height:"772px", opacity: "0.7", className: "peermark_manager", transition: "none",
            onLoad: function() {
                lightBoxCloseButton();
                getLoadingGif();
            },
            onCleanup:function() {
                $('#tii_close_bar').remove();
                hideLoadingGif();
            },
            onClosed:function() {
                var idStr = $(this).attr("id").split("_");
                refreshPPPeermarkAssignments(idStr[2], 1);
            }
        });
    }

    // Open an iframe light box containing the Peermark Reviews.
    if ($('.peermark_reviews_launch').length > 0) {
        $('.tii_peermark_reviews_launch').colorbox({
            iframe:true, width:"802px", height:"772px", opacity: "0.7", className: "peermark_reviews", transition: "none",
            onLoad: function() {
                lightBoxCloseButton();
                getLoadingGif();
            },
            onCleanup: function() {
                $('#tii_close_bar').remove();
                hideLoadingGif();
            }
        });
    }

    // Show warning when changing the rubric linked to an assignment.
    $('#id_plagiarism_rubric').mousedown(function () {
        if ($('input[name="instance"]').val() != '' && $('input[name="rubric_warning_seen"]').val() != 'Y') {
            if (confirm(M.str.plagiarism_turnitin.changerubricwarning)) {
                $('input[name="rubric_warning_seen"]').val('Y');
            }
        }
    });

    // Open an iframe light box containing the Quickmark Manager.
    if ($('.plagiarism_turnitin_quickmark_manager_launch').length > 0) {
        $('.plagiarism_turnitin_quickmark_manager_launch').colorbox({
            iframe: true, width: "770px", height: "600px", opacity: "0.7", className: "quickmark_manager", transition: "none",
            onLoad: function () {
                lightBoxCloseButton();
                getLoadingGif();
            },
            onCleanup: function () {
                $('#tii_close_bar').remove();
                hideLoadingGif();
            }
        });
    }

    // Get the rubrics belonging to a user from Turnitin and refresh menu accordingly.
    function refreshRubricSelect() {
        var currentRubric = $('#id_plagiarism_rubric').val();
        $.ajax({
            "dataType": 'json',
            "type": "POST",
            "url": "../plagiarism/turnitin/ajax.php",
            "data": {
                action: "refresh_rubric_select", assignment: $('input[name="instance"]').val(),
                modulename: $('input[name="modulename"]').val(), course: $('input[name="course"]').val()
            },
            success: function (data) {
                $($('#id_plagiarism_rubric')).empty();
                var options = data;
                $.each(options, function (i, val) {
                    if (!$.isNumeric(i) && i !== "") {

                        var optgroup = $('<optgroup>');
                        optgroup.attr('label', i);

                        $.each(val, function (j, rubric) {
                            var option = $("<option></option>");
                            option.val(j);
                            option.text(rubric);

                            optgroup.append(option);
                        });

                        $('#id_plagiarism_rubric').append(optgroup);

                    } else {
                        $($('#id_plagiarism_rubric')).append($('<option>', {
                            value: i,
                            text: val
                        }));
                    }
                });

                $('#id_plagiarism_rubric' + ' option[value="' + currentRubric + '"]').attr("selected", "selected");
            }
        });
    }
});
