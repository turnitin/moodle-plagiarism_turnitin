jQuery(document).ready(function($) {

    $(document).on('mouseover', '.tii_links_container .tii_tooltip', function() {
        $(this).tooltipster({ multiple: true });
        return false;
    });

    $(document).on('click', '.pp_origreport_open', function() {
        var classList = $(this).attr('class').replace(/\s+/,' ').split(' ');

        for (var i = 0; i < classList.length; i++) {
            if (classList[i].indexOf('origreport_') !== -1 && classList[i] != 'pp_origreport_open') {
                var classStr = classList[i].split("_");
                var url = "";
                // URL must be stored in separate div on forums.
                if ($('.origreport_forum_launch_' + classStr[1]).length > 0) {
                    url = $('.origreport_forum_launch_' + classStr[1]).html();
                } else {
                    url = $(this).attr("id");
                }
                openDV("origreport", classStr[1], classStr[2], url);
            }
        }
    });

    $(document).on('click', '.pp_grademark_open', function() {
        var classList = $(this).attr('class').replace(/\s+/,' ').split(' ');

        for (var i = 0; i < classList.length; i++) {
            if (classList[i].indexOf('grademark_') !== -1 && classList[i] != 'pp_grademark_open') {
                var classStr = classList[i].split("_");
                var url = "";
                // URL must be stored in separate div on forums.
                if ($('.grademark_forum_launch_' + classStr[1]).length > 0) {
                    url = $('.grademark_forum_launch_' + classStr[1]).html();
                } else {
                    url = $(this).attr("id");
                }
                openDV("grademark", classStr[1], classStr[2], url);
            }
        }
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
    if ($(".pp_turnitin_ula_ignored").length > 0) {
        if ($('.editsubmissionform').length > 0) {
            $('.editsubmissionform').hide();
        }
        if ($('.pp_turnitin_ula').siblings('.mform').length > 0) {
            $('.pp_turnitin_ula').siblings('.mform').hide();
        }
    }

    function lightBoxCloseButton(closeBtnText) {
        $('body').append('<div id="tii_close_bar"><a href="#" onclick="$.colorbox.close(); return false;">' + M.str.plagiarism_turnitin.closebutton + '</a></div>');
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

    // Open the DV in a new window in such a way as to not be blocked by popups.
    function openDV(dvtype, submissionid, coursemoduleid, url) {
        dvWindow = window.open('', '_blank');
        var loading = '<div class="tii_dv_loading" style="text-align:center;">';
        loading += '<img src="' + M.cfg.wwwroot + '/plagiarism/turnitin/pix/tiiIcon.svg" style="width:100px; height: 100px">';
        loading += '<p style="font-family: Arial, Helvetica, sans-serif;">' + M.str.plagiarism_turnitin.loadingdv + '</p>';
        loading += '</div>';
        $(dvWindow.document.body).html(loading);

        // Get html to launch DV.
        $.ajax({
            type: "POST",
            url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
            dataType: "json",
            data: {action: "get_dv_html", submissionid: submissionid, dvtype: dvtype,
                cmid: coursemoduleid, sesskey: M.cfg.sesskey},
            success: function(data) {
                $(dvWindow.document.body).html(loading + data);
                dvWindow.document.forms[0].submit();
                dvWindow.document.close();

                checkDVClosed(submissionid, coursemoduleid);
            }
        });
    }

    // Check whether the DV is still open, refresh the opening window when it closes.
    function checkDVClosed(submissionid, coursemoduleid) {
        if (window.dvWindow.closed) {
            refreshScores(submissionid, coursemoduleid);
        } else {
            setTimeout( function(){
                            checkDVClosed(submissionid, coursemoduleid);
            }, 500);
        }
    }

    function refreshScores(submission_id, coursemoduleid) {
        $.ajax({
            type: "POST",
            url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
            dataType: "json",
            data: {action: "update_grade", submission: submission_id, cmid: coursemoduleid, sesskey: M.cfg.sesskey},
            success: function(data) {
                window.location = window.location;
            }
        });
    }

    // Update the DB value for EULA accepted.
    function userAgreementAccepted( user_id ){
        $.ajax({
            type: "POST",
            url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
            dataType: "json",
            data: {action: 'acceptuseragreement', user_id: user_id},
            success: function(data) {
                window.location = window.location;
            }
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
});
