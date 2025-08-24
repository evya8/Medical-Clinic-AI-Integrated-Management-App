<?php

namespace MedicalClinic\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class SMSService
{
    private array $config;
    private ?Client $client;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->initializeTwilio();
    }

    private function initializeTwilio(): void
    {
        $sid = $this->config['sms']['twilio']['sid'];
        $token = $this->config['sms']['twilio']['token'];

        if (empty($sid) || empty($token)) {
            $this->client = null;
            return;
        }

        try {
            $this->client = new Client($sid, $token);
        } catch (TwilioException $e) {
            error_log("Twilio initialization failed: " . $e->getMessage());
            $this->client = null;
        }
    }

    public function sendAppointmentReminder(array $appointmentData): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'message' => 'SMS service not configured',
                'provider_message_id' => null,
                'cost_cents' => 0
            ];
        }

        $message = $this->generateAppointmentReminderText($appointmentData);
        
        return $this->sendSMS(
            $appointmentData['patient_phone'],
            $message,
            'appointment_reminder'
        );
    }

    public function sendAppointmentConfirmation(array $appointmentData): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'message' => 'SMS service not configured',
                'provider_message_id' => null,
                'cost_cents' => 0
            ];
        }

        $message = $this->generateAppointmentConfirmationText($appointmentData);
        
        return $this->sendSMS(
            $appointmentData['patient_phone'],
            $message,
            'appointment_confirmation'
        );
    }

    public function sendCustomSMS(string $to, string $message): array
    {
        return $this->sendSMS($to, $message, 'custom');
    }

    private function sendSMS(string $to, string $message, string $type = 'general'): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'message' => 'SMS service not configured',
                'provider_message_id' => null,
                'cost_cents' => 0
            ];
        }

        try {
            // Clean phone number
            $to = $this->formatPhoneNumber($to);
            
            if (!$this->isValidPhoneNumber($to)) {
                return [
                    'success' => false,
                    'message' => 'Invalid phone number format',
                    'provider_message_id' => null,
                    'cost_cents' => 0
                ];
            }

            $twilioMessage = $this->client->messages->create(
                $to,
                [
                    'from' => $this->config['sms']['twilio']['from'],
                    'body' => $message
                ]
            );

            // Calculate estimated cost (approximate - Twilio charges vary by region)
            $estimatedCostCents = $this->calculateSMSCost($to, $message);

            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'provider_message_id' => $twilioMessage->sid,
                'cost_cents' => $estimatedCostCents,
                'delivery_status' => $twilioMessage->status
            ];

        } catch (TwilioException $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'SMS sending failed: ' . $e->getMessage(),
                'provider_message_id' => null,
                'cost_cents' => 0
            ];
        }
    }

    private function generateAppointmentReminderText(array $data): string
    {
        $appointmentDate = date('M j, Y', strtotime($data['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($data['start_time']));
        
        return "🏥 APPOINTMENT REMINDER\n\n" .
               "Hi {$data['patient_name']},\n\n" .
               "Your appointment with Dr. {$data['doctor_name']} is scheduled for:\n\n" .
               "📅 {$appointmentDate}\n" .
               "⏰ {$appointmentTime}\n" .
               "🩺 {$data['appointment_type']}\n\n" .
               "Please arrive 15 minutes early.\n\n" .
               "Need to reschedule? Call us at {$data['clinic_phone']}\n\n" .
               "- {$data['clinic_name']}";
    }

    private function generateAppointmentConfirmationText(array $data): string
    {
        $appointmentDate = date('M j, Y', strtotime($data['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($data['start_time']));
        
        return "✅ APPOINTMENT CONFIRMED\n\n" .
               "Hi {$data['patient_name']},\n\n" .
               "Your appointment has been confirmed:\n\n" .
               "👨‍⚕️ Dr. {$data['doctor_name']}\n" .
               "📅 {$appointmentDate}\n" .
               "⏰ {$appointmentTime}\n" .
               "🩺 {$data['appointment_type']}\n\n" .
               "We look forward to seeing you!\n\n" .
               "Questions? Call {$data['clinic_phone']}\n\n" .
               "- {$data['clinic_name']}";
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phone);
        
        // Add country code if missing (assumes US/Canada)
        if (strlen($digits) === 10) {
            $digits = '1' . $digits;
        }
        
        // Format with +
        return '+' . $digits;
    }

    private function isValidPhoneNumber(string $phone): bool
    {
        // Basic validation for international format
        return preg_match('/^\+[1-9]\d{10,14}$/', $phone);
    }

    private function calculateSMSCost(string $to, string $message): int
    {
        // Approximate SMS cost calculation
        // Actual costs vary by destination and Twilio pricing
        
        $segments = ceil(strlen($message) / 160); // SMS segments
        
        // Rough estimates in cents (update with actual Twilio pricing)
        $costPerSegment = 75; // $0.0075 for US/Canada
        
        // International numbers cost more
        if (!str_starts_with($to, '+1')) {
            $costPerSegment = 500; // $0.05 for international
        }
        
        return $segments * $costPerSegment;
    }

    public function getMessageStatus(string $messageSid): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'message' => 'SMS service not configured'
            ];
        }

        try {
            $message = $this->client->messages($messageSid)->fetch();
            
            return [
                'success' => true,
                'status' => $message->status,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage,
                'date_sent' => $message->dateSent ? $message->dateSent->format('Y-m-d H:i:s') : null,
                'price' => $message->price,
                'price_unit' => $message->priceUnit
            ];
            
        } catch (TwilioException $e) {
            return [
                'success' => false,
                'message' => 'Failed to get message status: ' . $e->getMessage()
            ];
        }
    }

    public function testConnection(): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'message' => 'SMS service not configured - please check Twilio credentials'
            ];
        }

        try {
            // Test by fetching account information
            $account = $this->client->api->accounts($this->config['sms']['twilio']['sid'])->fetch();
            
            return [
                'success' => true,
                'message' => 'SMS service connection successful',
                'account_status' => $account->status,
                'account_type' => $account->type
            ];
            
        } catch (TwilioException $e) {
            return [
                'success' => false,
                'message' => 'SMS service connection failed: ' . $e->getMessage()
            ];
        }
    }

    public function validatePhoneNumber(string $phone): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'message' => 'SMS service not configured'
            ];
        }

        try {
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            $phoneNumber = $this->client->lookups->v1->phoneNumbers($formattedPhone)
                                                    ->fetch(['type' => 'carrier']);
            
            return [
                'success' => true,
                'formatted_number' => $phoneNumber->phoneNumber,
                'country_code' => $phoneNumber->countryCode,
                'carrier_name' => $phoneNumber->carrier['name'] ?? null,
                'carrier_type' => $phoneNumber->carrier['type'] ?? null,
                'is_mobile' => ($phoneNumber->carrier['type'] ?? '') === 'mobile'
            ];
            
        } catch (TwilioException $e) {
            return [
                'success' => false,
                'message' => 'Phone number validation failed: ' . $e->getMessage()
            ];
        }
    }
}
