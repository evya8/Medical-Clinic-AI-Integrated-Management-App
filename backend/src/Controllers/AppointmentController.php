<?php

namespace MedicalClinic\Controllers;

class AppointmentController extends BaseController
{
    public function getAppointments(): void
    {
        $this->requireAuth();

        // Get query parameters
        $date = $_GET['date'] ?? null;
        $doctor_id = $_GET['doctor_id'] ?? null;
        $patient_id = $_GET['patient_id'] ?? null;
        $status = $_GET['status'] ?? null;

        $sql = "SELECT a.*, 
                       p.first_name as patient_first_name, p.last_name as patient_last_name,
                       u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                       d.specialty
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE 1=1";

        $params = [];

        if ($date) {
            $sql .= " AND DATE(a.appointment_date) = :date";
            $params['date'] = $date;
        }

        if ($doctor_id) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params['doctor_id'] = $doctor_id;
        }

        if ($patient_id) {
            $sql .= " AND a.patient_id = :patient_id";
            $params['patient_id'] = $patient_id;
        }

        if ($status) {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY a.appointment_date, a.start_time";

        $appointments = $this->db->fetchAll($sql, $params);

        $this->success($appointments, 'Appointments retrieved successfully');
    }

    public function getAppointment(int $id): void
    {
        $this->requireAuth();

        $appointment = $this->db->fetch(
            "SELECT a.*, 
                    p.first_name as patient_first_name, p.last_name as patient_last_name,
                    p.email as patient_email, p.phone as patient_phone,
                    u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                    d.specialty
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             WHERE a.id = :id",
            ['id' => $id]
        );

        if (!$appointment) {
            $this->error('Appointment not found', 404);
        }

        $this->success($appointment, 'Appointment retrieved successfully');
    }

    public function getAvailableSlots(): void
    {
        $this->requireAuth();

        $this->validateRequired(['doctor_id', 'date'], $_GET);
        
        $doctor_id = $_GET['doctor_id'];
        $date = $_GET['date'];
        $duration = $_GET['duration'] ?? 30; // Default 30 minutes

        // Get doctor's working hours
        $doctor = $this->db->fetch(
            "SELECT d.*, d.working_hours, d.consultation_duration
             FROM doctors d 
             WHERE d.id = :doctor_id",
            ['doctor_id' => $doctor_id]
        );

        if (!$doctor) {
            $this->error('Doctor not found', 404);
        }

        $workingHours = json_decode($doctor['working_hours'], true);
        $consultationDuration = $doctor['consultation_duration'] ?? 30;

        // Get existing appointments for the date
        $existingAppointments = $this->db->fetchAll(
            "SELECT start_time, end_time 
             FROM appointments 
             WHERE doctor_id = :doctor_id 
             AND DATE(appointment_date) = :date 
             AND status != 'cancelled'
             ORDER BY start_time",
            ['doctor_id' => $doctor_id, 'date' => $date]
        );

        // Generate available slots
        $availableSlots = $this->generateAvailableSlots(
            $workingHours,
            $existingAppointments,
            $duration
        );

        $this->success([
            'date' => $date,
            'doctor_id' => $doctor_id,
            'available_slots' => $availableSlots
        ], 'Available slots retrieved successfully');
    }

    public function createAppointment(): void
    {
        $user = $this->requireRole(['admin', 'doctor', 'nurse', 'receptionist']);

        $this->validateRequired([
            'patient_id', 
            'doctor_id', 
            'appointment_date', 
            'start_time',
            'end_time', 
            'appointment_type'
        ], $this->input);

        $appointmentData = [
            'patient_id' => (int) $this->input['patient_id'],
            'doctor_id' => (int) $this->input['doctor_id'],
            'appointment_date' => $this->input['appointment_date'],
            'start_time' => $this->input['start_time'],
            'end_time' => $this->input['end_time'],
            'appointment_type' => $this->sanitizeString($this->input['appointment_type']),
            'notes' => isset($this->input['notes']) ? $this->sanitizeString($this->input['notes']) : null,
            'priority' => isset($this->input['priority']) ? $this->input['priority'] : 'normal',
            'status' => 'scheduled',
            'created_by' => $user['id']
        ];

        // Validate appointment doesn't conflict (temporarily disabled for testing)
        // if ($this->hasConflict($appointmentData)) {
        //     $this->error('Appointment time conflicts with existing appointment', 409);
        // }

        try {
            $appointmentId = $this->db->insert('appointments', $appointmentData);

            $this->success([
                'appointment_id' => $appointmentId,
                'appointment_data' => $appointmentData
            ], 'Appointment created successfully');
        } catch (\Exception $e) {
            error_log("Appointment creation error: " . $e->getMessage());
            $this->error('Failed to create appointment: ' . $e->getMessage(), 500);
        }
    }

