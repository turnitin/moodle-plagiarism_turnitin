/**
 * Javascript controller for handling rubrics.
 *
 * @copyright Turnitin
 * @author 2024 Isaac Xiong <ixiong@turnitin.com>
 * @module plagiarism_turnitin/new_rubric
 */

define(['jquery',
        'core/templates',
        'core/modal',
        'core/modal_events',
        'plagiarism_turnitin/modal_rubric_manager_launch',
        'plagiarism_turnitin/modal_rubric_view_launch',
        'plagiarism_turnitin/eula_event_listener'
    ],
    function($, Templates, Modal, ModalEvents, ModalRubricManagerLaunch, ModalRubricViewLaunch, EulaEventListener) {
        return {
            newRubric: function() {
                var that = this;
                $('.rubric_manager_launch').on('click', function() {
                    var courseid = $(this).data('courseid');
                    var cmid = $(this).data('cmid');
                    that.rubricCreateModal(ModalRubricManagerLaunch, courseid, cmid);
                });

                $(document).on('click', '.rubric_view', function() {
                    var courseid = $(this).data('courseid');
                    var cmid = $(this).data('cmid');
                    that.rubricCreateModal(ModalRubricViewLaunch, courseid, cmid);
                });

                // Show warning when changing the rubric linked to an assignment.
                $('#id_plagiarism_rubric').mousedown(function () {
                    if ($('input[name="instance"]').val() != '' && $('input[name="rubric_warning_seen"]').val() != 'Y') {
                        if (confirm(M.str.plagiarism_turnitin.changerubricwarning)) {
                            $('input[name="rubric_warning_seen"]').val('Y');
                        }
                    }
                });

                ModalRubricManagerLaunch.refreshRubricSelect();
            },
            rubricCreateModal: function(modalType, courseid, cmid) {
                Modal.create({
                    type: modalType.TYPE,
                    template: modalType.TEMPLATE,
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

                    // Attach the hidden event listener to the modal
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        setTimeout(function() {
                             ModalRubricManagerLaunch.refreshRubricSelect();
                        }, 1500);
                    });

                    EulaEventListener.attach();
                });
            }
        };
    });
