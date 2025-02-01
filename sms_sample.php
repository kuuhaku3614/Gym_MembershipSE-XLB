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
    $account_sid = 'AC80cae86174ab25c1728133facec97816';
    $auth_token = '6ea56e4f9eb311a8c85158e835f5ba38';
    $verify_sid = 'VA65eaa4607fec1266ff04693d0dab7f4f';
    
    // Format the test number
    $test_number = '09944758991';
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