public function transform($user, $token)
{
    return [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'google_id' => $user->google_id,
            'avatar' => $user->avatar,
            'email_verified_at' => $user->email_verified_at
        ],
        'token' => $token,
        'token_type' => 'Bearer'
    ];
}