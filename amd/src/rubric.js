/**
 * Javascript controller for handling rubrics.
 *
 * @package   turnitin
 * @copyright Turnitin
 * @author 2019 David Winn <dwinn@turnitin.com>
 * @module plagiarism_turnitin/rubric
 */

define(['jquery',
        'core/templates',
        'core/modal_factory',
        'core/modal_events',
        'plagiarism_turnitin/modal_rubric_manager_launch',
        'plagiarism_turnitin/modal_rubric_view_launch'
    ],
    function($, Templates, ModalFactory, ModalEvents, ModalRubricManagerLaunch, ModalRubricViewLaunch) {
        return {
            rubric: function() {
                var that = this;
                $('.rubric_manager_launch').on('click', function() {
                    var courseid = $(this).data('courseid');
                    var cmid = $(this).data('cmid');
                    that.rubricCreateModal(ModalRubricManagerLaunch.TYPE, courseid, cmid);
                });

                $(document).on('click', '.rubric_view', function() {
                    var courseid = $(this).data('courseid');
                    var cmid = $(this).data('cmid');
                    that.rubricCreateModal(ModalRubricViewLaunch.TYPE, courseid, cmid);
                });

                // Show warning when changing the rubric linked to an assignment.
                $('#id_plagiarism_rubric').mousedown(function () {
                    if ($('input[name="instance"]').val() != '' && $('input[name="rubric_warning_seen"]').val() != 'Y') {
                        if (confirm(M.str.plagiarism_turnitin.changerubricwarning)) {
                            $('input[name="rubric_warning_seen"]').val('Y');
                        }
                    }
                });
            },
            rubricCreateModal: function(modalType, courseid, cmid) {
                ModalFactory.create({
                    type: modalType,
                    templateContext: {
                        courseid: courseid,
                        cmid: cmid,
                        wwwroot: M.cfg.wwwroot
                    },
                    large: true
                })
                    .then(function (modal) {
                        modal.show();
                        modal.getRoot().find('.modal').addClass('tii_pp_modal_rubric');
                        modal.getRoot().find('.modal-content').addClass('tii_pp_modal_rubric_content');
                    });
            }
        };
    });
