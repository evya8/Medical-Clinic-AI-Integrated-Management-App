<?php

namespace MedicalClinic\Controllers;

class BillController extends BaseController
{
    public function getBills(): void
    {
        $this->requireAuth();

        $bills = $this->db->fetchAll(
            "SELECT b.*, p.first_name, p.last_name, a.appointment_date
             FROM bills b
             JOIN patients p ON b.patient_id = p.id
             LEFT JOIN appointments a ON b.appointment_id = a.id
             ORDER BY b.created_at DESC"
        );

        $this->success($bills, 'Bills retrieved successfully');
    }

    public function getBill(int $id): void
    {
        $this->requireAuth();

        $bill = $this->db->fetch(
            "SELECT b.*, p.first_name, p.last_name, a.appointment_date
             FROM bills b
             JOIN patients p ON b.patient_id = p.id
             LEFT JOIN appointments a ON b.appointment_id = a.id
             WHERE b.id = :id",
            ['id' => $id]
        );

        if (!$bill) {
            $this->error('Bill not found', 404);
        }

        // Decode JSON services
        $bill['services'] = json_decode($bill['services'], true);

        $this->success($bill, 'Bill retrieved successfully');
    }

    public function createBill(): void
    {
        $this->requireRole(['admin', 'receptionist']);
        
        // Implementation for creating bills
        $this->success(null, 'Bill creation endpoint - to be implemented');
    }

    public function updateBill(int $id): void
    {
        $this->requireRole(['admin', 'receptionist']);
        
        // Implementation for updating bills
        $this->success(null, 'Bill update endpoint - to be implemented');
    }
}
