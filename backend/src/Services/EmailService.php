<?php

namespace MedicalClinic\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private array $config;
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->initializeMailer();
    }

    private function initializeMailer(): void
    {
        $this->mailer = new PHPMailer(true);

        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['mail']['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['mail']['username'];
            $this->mailer->Password = $this->config['mail']['password'];
            $this->mailer->SMTPSecure = $this->config['mail']['encryption'];
            $this->mailer->Port = $this->config['mail']['port'];
            
            // Default sender
            $this->mailer->setFrom(
                $this->config['mail']['from']['address'],
                $this->config['mail']['from']['name']
            );

            // Enable verbose debug output (disable in production)
            if ($this->config['debug']) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }

        } catch (Exception $e) {
            throw new \Exception("Email configuration failed: " . $e->getMessage());
        }
    }

    public function sendAppointmentReminder(array $appointmentData): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipient
            $this->mailer->addAddress($appointmentData['patient_email'], 
                                    $appointmentData['patient_name']);

            // Email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Appointment Reminder - ' . $appointmentData['clinic_name'];
            
            $htmlBody = $this->generateAppointmentReminderHTML($appointmentData);
            $textBody = $this->generateAppointmentReminderText($appointmentData);
            
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody;

            $result = $this->mailer->send();
            
            return $result;

        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendAppointmentConfirmation(array $appointmentData): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($appointmentData['patient_email'], 
                                    $appointmentData['patient_name']);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Appointment Confirmation - ' . $appointmentData['clinic_name'];
            
            $htmlBody = $this->generateAppointmentConfirmationHTML($appointmentData);
            $textBody = $this->generateAppointmentConfirmationText($appointmentData);
            
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody;

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendCustomEmail(string $to, string $name, string $subject, string $message): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($to, $name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            $this->mailer->AltBody = strip_tags($message);

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private function generateAppointmentReminderHTML(array $data): string
    {
        $appointmentDate = date('l, F j, Y', strtotime($data['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($data['start_time']));
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #2c5aa0; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .appointment-details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { background-color: #2c5aa0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$data['clinic_name']}</h1>
                <h2>Appointment Reminder</h2>
            </div>
            
            <div class='content'>
                <p>Dear {$data['patient_name']},</p>
                
                <p>This is a friendly reminder about your upcoming appointment:</p>
                
                <div class='appointment-details'>
                    <h3>Appointment Details</h3>
                    <p><strong>Doctor:</strong> {$data['doctor_name']}</p>
                    <p><strong>Specialty:</strong> {$data['doctor_specialty']}</p>
                    <p><strong>Date:</strong> {$appointmentDate}</p>
                    <p><strong>Time:</strong> {$appointmentTime}</p>
                    <p><strong>Type:</strong> {$data['appointment_type']}</p>
                </div>
                
                <p><strong>Please arrive 15 minutes early</strong> to complete any necessary paperwork.</p>
                
                <p>If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance.</p>
                
                <p>Contact Information:<br>
                Phone: {$data['clinic_phone']}<br>
                Email: {$data['clinic_email']}</p>
                
                <p>We look forward to seeing you!</p>
                
                <p>Best regards,<br>
                {$data['clinic_name']} Team</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated reminder. Please do not reply to this email.</p>
                <p>{$data['clinic_address']}</p>
            </div>
        </body>
        </html>";
    }

    private function generateAppointmentReminderText(array $data): string
    {
        $appointmentDate = date('l, F j, Y', strtotime($data['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($data['start_time']));
        
        return "
APPOINTMENT REMINDER - {$data['clinic_name']}

Dear {$data['patient_name']},

This is a friendly reminder about your upcoming appointment:

APPOINTMENT DETAILS:
Doctor: {$data['doctor_name']}
Specialty: {$data['doctor_specialty']}
Date: {$appointmentDate}
Time: {$appointmentTime}
Type: {$data['appointment_type']}

Please arrive 15 minutes early to complete any necessary paperwork.

If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance.

Contact Information:
Phone: {$data['clinic_phone']}
Email: {$data['clinic_email']}

We look forward to seeing you!

Best regards,
{$data['clinic_name']} Team

---
This is an automated reminder. Please do not reply to this email.
{$data['clinic_address']}
        ";
    }

    private function generateAppointmentConfirmationHTML(array $data): string
    {
        // Similar to reminder but with confirmation messaging
        return str_replace(
            ['Appointment Reminder', 'This is a friendly reminder'],
            ['Appointment Confirmation', 'Your appointment has been confirmed'],
            $this->generateAppointmentReminderHTML($data)
        );
    }

    private function generateAppointmentConfirmationText(array $data): string
    {
        return str_replace(
            ['APPOINTMENT REMINDER', 'This is a friendly reminder'],
            ['APPOINTMENT CONFIRMATION', 'Your appointment has been confirmed'],
            $this->generateAppointmentReminderText($data)
        );
    }

    public function testConnection(): array
    {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            
            return [
                'success' => true,
                'message' => 'Email service connection successful'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Email service connection failed: ' . $e->getMessage()
            ];
        }
    }
}
