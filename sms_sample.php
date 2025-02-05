<?php
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

// Format the phone number to E.164 format for Philippines
function formatPhilippinesNumber($number) {
    // Remove any non-digit characters
    $number = preg_replace('/[^0-9]/', '', $number);
    
    // Remove leading 0 if present
    $number = ltrim($number, '0');
    
    // Add Philippines country code
    return '+63' . $number;
}

try {
    // Your Twilio Account SID and Auth Token
    $account_sid = 'ACc1f1f89f87b2b2e23e7c037aad8abae0';
    $auth_token = 'e8b25acb4e646642c43f8d751c75aaf8';
    $verify_sid = 'VA48723f597c526f0dcf1203976de0780f';
    
    // Format the test number
    $test_number = '09562307646';
    $formatted_number = formatPhilippinesNumber($test_number);
    
    echo "Attempting to send verification to: " . $formatted_number . "\n";
    
    // Initialize the Twilio client
    $client = new Client($account_sid, $auth_token);
    
    // Create the verification
    $verification = $client->verify->v2->services($verify_sid)
        ->verifications
        ->create($formatted_number, "sms");
    
    echo "Success! Verification SID: " . $verification->sid . "\n";
    echo "Status: " . $verification->status . "\n";
    
    // Now you can enter the code you receive
    echo "\nTo verify the code, run the verify script with the code you received.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>