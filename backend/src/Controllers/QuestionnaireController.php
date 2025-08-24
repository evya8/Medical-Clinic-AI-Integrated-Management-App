<?php

namespace MedicalClinic\Controllers;

class QuestionnaireController extends BaseController
{
    public function getQuestionnaires(): void
    {
        $this->requireAuth();

        $questionnaires = $this->db->fetchAll(
            "SELECT id, title, description, is_active, created_at 
             FROM questionnaires 
             ORDER BY created_at DESC"
        );

        $this->success($questionnaires, 'Questionnaires retrieved successfully');
    }

    public function getQuestionnaire(int $id): void
    {
        $this->requireAuth();

        $questionnaire = $this->db->fetch(
            "SELECT * FROM questionnaires WHERE id = :id",
            ['id' => $id]
        );

        if (!$questionnaire) {
            $this->error('Questionnaire not found', 404);
        }

        // Decode JSON questions
        $questionnaire['questions'] = json_decode($questionnaire['questions'], true);

        $this->success($questionnaire, 'Questionnaire retrieved successfully');
    }

    public function createQuestionnaire(): void
    {
        $this->requireRole(['admin', 'doctor']);
        
        // Implementation for creating questionnaires with branching logic
        $this->success(null, 'Questionnaire creation endpoint - to be implemented');
    }

    public function updateQuestionnaire(int $id): void
    {
        $this->requireRole(['admin', 'doctor']);
        
        // Implementation for updating questionnaires
        $this->success(null, 'Questionnaire update endpoint - to be implemented');
    }

    public function submitResponse(int $id): void
    {
        $this->requireAuth();
        
        // Implementation for submitting questionnaire responses
        $this->success(null, 'Response submission endpoint - to be implemented');
    }

    public function getQuestionnaireResponses(int $id): void
    {
        $this->requireRole(['admin', 'doctor', 'nurse']);
        
        // Implementation for getting questionnaire responses
        $this->success(null, 'Response retrieval endpoint - to be implemented');
    }
}
