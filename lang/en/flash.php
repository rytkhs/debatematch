<?php

return [
    // DebateController
    'debate.show.finished' => 'The debate has finished.',
    'debate.show.terminated' => 'Disconnected.',
    'debate.terminate.success' => 'The debate was terminated due to disconnection with the opponent.',

    // RoomController
    'room.store.success' => 'Room created successfully.',
    'room.show.forbidden' => 'Cannot access this page.',
    'room.join.already_joined' => 'You have already joined this room.',
    'room.join.full' => 'This room is already full.',
    'room.join.not_waiting' => 'Cannot join this room.',
    'room.join.success' => 'Joined the room successfully.',
    'room.exit.already_closed' => 'This room is already closed.',
    'room.exit.creator_success' => 'Room deleted successfully.',
    'room.exit.participant_success' => 'Exited the room successfully.',
    'room.start_debate.unauthorized' => 'You do not have permission to start the debate.',

    // AuthenticatedSessionController
    'auth.login.success' => 'Logged in successfully.',
    'auth.logout.success' => 'Logged out successfully.',

    // Livewire/Debates/Chat.php
    'chat.message.received' => 'Received a message.',

    // Livewire/Debates/Header.php
    'header.turn.my_turn' => "It's your speech.",

    // Livewire/Debates/MessageInput.php
    'message_input.send.success' => 'Message sent successfully.',

    // Livewire/Debates/Participants.php
    'participants.turn.advanced' => 'Speech finished.',

    // Livewire/Rooms/StartDebateButton.php
    'start_debate.error.not_enough_participants' => 'Not enough debaters.',
    'start_debate.error.already_started' => 'The debate has already started.',

    // Middleware/CheckUserActiveStatus.php
    'middleware.active_debate' => 'You are in an active debate.',
    'middleware.active_room' => 'You are in a waiting room.',

    // AIDebateController
    'ai_debate.exit.success' => 'Successfully exited the AI debate.',
    'ai_debate.exit.error' => 'Failed to exit the AI debate.',

    // ValidateDebateAccess.php
    'auth.login_required' => 'Login is required.',
    'debate.not_found' => 'Debate not found.',
    'debate.access_denied' => 'You do not have access to this debate.',
    'debate.deleted' => 'Debate deleted.',
    'debate.terminated' => 'Debate terminated.',
    'room.not_found' => 'Room not found.',
    'debate.invalid_state' => 'Debate state is invalid.',
];
