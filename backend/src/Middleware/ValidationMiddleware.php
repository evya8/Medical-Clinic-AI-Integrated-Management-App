<?php

namespace MedicalClinic\Middleware;

class ValidationMiddleware extends BaseMiddleware
{
    private array $rules;

    public function __construct(array $rules)
    {
        parent::__construct();
        $this->rules = $rules;
    }

    public function handle(array $request, callable $next): mixed
    {
        $input = $this->getInput();
        $errors = [];

        foreach ($this->rules as $field => $rule) {
            $error = $this->validateField($field, $input[$field] ?? null, $rule);
            if ($error) {
                $errors[$field] = $error;
            }
        }

        if (!empty($errors)) {
            $this->log('Validation failed', [
                'errors' => $errors,
                'input_fields' => array_keys($input),
                'validation_rules' => $this->rules
            ]);

            $this->errorResponse('Validation failed', 422, $errors);
        }

        // Add validated input to request context
        $request['validated_input'] = $input;
        $request['validation_passed'] = true;

        $this->log('Validation passed', [
            'validated_fields' => array_keys($input),
            'applied_rules' => array_keys($this->rules)
        ]);

        return $next($request);
    }

    /**
     * Validate a single field against a rule string
     */
    private function validateField(string $field, mixed $value, string $rule): ?string
    {
        $rules = explode('|', $rule);

        foreach ($rules as $singleRule) {
            $ruleParts = explode(':', $singleRule);
            $ruleName = trim($ruleParts[0]);
            $ruleParam = isset($ruleParts[1]) ? trim($ruleParts[1]) : null;

            $error = $this->applyValidationRule($field, $value, $ruleName, $ruleParam);
            if ($error) {
                return $error;
            }
        }

        return null;
    }

    /**
     * Apply a specific validation rule
     */
    private function applyValidationRule(string $field, mixed $value, string $ruleName, ?string $ruleParam): ?string
    {
        switch ($ruleName) {
            case 'required':
                if (is_null($value) || (is_string($value) && trim($value) === '')) {
                    return "The {$field} field is required";
                }
                break;

            case 'email':
                if ($value && !$this->validateEmail($value)) {
                    return "The {$field} must be a valid email address";
                }
                break;

            case 'min':
                if ($value && strlen((string)$value) < (int)$ruleParam) {
                    return "The {$field} must be at least {$ruleParam} characters";
                }
                break;

            case 'max':
                if ($value && strlen((string)$value) > (int)$ruleParam) {
                    return "The {$field} must not exceed {$ruleParam} characters";
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return "The {$field} must be a number";
                }
                break;

            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return "The {$field} must be an integer";
                }
                break;

            case 'date':
                if ($value && !$this->isValidDate($value)) {
                    return "The {$field} must be a valid date (YYYY-MM-DD)";
                }
                break;

            case 'datetime':
                if ($value && !$this->isValidDateTime($value)) {
                    return "The {$field} must be a valid datetime";
                }
                break;

            case 'time':
                if ($value && !$this->isValidTime($value)) {
                    return "The {$field} must be a valid time (HH:MM:SS)";
                }
                break;

            case 'in':
                if ($value) {
                    $allowed = array_map('trim', explode(',', $ruleParam));
                    if (!in_array($value, $allowed)) {
                        return "The {$field} must be one of: " . implode(', ', $allowed);
                    }
                }
                break;

            case 'unique':
                if ($value && $this->isDuplicateValue($field, $value, $ruleParam)) {
                    return "The {$field} has already been taken";
                }
                break;

            case 'exists':
                if ($value && !$this->valueExists($ruleParam, $value)) {
                    return "The selected {$field} is invalid";
                }
                break;

            case 'phone':
                if ($value && !$this->isValidPhone($value)) {
                    return "The {$field} must be a valid phone number";
                }
                break;

            case 'password':
                if ($value && !$this->isValidPassword($value)) {
                    return "The {$field} must be at least 8 characters and contain letters and numbers";
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $input = $this->getInput();
                if ($value && $value !== ($input[$confirmField] ?? null)) {
                    return "The {$field} confirmation does not match";
                }
                break;
        }

        return null;
    }

    /**
     * Validation helper methods
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function isValidDateTime(string $datetime): bool
    {
        return strtotime($datetime) !== false;
    }

    private function isValidTime(string $time): bool
    {
        $t = \DateTime::createFromFormat('H:i:s', $time);
        return $t && $t->format('H:i:s') === $time;
    }

    private function isValidPhone(string $phone): bool
    {
        // Basic phone validation - adjust regex as needed
        return preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[^\d\+]/', '', $phone));
    }

    private function isValidPassword(string $password): bool
    {
        // At least 8 characters, contains letters and numbers
        return strlen($password) >= 8 && 
               preg_match('/[a-zA-Z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }

    private function isDuplicateValue(string $field, mixed $value, string $table): bool
    {
        try {
            $result = $this->db->fetch(
                "SELECT id FROM {$table} WHERE {$field} = :value LIMIT 1",
                ['value' => $value]
            );
            return $result !== null;
        } catch (\Exception $e) {
            return false; // If table doesn't exist or query fails, don't block
        }
    }

    private function valueExists(string $tableAndField, mixed $value): bool
    {
        try {
            $parts = explode('.', $tableAndField);
            $table = $parts[0];
            $field = $parts[1] ?? 'id';
            
            $result = $this->db->fetch(
                "SELECT {$field} FROM {$table} WHERE {$field} = :value LIMIT 1",
                ['value' => $value]
            );
            return $result !== null;
        } catch (\Exception $e) {
            return true; // If table doesn't exist or query fails, allow through
        }
    }

    /**
     * Static factory method for easy usage
     */
    public static function rules(array $rules): self
    {
        return new self($rules);
    }

    /**
     * Common validation rule sets
     */
    public static function userValidation(): self
    {
        return new self([
            'username' => 'required|min:3|max:50|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|password|confirmed',
            'first_name' => 'required|min:2|max:50',
            'last_name' => 'required|min:2|max:50',
            'role' => 'required|in:admin,doctor',
            'phone' => 'phone'
        ]);
    }

    public static function patientValidation(): self
    {
        return new self([
            'first_name' => 'required|min:2|max:50',
            'last_name' => 'required|min:2|max:50',
            'email' => 'email',
            'phone' => 'phone',
            'date_of_birth' => 'date',
            'gender' => 'in:male,female,other'
        ]);
    }

    public static function appointmentValidation(): self
    {
        return new self([
            'patient_id' => 'required|integer|exists:patients.id',
            'doctor_id' => 'required|integer|exists:doctors.id',
            'appointment_date' => 'required|date',
            'start_time' => 'required|time',
            'end_time' => 'required|time',
            'appointment_type' => 'required|max:100',
            'priority' => 'in:low,normal,high,urgent',
            'status' => 'in:scheduled,confirmed,completed,cancelled,no_show'
        ]);
    }

    public static function loginValidation(): self
    {
        return new self([
            'email' => 'required|email',
            'password' => 'required'
        ]);
    }
}
