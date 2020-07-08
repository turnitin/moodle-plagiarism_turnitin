/**
 * Javascript controller for launching a Peermark modal.
 *
 * @package   turnitin
 * @copyright Turnitin
 * @author 2019 David Winn <dwinn@turnitin.com>
 * @module plagiarism_turnitin/peermarkLaunch
 */

define(['jquery',
        'core/templates',
        'core/modal_factory',
        'core/modal_events',
        'plagiarism_turnitin/modal_peermark_manager_launch',
        'plagiarism_turnitin/modal_peermark_reviews_launch'
    ],
    function($, Templates, ModalFactory, ModalEvents, ModalPeermarkManagerLaunch, ModalPeermarkReviewsLaunch) {
        return {
            peermarkLaunch: function() {
                var that = this;
                $('.peermark_manager_launch').on('click', function(event) {
                    event.preventDefault();
                    that.peermarkCreateModal(ModalPeermarkManagerLaunch.TYPE);
                });

                $(document).on('click', '.peermark_reviews_pp_launch', function() {
                    that.peermarkCreateModal(ModalPeermarkReviewsLaunch.TYPE);
                });
            },
            peermarkCreateModal: function(modalType) {

                if ($('input[name="coursemodule"]').val()) {
                    var cmid = $('input[name="coursemodule"]').val();
                } else {
                    var urlParams = new URLSearchParams(window.location.search);
                    var cmid = urlParams.get('id');
                }
                ModalFactory.create({
                    type: modalType,
                    templateContext: {
                        cmid: cmid,
                        wwwroot: M.cfg.wwwroot
                    },
                    large: true
                })
                    .then(function (modal) {
                        modal.show();
                    });
            }
        };
    });