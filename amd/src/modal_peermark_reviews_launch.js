/**
 * Javascript controller for Peermark Reviews launcher
 *
 * @copyright Turnitin
 * @author 2019 David Winn <dwinn@turnitin.com>
 * @module plagiarism_turnitin/modal_peermark_reviews_launch
 */

define(
    [
        'jquery',
        'core/ajax',
        'core/notification',
        'core/custom_interaction_events',
        'core/modal',
        'core/modal_events'
    ],
    function($, Ajax, Notification, CustomEvents, Modal, ModalEvents) {

        var registered = false;
        var SELECTORS = {
            HIDE_BUTTON: '[data-action="hide"]',
            MODAL: '[data-region="modal"]'
        };

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var ModalPeermarkReviewsLaunch = function(root) {
            Modal.call(this, root);
        };

        ModalPeermarkReviewsLaunch.TYPE = 'plagiarism_turnitin-modal_peermark_reviews_launch';
        ModalPeermarkReviewsLaunch.TEMPLATE = 'plagiarism_turnitin/modal_peermark_reviews_launch';
        ModalPeermarkReviewsLaunch.prototype = Object.create(Modal.prototype);
        ModalPeermarkReviewsLaunch.prototype.constructor = ModalPeermarkReviewsLaunch;

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        ModalPeermarkReviewsLaunch.prototype.registerEventListeners = function() {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);

            // On cancel, then hide the modal.
            this.getModal().on(CustomEvents.events.activate, SELECTORS.HIDE_BUTTON, function(e, data) {
                var cancelEvent = $.Event(ModalEvents.cancel);
                this.getRoot().trigger(cancelEvent, this);

                if (!cancelEvent.isDefaultPrevented()) {
                    this.hide();
                    data.originalEvent.preventDefault();
                }
            }.bind(this));
        };

        return ModalPeermarkReviewsLaunch;
    }
);