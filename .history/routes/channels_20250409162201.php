<?php

use App\Models\Team;
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

// Authorize users for the private team chat channel.
// The wildcard {teamId} must match the parameter in the PrivateChannel definition.
Broadcast::channel('teams.{teamId}', function ($user, $teamId) {
    // Attempt to find the team by its ID.
    $team = Team::find($teamId);

    // Check if the team exists and if the authenticated user is a member of that team.
    if ($team && $team->members()->where('user_id', $user->id)->exists()) {
        // If the user is a member, return their ID and name (or simply true)
        // to authorize them to listen on this channel.
        return ['id' => $user->id, 'name' => $user->name];
    }

    // If the team doesn't exist or the user is not a member, deny access.
    return false;
});

// You can define other channels here...
// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;    
// }); 