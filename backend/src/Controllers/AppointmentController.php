<?php

namespace MedicalClinic\Controllers;

class AppointmentController extends BaseControllerMiddleware
{
    public function getAppointments(): void
    {
        // Auth handled by middleware
        $input = $this->getInput();
        
        // Get query parameters
        $date = $input['date'] ?? $_GET['date'] ?? null;
        $doctor_id = $input['doctor_id'] ?? $_GET['doctor_id'] ?? null;
        $patient_id = $input['patient_id'] ?? $_GET['patient_id'] ?? null;
        $status = $input['status'] ?? $_GET['status'] ?? null;
        $limit = (int) ($input['limit'] ?? $_GET['limit'] ?? 50);
        $offset = (int) ($input['offset'] ?? $_GET['offset'] ?? 0);

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

        $sql .= " ORDER BY a.appointment_date, a.start_time LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $appointments = $this->db->fetchAll($sql, $params);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM appointments a WHERE 1=1";
        $countParams = [];
        
        if ($date) {
            $countSql .= " AND DATE(a.appointment_date) = :date";
            $countParams['date'] = $date;
        }
        if ($doctor_id) {
            $countSql .= " AND a.doctor_id = :doctor_id";
            $countParams['doctor_id'] = $doctor_id;
        }
        if ($patient_id) {
            $countSql .= " AND a.patient_id = :patient_id";
            $countParams['patient_id'] = $patient_id;
        }
        if ($status) {
            $countSql .= " AND a.status = :status";
            $countParams['status'] = $status;
        }
        
        $totalCount = $this->db->fetch($countSql, $countParams)['total'];

        $this->paginated($appointments, $totalCount, ($offset / $limit) + 1, $limit);
    }

