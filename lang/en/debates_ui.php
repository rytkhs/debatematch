<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Debate UI Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for the debate interface and
    | real-time debate UI components throughout the application.
    |
    */

    // Chat and Messages
    'all' => 'All',
    'affirmative_side_label' => 'Affirmative',
    'negative_side_label' => 'Negative',
    'no_messages_yet' => 'No messages yet.',
    'new_message' => 'New Message',
    'enter_message_placeholder' => 'Enter your message...',
    'send_message' => 'Send Message',
    'resize_input_area' => 'Resize Handle',
    'expand_input_area' => 'Maximize',
    'shrink_input_area' => 'Minimize',
    'toggle_input_visibility' => 'Show/Hide',

    // Turn Management
    'your_turn' => 'Your Turn',
    'prep_time_turn' => 'Prep Time',
    'opponent_turn' => "Opponent's Turn",
    'confirm_end_turn' => 'End the :currentTurnName turn and proceed to :nextTurnName?',
    'end_turn' => 'End Speech',
    'current_turn_info' => 'Current Turn Info',

    // Time Management
    'remaining_time' => 'Remaining',
    'remaining_time_label' => 'Remaining:',
    'prep_time_in_progress' => 'Prep time in progress...',
    'questioning_in_progress' => 'Cross Examination in progress...',

    // Participants
    'debaters' => 'Debaters',
    'speaking' => 'Speaking',
    'online' => 'Online',
    'offline' => 'Offline',

    // Status Messages
    'ready_to_send' => 'Ready to send',
    'cannot_send_message_now' => 'Cannot send message now',
    'progress' => 'Progress',
    'questions_allowed' => 'CX Allowed',
    'completed' => 'Completed',

    // Tabs (if not in records.php)
    'debate_information_tab' => 'Debate Information',
    'timeline_tab' => 'Timeline',

    // Debate Finish
    'debate_finished_title' => 'Debate Finished',
    'evaluating_message' => 'Evaluating with AI. Please wait a moment...',
    'evaluation_complete_title' => 'Debate Evaluation Complete',
    'redirecting_to_results' => 'Redirecting to the results page.',
    'host_left_terminated' => 'The debate has been terminated because the opponent disconnected.',
    'debate_finished_overlay_title' => 'Debate Finished',
    'evaluating_overlay_message' => 'The debate has finished. AI evaluation is currently in progress...',
    'go_to_results_page' => 'Go to Results Page',

    // Connection Messages
    'connection_lost_title' => 'Connection Lost',
    'connection_lost_message' => 'Connection to the server was lost. Attempting to reconnect...',
    'reconnecting_message' => 'Reconnecting...',
    'reconnecting_failed_message' => 'Reconnection failed. Please reload the page.',
    'redirecting_after_termination' => 'Redirecting to the top page in 5 seconds...',

    // Early Termination
    'early_termination_request' => 'Propose Early End',
    'early_termination_requested' => 'Early end has been proposed',
    'early_termination_request_failed' => 'Failed to propose early end',
    'early_termination_agree' => 'Agree',
    'early_termination_decline' => 'Decline',
    'early_termination_agreed' => 'Early end agreed. Ending debate.',
    'early_termination_declined' => 'Early end declined. Continuing debate.',
    'early_termination_response_failed' => 'Failed to respond to early end',
    'early_termination_proposal' => ':name has proposed early end',
    'early_termination_waiting_response' => 'Waiting for opponent\'s response...',
    'early_termination_proposal_expired' => 'Early end proposal has expired',
    'early_termination_timeout_message' => 'The early end proposal has expired after 1 minute. Continuing the debate.',
    'early_termination_expired_notification' => 'Early end proposal has timed out',
    'early_termination_completed' => 'The debate has been ended early.',
    'early_termination_proposal_sent' => 'Early end proposal sent',
    'early_termination_response_sent_agree' => 'Agreement to early end sent',
    'early_termination_response_sent_decline' => 'Decline to early end sent',
    'early_termination_agreed_result' => 'Early end agreed. Ending debate.',
    'early_termination_declined_result' => 'Early end declined. Continuing debate.',
    'early_termination_ai_desc' => 'You can end the debate early',
    'early_termination_human_desc' => 'You can propose early termination of the debate',
    'early_termination_ai_button' => 'End Early',
    'early_termination_human_button' => 'Propose Early End',
    'early_termination_participant_only' => ' is only available to participants',
    'early_termination_wait_response' => 'Please wait for the opponent\'s response',
];
