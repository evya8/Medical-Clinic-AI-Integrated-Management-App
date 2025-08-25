<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Services\HIPAAComplianceService;

class HIPAAComplianceController extends BaseController
{
    private HIPAAComplianceService $hipaaService;

    public function __construct()
    {
        parent::__construct();
        $this->hipaaService = new HIPAAComplianceService();
    }

    public function getAuditReport(): void
    {
        $this->requireRole(['admin']);

        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $eventType = $_GET['event_type'] ?? null;

        if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            $this->error('Invalid date format. Use YYYY-MM-DD', 400);
        }

        $report = $this->hipaaService->getAuditReport($startDate, $endDate, $eventType);

        $this->success($report, 'HIPAA audit report retrieved successfully');
    }

    public function getAuditStats(): void
    {
        $this->requireRole(['admin']);

        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            $this->error('Invalid date format. Use YYYY-MM-DD', 400);
        }

        $stats = $this->hipaaService->getAuditStats($startDate, $endDate);

        $this->success($stats, 'HIPAA audit statistics retrieved successfully');
    }

    public function checkCompliance(): void
    {
        $this->requireRole(['admin']);

        $violations = $this->hipaaService->checkComplianceViolations();

        $this->success([
            'violations' => $violations,
            'violation_count' => count($violations),
            'compliance_status' => empty($violations) ? 'compliant' : 'violations_found',
            'check_timestamp' => date('Y-m-d H:i:s')
        ], 'Compliance check completed');
    }

    public function logEvent(): void
    {
        $this->requireRole(['admin', 'nurse', 'receptionist']);

        $this->validateRequired(['event_type', 'details'], $this->input);

        $eventType = $this->sanitizeString($this->input['event_type']);
        $details = $this->input['details'];
        $patientId = isset($this->input['patient_id']) ? (int) $this->input['patient_id'] : null;
        $appointmentId = isset($this->input['appointment_id']) ? (int) $this->input['appointment_id'] : null;

        $this->hipaaService->logHIPAAEvent(
            $eventType,
            $details,
            $this->getCurrentUserId(),
            $patientId,
            $appointmentId
        );

        $this->success(null, 'HIPAA event logged successfully');
    }

    private function validateDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function getCurrentUserId(): ?int
    {
        // This should be implemented based on your authentication system
        // For now, return null - you'd typically get this from JWT token or session
        return $_SESSION['user_id'] ?? null;
    }
}
