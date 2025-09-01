<?php

namespace MedicalClinic\Controllers;

class DoctorController extends BaseControllerMiddleware
{
    public function getDoctors(): void
    {
        // Auth handled by middleware
        $input = $this->getInput();
        
        $search = $input['search'] ?? $_GET['search'] ?? null;
        $specialty = $input['specialty'] ?? $_GET['specialty'] ?? null;
        $limit = (int) ($input['limit'] ?? $_GET['limit'] ?? 50);
        $offset = (int) ($input['offset'] ?? $_GET['offset'] ?? 0);

        $sql = "SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                FROM doctors d
                JOIN users u ON d.user_id = u.id
                WHERE u.is_active = 1";
        $params = [];

        if ($search) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR d.specialty LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($specialty) {
            $sql .= " AND d.specialty = :specialty";
            $params['specialty'] = $specialty;
        }

        $sql .= " ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $doctors = $this->db->fetchAll($sql, $params);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.is_active = 1";
        $countParams = [];
        
        if ($search) {
            $countSql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR d.specialty LIKE :search)";
            $countParams['search'] = "%{$search}%";
        }
        if ($specialty) {
            $countSql .= " AND d.specialty = :specialty";
            $countParams['specialty'] = $specialty;
        }
        
        $totalCount = $this->db->fetch($countSql, $countParams)['total'];

        $this->paginated($doctors, $totalCount, ($offset / $limit) + 1, $limit);
    }

    public function getDoctor(int $id): void
    {
        // Auth handled by middleware
        $doctor = $this->db->fetch(
            "SELECT d.*, u.first_name, u.last_name, u.email, u.phone, u.role
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             WHERE d.id = :id",
            ['id' => $id]
        );

        if (!$doctor) {
            $this->error('Doctor not found', 404);
        }

        // Parse JSON fields
        $doctor['working_days'] = json_decode($doctor['working_days'], true);
        $doctor['working_hours'] = json_decode($doctor['working_hours'], true);

        $this->success($doctor, 'Doctor retrieved successfully');
    }

    public function updateDoctor(int $id): void
    {
        // Admin role validation handled by middleware
        $input = $this->getInput();

        $doctor = $this->db->fetch("SELECT * FROM doctors WHERE id = :id", ['id' => $id]);
        if (!$doctor) {
            $this->error('Doctor not found', 404);
        }

        $updateData = [];
        $allowedFields = ['specialty', 'license_number', 'consultation_duration', 'working_days', 'working_hours'];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'working_days' || $field === 'working_hours') {
                    $updateData[$field] = json_encode($input[$field]);
                } elseif ($field === 'consultation_duration') {
                    $updateData[$field] = (int) $input[$field];
                } else {
                    $updateData[$field] = $this->sanitizeString($input[$field]);
                }
            }
        }

        if (empty($updateData)) {
            $this->error('No valid fields to update', 400);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        try {
            $this->db->update('doctors', $updateData, 'id = :id', ['id' => $id]);
            $this->success(null, 'Doctor updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update doctor: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get doctor's schedule for a specific date or date range
     */
    public function getDoctorSchedule(int $id): void
    {
        // Auth handled by middleware
        $input = array_merge($this->getInput(), $_GET);
        
        $date = $input['date'] ?? date('Y-m-d');
        $endDate = $input['end_date'] ?? $date;

        $doctor = $this->db->fetch("SELECT * FROM doctors WHERE id = :id", ['id' => $id]);
        if (!$doctor) {
            $this->error('Doctor not found', 404);
        }

        // Get appointments for the date range
        $appointments = $this->db->fetchAll(
            "SELECT a.*, p.first_name as patient_first_name, p.last_name as patient_last_name
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             WHERE a.doctor_id = :doctor_id
             AND a.appointment_date BETWEEN :start_date AND :end_date
             AND a.status != 'cancelled'
             ORDER BY a.appointment_date, a.start_time",
            [
                'doctor_id' => $id,
                'start_date' => $date,
                'end_date' => $endDate
            ]
        );

        $this->success([
            'doctor_id' => $id,
            'date_range' => [
                'start' => $date,
                'end' => $endDate
            ],
            'working_hours' => json_decode($doctor['working_hours'], true),
            'working_days' => json_decode($doctor['working_days'], true),
            'appointments' => $appointments
        ], 'Doctor schedule retrieved successfully');
    }

    /**
     * Get all available specialties
     */
    public function getSpecialties(): void
    {
        // Auth handled by middleware
        $specialties = $this->db->fetchAll(
            "SELECT DISTINCT specialty 
             FROM doctors 
             WHERE specialty IS NOT NULL AND specialty != ''
             ORDER BY specialty"
        );

        $this->success(array_column($specialties, 'specialty'), 'Specialties retrieved successfully');
    }

    /**
     * Get doctor statistics (appointments, patients, etc.)
     */
    public function getDoctorStats(int $id): void
    {
        // Auth handled by middleware
        $doctor = $this->db->fetch("SELECT * FROM doctors WHERE id = :id", ['id' => $id]);
        if (!$doctor) {
            $this->error('Doctor not found', 404);
        }

        // Get various statistics
        $totalAppointments = $this->db->fetch(
            "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = :doctor_id",
            ['doctor_id' => $id]
        )['count'];

        $todayAppointments = $this->db->fetch(
            "SELECT COUNT(*) as count FROM appointments 
             WHERE doctor_id = :doctor_id AND DATE(appointment_date) = CURDATE()",
            ['doctor_id' => $id]
        )['count'];

        $monthlyAppointments = $this->db->fetch(
            "SELECT COUNT(*) as count FROM appointments 
             WHERE doctor_id = :doctor_id 
             AND MONTH(appointment_date) = MONTH(CURDATE()) 
             AND YEAR(appointment_date) = YEAR(CURDATE())",
            ['doctor_id' => $id]
        )['count'];

        $uniquePatients = $this->db->fetch(
            "SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = :doctor_id",
            ['doctor_id' => $id]
        )['count'];

        $this->success([
            'doctor_id' => $id,
            'total_appointments' => $totalAppointments,
            'today_appointments' => $todayAppointments,
            'monthly_appointments' => $monthlyAppointments,
            'unique_patients' => $uniquePatients
        ], 'Doctor statistics retrieved successfully');
    }
}
