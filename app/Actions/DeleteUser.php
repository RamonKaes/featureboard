<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUser
{
    /**
     * Delete the user and lower the vote counts that the database cascade cannot maintain.
     *
     * @throws ValidationException when this is the last admin, because the interface cannot appoint a new one
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Admins leaving at once wait here in a fixed order, so each sees who really remains.
            $adminIds = User::query()->where('is_admin', true)->orderBy('id')->lockForUpdate()->pluck('id');

            if ($adminIds->all() === [$user->id]) {
                throw ValidationException::withMessages(['password' => 'Das letzte Admin-Konto kann nicht gelöscht werden.']);
            }

            // The lock blocks new votes by this user, so no vote escapes the count below.
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            $votedFor = $user->votes()->pluck('feature_request_id');

            // Sessions and reset tokens have no foreign key, but they hold personal data.
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // The given model stays intact, because logging out saves it again afterwards.
            $locked->delete();

            FeatureRequest::withoutTimestamps(
                fn () => FeatureRequest::query()->whereKey($votedFor)->decrement('votes_count'),
            );
        }, attempts: 3);
    }
}
