<?php

use App\Models\DailyNote;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('note.{noteId}', function ($user, $noteId) {
    $note = DailyNote::query()->find($noteId);

    if (!$note) {
        return false;
    }

    return (int) $note->location_id === (int) $user->location_id
        && (int) $note->department_id === (int) $user->department_id
        && (int) $note->branch_id === (int) $user->branch_id;
});
