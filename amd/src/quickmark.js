/**
 * Javascript controller for launching a Quickmark modal.
 *
 * @copyright Turnitin
 * @author 2019 David Winn <dwinn@turnitin.com>
 * @module plagiarism_turnitin/quickmarkLaunch
 */

define(['jquery',
        'core/templates',
        'core/modal',
        'core/modal_events',
        'plagiarism_turnitin/modal_quickmark_launch'
    ],
    function($, Templates, Modal, ModalEvents, ModalQuickmarkLaunch) {
        return {
            quickmarkLaunch: function() {
                $('.plagiarism_turnitin_quickmark_manager_launch').on('click', function (event) {
                    event.preventDefault();
                    Modal.create({
                        type: ModalQuickmarkLaunch.TYPE,
                        templateContext: {
                            cmid: $('input[name="coursemodule"]').val(),
                            wwwroot: M.cfg.wwwroot
                        },
                        large: true
                    })
                        .then(function (modal) {
                            modal.show();
                        });
                });
            }
        };
    });