<?php

namespace MedicalClinic\Controllers;

class PatientController extends BaseController
{
    public function getPatients(): void
    {
        $this->requireAuth();

        $search = $_GET['search'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;

        $sql = "SELECT * FROM patients WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY last_name, first_name LIMIT :limit OFFSET :offset";
        $params['limit'] = (int) $limit;
        $params['offset'] = (int) $offset;

        $patients = $this->db->fetchAll($sql, $params);

        $this->success($patients, 'Patients retrieved successfully');
    }

    public function getPatient(int $id): void
    {
        $this->requireAuth();

        $patient = $this->db->fetch("SELECT * FROM patients WHERE id = :id", ['id' => $id]);

        if (!$patient) {
            $this->error('Patient not found', 404);
        }

        $this->success($patient, 'Patient retrieved successfully');
    }

    public function createPatient(): void
    {
        $this->requireRole(['admin', 'doctor', 'nurse', 'receptionist']);

        $this->validateRequired([
            'first_name', 
            'last_name', 
            'email', 
            'phone', 
            'date_of_birth'
        ], $this->input);

        if (!$this->validateEmail($this->input['email'])) {
            $this->error('Invalid email format', 400);
        }

        $patientData = [
            'first_name' => $this->sanitizeString($this->input['first_name']),
            'last_name' => $this->sanitizeString($this->input['last_name']),
            'email' => strtolower(trim($this->input['email'])),
            'phone' => $this->sanitizeString($this->input['phone']),
            'date_of_birth' => $this->input['date_of_birth'],
            'gender' => $this->input['gender'] ?? null,
            'address' => isset($this->input['address']) ? $this->sanitizeString($this->input['address']) : null,
            'emergency_contact_name' => isset($this->input['emergency_contact_name']) ? $this->sanitizeString($this->input['emergency_contact_name']) : null,
            'emergency_contact_phone' => isset($this->input['emergency_contact_phone']) ? $this->sanitizeString($this->input['emergency_contact_phone']) : null,
            'medical_notes' => isset($this->input['medical_notes']) ? $this->sanitizeString($this->input['medical_notes']) : null,
            'allergies' => isset($this->input['allergies']) ? $this->sanitizeString($this->input['allergies']) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $patientId = $this->db->insert('patients', $patientData);

        $this->success(['patient_id' => $patientId], 'Patient created successfully');
    }

    public function updatePatient(int $id): void
    {
        $this->requireRole(['admin', 'doctor', 'nurse', 'receptionist']);

        $patient = $this->db->fetch("SELECT * FROM patients WHERE id = :id", ['id' => $id]);
        
        if (!$patient) {
            $this->error('Patient not found', 404);
        }

        $updateData = [];
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 
                         'gender', 'address', 'emergency_contact_name', 'emergency_contact_phone',
                         'medical_notes', 'allergies'];

        foreach ($allowedFields as $field) {
            if (isset($this->input[$field])) {
                $updateData[$field] = $this->sanitizeString($this->input[$field]);
            }
        }

        if (empty($updateData)) {
            $this->error('No valid fields to update', 400);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->update('patients', $updateData, 'id = :id', ['id' => $id]);

        $this->success(null, 'Patient updated successfully');
    }

    public function deletePatient(int $id): void
    {
        $this->requireRole(['admin']);

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

        $this->db->delete('patients', 'id = :id', ['id' => $id]);

        $this->success(null, 'Patient deleted successfully');
    }
}
