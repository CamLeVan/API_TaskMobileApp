public function handle(Request $request, Closure $next)
{
    try {
        $client = new Google_Client(['client_id' => config('services.google.client_id')]);
        $payload = $client->verifyIdToken($request->id_token);
        
        if ($payload) {
            $request->merge(['google_payload' => $payload]);
            return $next($request);
        }
        
        throw new Exception('Invalid token');
    } catch (Exception $e) {
        return response()->json(['error' => 'Invalid Google token'], 401);
    }
}