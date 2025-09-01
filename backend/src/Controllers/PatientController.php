<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Models\Patient;

class PatientController extends BaseControllerMiddleware
{
    public function getPatients(): void
    {
        // Auth handled by middleware - no manual requireAuth() needed
        $input = $this->getInput();
        
        $search = $input['search'] ?? $_GET['search'] ?? null;
        $limit = (int) ($input['limit'] ?? $_GET['limit'] ?? 50);
        $offset = (int) ($input['offset'] ?? $_GET['offset'] ?? 0);

        $sql = "SELECT * FROM patients WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY last_name, first_name LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $patients = $this->db->fetchAll($sql, $params);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM patients WHERE 1=1";
        $countParams = [];
        if ($search) {
            $countSql .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
            $countParams['search'] = "%{$search}%";
        }
        $totalCount = $this->db->fetch($countSql, $countParams)['total'];

        $this->paginated($patients, $totalCount, ($offset / $limit) + 1, $limit);
    }

    public function getPatient(int $id): void
    {
        // Auth handled by middleware
        $patient = $this->db->fetch("SELECT * FROM patients WHERE id = :id", ['id' => $id]);

        if (!$patient) {
            $this->error('Patient not found', 404);
        }

        $this->success($patient, 'Patient retrieved successfully');
    }

    public function createPatient(): void
    {
        // Role validation handled by middleware (admin, doctor, nurse, receptionist)
        $input = $this->getInput(); // Already validated by middleware

        // Basic validation for required fields
        $this->validateRequired([
            'first_name', 
            'last_name', 
            'email', 
            'phone', 
            'date_of_birth'
        ], $input);

        // Email validation
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email format', 400);
        }

        $patientData = [
            'first_name' => $this->sanitizeString($input['first_name']),
            'last_name' => $this->sanitizeString($input['last_name']),
            'email' => strtolower(trim($input['email'])),
            'phone' => $this->sanitizeString($input['phone']),
            'date_of_birth' => $input['date_of_birth'],
            'gender' => $input['gender'] ?? null,
            'address' => isset($input['address']) ? $this->sanitizeString($input['address']) : null,
            'emergency_contact_name' => isset($input['emergency_contact_name']) ? $this->sanitizeString($input['emergency_contact_name']) : null,
            'emergency_contact_phone' => isset($input['emergency_contact_phone']) ? $this->sanitizeString($input['emergency_contact_phone']) : null,
            'medical_notes' => isset($input['medical_notes']) ? $this->sanitizeString($input['medical_notes']) : null,
            'allergies' => isset($input['allergies']) ? $this->sanitizeString($input['allergies']) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $patientId = $this->db->insert('patients', $patientData);
            $this->success(['patient_id' => $patientId], 'Patient created successfully');
        } catch (\Exception $e) {
            $this->error('Failed to create patient: ' . $e->getMessage(), 500);
        }
    }

    public function updatePatient(int $id): void
    {
        // Role validation handled by middleware (admin, doctor, nurse, receptionist)
        $input = $this->getInput();
        
        $patient = $this->db->fetch("SELECT * FROM patients WHERE id = :id", ['id' => $id]);
        
        if (!$patient) {
            $this->error('Patient not found', 404);
        }

        $updateData = [];
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 
                         'gender', 'address', 'emergency_contact_name', 'emergency_contact_phone',
                         'medical_notes', 'allergies'];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'email' && !filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                    $this->error('Invalid email format', 400);
                }
                $updateData[$field] = $this->sanitizeString($input[$field]);
            }
        }

        if (empty($updateData)) {
            $this->error('No valid fields to update', 400);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        try {
            $this->db->update('patients', $updateData, 'id = :id', ['id' => $id]);
            $this->success(null, 'Patient updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update patient: ' . $e->getMessage(), 500);
        }
    }

    public function deletePatient(int $id): void
    {
        // Admin role validation handled by middleware
        $patient = $this->db->fetch("SELECT * FROM patients WHERE id = :id", ['id' => $id]);
        
        if (!$patient) {
            $this->error('Patient not found', 404);
        }

        // Check if patient has appointments
        $hasAppointments = $this->db->fetch(
            "SELECT COUNT(*) as count FROM appointments WHERE patient_id = :id",
            ['id' => $id]
        );

        if ($hasAppointments['count'] > 0) {
            $this->error('Cannot delete patient with existing appointments', 409);
        }

        try {
            $this->db->delete('patients', 'id = :id', ['id' => $id]);
            $this->success(null, 'Patient deleted successfully');
        } catch (\Exception $e) {
            $this->error('Failed to delete patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient appointments
     */
    public function getPatientAppointments(int $id): void
    {
        // Auth handled by middleware
        $patient = $this->db->fetch("SELECT * FROM patients WHERE id = :id", ['id' => $id]);
        
        if (!$patient) {
            $this->error('Patient not found', 404);
        }

        $appointments = $this->db->fetchAll(
            "SELECT a.*, 
                    u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                    d.specialty
             FROM appointments a
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             WHERE a.patient_id = :patient_id
             ORDER BY a.appointment_date DESC, a.start_time DESC",
            ['patient_id' => $id]
        );

        $this->success([
            'patient' => $patient,
            'appointments' => $appointments
        ], 'Patient appointments retrieved successfully');
    }

    /**
     * Search patients with better filtering
     */
    public function searchPatients(): void
    {
        // Auth handled by middleware
        $input = $this->getInput();
        
        $query = $input['q'] ?? $input['query'] ?? '';
        $limit = (int) ($input['limit'] ?? 20);
        $offset = (int) ($input['offset'] ?? 0);

        if (strlen($query) < 2) {
            $this->error('Search query must be at least 2 characters long', 400);
        }

        $sql = "SELECT id, first_name, last_name, email, phone, date_of_birth 
                FROM patients 
                WHERE CONCAT(first_name, ' ', last_name) LIKE :query 
                   OR email LIKE :query 
                   OR phone LIKE :query
                ORDER BY last_name, first_name 
                LIMIT :limit OFFSET :offset";

        $params = [
            'query' => "%{$query}%",
            'limit' => $limit,
            'offset' => $offset
        ];

        $patients = $this->db->fetchAll($sql, $params);

        $this->success($patients, 'Patients search completed');
    }
}
