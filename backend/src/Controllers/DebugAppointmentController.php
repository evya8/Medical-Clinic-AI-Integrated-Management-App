<?php

namespace MedicalClinic\Controllers;

class DebugAppointmentController extends BaseController
{
    public function createSimpleAppointment(): void
    {
        $user = $this->requireRole(['admin', 'doctor', 'nurse', 'receptionist']);

        // Very simple data first
        $appointmentData = [
            'patient_id' => 3,
            'doctor_id' => 1,
            'appointment_date' => '2025-08-26',
            'start_time' => '14:00',
            'end_time' => '14:30',
            'appointment_type' => 'General Consultation',
            'status' => 'scheduled',
            'created_by' => $user['id']
        ];

        try {
            // Debug: let's see the SQL that would be generated
            $fields = implode(',', array_keys($appointmentData));
            $placeholders = ':' . implode(', :', array_keys($appointmentData));
            $sql = "INSERT INTO appointments ({$fields}) VALUES ({$placeholders})";
            
            $this->success([
                'debug_info' => [
                    'sql' => $sql,
                    'data' => $appointmentData,
                    'fields' => $fields,
                    'placeholders' => $placeholders
                ]
            ], 'Debug info generated');

        } catch (\Exception $e) {
            $this->error('Debug failed: ' . $e->getMessage(), 500);
        }
    }

    public function testInsert(): void
    {
        $user = $this->requireRole(['admin']);

        $appointmentData = [
            'patient_id' => 3,
            'doctor_id' => 1,
            'appointment_date' => '2025-08-26',
            'start_time' => '14:00',
            'end_time' => '14:30',
            'appointment_type' => 'General Consultation',
            'status' => 'scheduled',
            'created_by' => $user['id']
        ];

        try {
            $appointmentId = $this->db->insert('appointments', $appointmentData);

            $this->success([
                'appointment_id' => $appointmentId,
                'data_used' => $appointmentData
            ], 'Test appointment created successfully');

        } catch (\Exception $e) {
            $this->error('Test insert failed: ' . $e->getMessage(), 500);
        }
    }
}
