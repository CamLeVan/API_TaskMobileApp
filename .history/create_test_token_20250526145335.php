<?php

// Script để tạo test token cho Android team

echo "=== CREATING TEST TOKEN FOR ANDROID ===\n";

// 1. Register test user
echo "\n1. REGISTERING TEST USER:\n";
$registerUrl = 'http://localhost:8000/api/auth/register';
$registerData = json_encode([
    'name' => 'Test Manager',
    'email' => 'testmanager@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $registerUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $registerData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$registerResponse = curl_exec($ch);
$registerHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Register HTTP Code: $registerHttpCode\n";
echo "Register Response: $registerResponse\n";

// 2. Login to get token
echo "\n2. LOGGING IN TO GET TOKEN:\n";
$loginUrl = 'http://localhost:8000/api/auth/login';
$loginData = json_encode([
    'email' => 'testmanager@example.com',
    'password' => 'password123'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Code: $loginHttpCode\n";
echo "Login Response: $loginResponse\n";

// Parse token
$loginData = json_decode($loginResponse, true);
if (isset($loginData['token'])) {
    $token = $loginData['token'];
    echo "\n✅ TOKEN CREATED: $token\n";

    // 3. Test invitation API with real token
    echo "\n3. TESTING INVITATION API WITH REAL TOKEN:\n";
    $inviteUrl = 'http://localhost:8000/api/teams/1/invitations';
    $inviteData = json_encode([
        'email' => 'exp@gmail.com',
        'role' => 'member'
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $inviteUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $inviteData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $inviteResponse = curl_exec($ch);
    $inviteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Invite HTTP Code: $inviteHttpCode\n";
    echo "Invite Response: $inviteResponse\n";

    // 4. Save token for Android team
    echo "\n4. SAVING TOKEN FOR ANDROID TEAM:\n";
    file_put_contents('android_test_token.txt', $token);
    echo "✅ Token saved to android_test_token.txt\n";

    echo "\n=== FOR ANDROID TEAM ===\n";
    echo "📱 Test Account:\n";
    echo "   Email: testmanager@example.com\n";
    echo "   Password: password123\n";
    echo "   Token: $token\n";
    echo "\n📡 API URLs:\n";
    echo "   Base URL: http://10.0.2.2:8000/api/\n";
    echo "   Login: POST http://10.0.2.2:8000/api/auth/login\n";
    echo "   Invitations: POST http://10.0.2.2:8000/api/teams/1/invitations\n";
    echo "\n🔧 Headers:\n";
    echo "   Authorization: Bearer $token\n";
    echo "   Content-Type: application/json\n";

} else {
    echo "\n❌ FAILED TO GET TOKEN\n";
    echo "Login response: $loginResponse\n";
}

echo "\n=== TEST COMPLETED ===\n";
