<?php

namespace MedicalClinic\Controllers;

class DoctorController extends BaseController
{
    public function getDoctors(): void
    {
        $this->requireAuth();

        $doctors = $this->db->fetchAll(
            "SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             WHERE u.is_active = 1
             ORDER BY u.last_name, u.first_name"
        );

        $this->success($doctors, 'Doctors retrieved successfully');
    }

    public function getDoctor(int $id): void
    {
        $this->requireAuth();

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

        $this->success($doctor, 'Doctor retrieved successfully');
    }

    public function createDoctor(): void
    {
        $this->requireRole(['admin']);
        
        // Implementation for creating doctors
        $this->success(null, 'Doctor creation endpoint - to be implemented');
    }

    public function updateDoctor(int $id): void
    {
        $this->requireRole(['admin']);
        
        // Implementation for updating doctors
        $this->success(null, 'Doctor update endpoint - to be implemented');
    }
}