    public function getAppointment(int $id): void
    {
        // Auth handled by middleware
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
        // Auth handled by middleware
        $input = array_merge($this->getInput(), $_GET);
        
        $this->validateRequired(['doctor_id', 'date'], $input);
        
        $doctor_id = (int) $input['doctor_id'];
        $date = $input['date'];
        $duration = (int) ($input['duration'] ?? 30); // Default 30 minutes

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error('Invalid date format. Use YYYY-MM-DD', 400);
        }

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
            'duration' => $duration,
            'available_slots' => $availableSlots,
            'working_hours' => $workingHours
        ], 'Available slots retrieved successfully');
    }

    public function createAppointment(): void
    {
        // Role validation handled by middleware (admin, doctor, nurse, receptionist)
        $input = $this->getInput(); // Already validated by middleware
        $user = $this->getUser();

        $this->validateRequired([
            'patient_id', 
            'doctor_id', 
            'appointment_date', 
            'start_time',
            'end_time', 
            'appointment_type'
        ], $input);

        // Validate appointment data
        $this->validateAppointmentData($input);

        $appointmentData = [
            'patient_id' => (int) $input['patient_id'],
            'doctor_id' => (int) $input['doctor_id'],
            'appointment_date' => $input['appointment_date'],
            'start_time' => $input['start_time'],
            'end_time' => $input['end_time'],
            'appointment_type' => $this->sanitizeString($input['appointment_type']),
            'notes' => isset($input['notes']) ? $this->sanitizeString($input['notes']) : null,
            'priority' => isset($input['priority']) ? $input['priority'] : 'normal',
            'status' => 'scheduled',
            'created_by' => $user->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Check for conflicts
        if ($this->hasConflict($appointmentData)) {
            $this->error('Appointment time conflicts with existing appointment', 409);
        }

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
        // Role validation handled by middleware (admin, doctor, nurse, receptionist)
        $input = $this->getInput();
        $user = $this->getUser();

        $appointment = $this->db->fetch("SELECT * FROM appointments WHERE id = :id", ['id' => $id]);
        
        if (!$appointment) {
            $this->error('Appointment not found', 404);
        }

        $updateData = [];
        $allowedFields = ['patient_id', 'doctor_id', 'appointment_date', 'start_time', 'end_time', 
                         'appointment_type', 'notes', 'priority', 'status'];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (empty($updateData)) {
            $this->error('No valid fields to update', 400);
        }

        // If time/date fields are being updated, validate them
        if (isset($updateData['appointment_date']) || isset($updateData['start_time']) || isset($updateData['end_time'])) {
            $checkData = array_merge($appointment, $updateData);
            $this->validateAppointmentData($checkData);
            
            // Check for conflicts (excluding current appointment)
            if ($this->hasConflict($checkData, $id)) {
                $this->error('Updated appointment time conflicts with existing appointment', 409);
            }
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        try {
            $this->db->update('appointments', $updateData, 'id = :id', ['id' => $id]);
            $this->success(null, 'Appointment updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update appointment: ' . $e->getMessage(), 500);
        }
    }

    public function deleteAppointment(int $id): void
    {
        // Role validation handled by middleware (admin, doctor, receptionist)
        $appointment = $this->db->fetch("SELECT * FROM appointments WHERE id = :id", ['id' => $id]);
        
        if (!$appointment) {
            $this->error('Appointment not found', 404);
        }

        try {
            // Instead of deleting, mark as cancelled
            $this->db->update(
                'appointments', 
                ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], 
                'id = :id', 
                ['id' => $id]
            );

            $this->success(null, 'Appointment cancelled successfully');
        } catch (\Exception $e) {
            $this->error('Failed to cancel appointment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get appointments for current user (doctors see their appointments, patients see theirs)
     */
    public function getMyAppointments(): void
    {
        // Auth handled by middleware
        $user = $this->getUser();
        $input = $this->getInput();
        
        $date = $input['date'] ?? $_GET['date'] ?? null;
        $status = $input['status'] ?? $_GET['status'] ?? null;
        $limit = (int) ($input['limit'] ?? $_GET['limit'] ?? 50);
        $offset = (int) ($input['offset'] ?? $_GET['offset'] ?? 0);

        if ($user->role === 'doctor') {
            // Get doctor's ID
            $doctor = $this->db->fetch("SELECT id FROM doctors WHERE user_id = :user_id", ['user_id' => $user->id]);
            if (!$doctor) {
                $this->error('Doctor profile not found', 404);
            }

            $sql = "SELECT a.*, 
                           p.first_name as patient_first_name, p.last_name as patient_last_name,
                           p.phone as patient_phone
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    WHERE a.doctor_id = :doctor_id";
            $params = ['doctor_id' => $doctor['id']];
        } else {
            $this->error('Only doctors can access appointments through this endpoint', 403);
        }

        if ($date) {
            $sql .= " AND DATE(a.appointment_date) = :date";
            $params['date'] = $date;
        }

        if ($status) {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY a.appointment_date, a.start_time LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $appointments = $this->db->fetchAll($sql, $params);

        $this->success($appointments, 'My appointments retrieved successfully');
    }

    private function validateAppointmentData(array $data): void
    {
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['appointment_date'])) {
            $this->error('Invalid appointment date format. Use YYYY-MM-DD', 400);
        }

        // Validate time format
        if (!preg_match('/^\d{2}:\d{2}$/', $data['start_time']) || !preg_match('/^\d{2}:\d{2}$/', $data['end_time'])) {
            $this->error('Invalid time format. Use HH:MM', 400);
        }

        // Validate that end time is after start time
        if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
            $this->error('End time must be after start time', 400);
        }

        // Validate appointment is in the future (unless updating existing)
        $appointmentDateTime = strtotime($data['appointment_date'] . ' ' . $data['start_time']);
        if ($appointmentDateTime < time()) {
            $this->error('Cannot create appointments in the past', 400);
        }

        // Validate patient and doctor exist
        $patient = $this->db->fetch("SELECT id FROM patients WHERE id = :id", ['id' => $data['patient_id']]);
        if (!$patient) {
            $this->error('Patient not found', 404);
        }

        $doctor = $this->db->fetch("SELECT id FROM doctors WHERE id = :id", ['id' => $data['doctor_id']]);
        if (!$doctor) {
            $this->error('Doctor not found', 404);
        }
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

            // Don't create slots that go past working hours
            if (strtotime($slotEnd) > $end) {
                break;
            }

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

    private function hasConflict(array $appointmentData, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM appointments 
                WHERE doctor_id = :doctor_id 
                AND DATE(appointment_date) = :appointment_date 
                AND status != 'cancelled'
                AND (
                    (start_time <= :new_start_time AND end_time > :new_start_time)
                    OR (start_time < :new_end_time AND end_time >= :new_end_time)
                    OR (start_time >= :new_start_time AND end_time <= :new_end_time)
                )";
        
        $params = [
            'doctor_id' => $appointmentData['doctor_id'],
            'appointment_date' => $appointmentData['appointment_date'],
            'new_start_time' => $appointmentData['start_time'],
            'new_end_time' => $appointmentData['end_time']
        ];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $conflicts = $this->db->fetchAll($sql, $params);
        return count($conflicts) > 0;
    }

    private function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        return (strtotime($start1) < strtotime($end2)) && (strtotime($end1) > strtotime($start2));
    }
}