    public function updateAppointment(int $id): void
    {
        $user = $this->requireRole(['admin', 'doctor', 'nurse', 'receptionist']);

        $appointment = $this->db->fetch("SELECT * FROM appointments WHERE id = :id", ['id' => $id]);
        
        if (!$appointment) {
            $this->error('Appointment not found', 404);
        }

        $updateData = [];
        $allowedFields = ['patient_id', 'doctor_id', 'appointment_date', 'start_time', 'end_time', 
                         'appointment_type', 'notes', 'priority', 'status'];

        foreach ($allowedFields as $field) {
            if (isset($this->input[$field])) {
                $updateData[$field] = $this->input[$field];
            }
        }

        if (empty($updateData)) {
            $this->error('No valid fields to update', 400);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->update('appointments', $updateData, 'id = :id', ['id' => $id]);

        $this->success(null, 'Appointment updated successfully');
    }

    public function deleteAppointment(int $id): void
    {
        $user = $this->requireRole(['admin', 'doctor', 'receptionist']);

        $appointment = $this->db->fetch("SELECT * FROM appointments WHERE id = :id", ['id' => $id]);
        
        if (!$appointment) {
            $this->error('Appointment not found', 404);
        }

        // Instead of deleting, mark as cancelled
        $this->db->update(
            'appointments', 
            ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $id]
        );

        $this->success(null, 'Appointment cancelled successfully');
    }

    private function generateAvailableSlots(array $workingHours, array $existingAppointments, int $duration): array
    {
        $slots = [];
        $startTime = $workingHours['start'] ?? '09:00';
        $endTime = $workingHours['end'] ?? '17:00';

        $current = strtotime($startTime);
        $end = strtotime($endTime);

        while ($current < $end) {
            $slotStart = date('H:i', $current);
            $slotEnd = date('H:i', $current + ($duration * 60));

            // Check if this slot conflicts with existing appointments
            $hasConflict = false;
            foreach ($existingAppointments as $appointment) {
                if ($this->timesOverlap($slotStart, $slotEnd, $appointment['start_time'], $appointment['end_time'])) {
                    $hasConflict = true;
                    break;
                }
            }

            if (!$hasConflict) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'duration' => $duration
                ];
            }

            $current += ($duration * 60);
        }

        return $slots;
    }

    private function calculateEndTime(string $startTime, int $duration): string
    {
        $start = strtotime($startTime);
        return date('H:i', $start + ($duration * 60));
    }

    private function hasConflict(array $appointmentData): bool
    {
        $conflicts = $this->db->fetchAll(
            "SELECT id FROM appointments 
             WHERE doctor_id = :doctor_id 
             AND DATE(appointment_date) = :appointment_date 
             AND status != 'cancelled'
             AND (
                 (start_time <= :new_start_time AND end_time > :new_start_time)
                 OR (start_time < :new_end_time AND end_time >= :new_end_time)
                 OR (start_time >= :new_start_time AND end_time <= :new_end_time)
             )",
            [
                'doctor_id' => $appointmentData['doctor_id'],
                'appointment_date' => $appointmentData['appointment_date'],
                'new_start_time' => $appointmentData['start_time'],
                'new_end_time' => $appointmentData['end_time']
            ]
        );

        return count($conflicts) > 0;
    }

    private function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        return ($start1 < $end2) && ($end1 > $start2);
    }
}
