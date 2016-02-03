<?php

class pp_receipt_message {

    /**
     * Send digital receipt to submitter
     *
     * @param string $message
     * @return void
     */
    public function send_message($userid, $message) {

        $subject = get_string('digital_receipt_subject', 'plagiarism_turnitin');

        $eventdata = new stdClass();
        $eventdata->component         = 'plagiarism_turnitin'; //your component name
        $eventdata->name              = 'submission'; //this is the message name from messages.php
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->userto            = $userid;
        $eventdata->subject           = $subject;
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $message;
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1; //this is only set to 0 for personal messages between users

        message_send($eventdata);
    }

    /**
     * Build message to send to user
     *
     * @param array $input - used to build message
     * @return string
     */
    public function build_message($input) {
        $message = new stdClass();
        $message->firstname = $input['firstname'];
        $message->lastname = $input['lastname'];
        $message->submission_title = $input['submission_title'];
        $message->assignment_name = $input['assignment_name'];
        if ( isset($input['assignment_part']) ) {
            $message->assignment_part = ": " . $input['assignment_part'];
        } else {
            $message->assignment_part = "";
        }
        $message->course_fullname = $input['course_fullname'];
        $message->submission_date = $input['submission_date'];
        $message->submission_id = $input['submission_id'];

        return get_string('pp_digital_receipt_message', 'plagiarism_turnitin', $message);
    }

    /**
     * Build a modified receipt to send to instructors upon a submission being made.
     * This message must preserve the anonymity of a submission.
     *
     * @param array $input - used tob uild message
     * @return string
     */
    public function build_instructor_message($input)
    {
        $message->submission_title = $input['submission_title'];
        $message->assignment_name = $input['assignment_name'];
        if ( isset($input['assignment_part']) ) {
            $message->assignment_part = ": " . $input['assignment_part'];
        } else {
            $message->assignment_part = "";
        }
        $message->course_fullname = $input['course_fullname'];
        $message->submission_date = $input['submission_date'];
        $message->submission_id = $input['submission_id'];

        return get_string('receipt_instructor_copy', 'plagiarism_turnitin', $message);
    }

    /**
     * Send instructor message to instructors on course.
     *
     * @param array $instructors
     * @param string $message
     * @return void
     */
    public function send_instructor_message($instructors, $message)
    {
        $subject = get_string('receipt_instructor_copy_subject', 'plagiarism_turnitin');

        $eventdata = new stdClass();
        $eventdata->component         = 'plagiarism_turnitin'; //your component name
        $eventdata->name              = 'submission'; //this is the message name from messages.php
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->subject           = $subject;
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $message;
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1; //this is only set to 0 for personal messages between users

        foreach ($instructors as $instructor) {
            $eventdata->userto = $instructor;
            message_send($eventdata);
        }
        unset($instructor);
    }
}
