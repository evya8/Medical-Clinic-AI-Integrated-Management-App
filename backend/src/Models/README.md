# Medical Clinic Models Directory

This directory contains all the model classes for the Medical Clinic Management System, implementing a complete ORM layer for your 8-table database schema.

## 📁 Available Models

### **Core Models**
- `BaseModel.php` - Abstract base class with ORM functionality
- `User.php` - User authentication and role management
- `Doctor.php` - Doctor profiles with scheduling and availability
- `Patient.php` - Patient information with medical data and relationships
- `Appointment.php` - Enhanced appointments with medical fields and workflow

### **AI & System Models**
- `AppointmentSummary.php` - AI-generated medical summaries
- `AIAlert.php` - Intelligent alert system with priority management
- `Reminder.php` - Automated notification system
- `HIPAAAuditLog.php` - Compliance and audit logging

## 🚀 Quick Start

### Basic Usage Examples

```php
// Find a user
$user = User::find(1);
$adminUsers = User::getAdmins();
$doctorUsers = User::getDoctors();

// Patient operations
$patient = Patient::find(1);
$searchResults = Patient::search('John');
$recentPatients = Patient::getRecentPatients(10);

// Appointment management
$todayAppointments = Appointment::getTodayAppointments();
$appointment = Appointment::find(1);
$appointment->complete(['diagnosis' => 'Common cold']);

// Doctor scheduling
$doctor = Doctor::find(1);
$availableSlots = $doctor->getAvailableSlots('2024-12-01');
$isAvailable = $doctor->isAvailableAt('2024-12-01', '10:00:00', '10:30:00');
```

### Relationships

```php
// Get related data
$appointment = Appointment::find(1);
$patient = $appointment->patient();
$doctor = $appointment->doctor();
$summary = $appointment->summary();

// Reverse relationships
$patient = Patient::find(1);
$appointments = $patient->appointments();
$upcomingAppointments = $patient->getUpcomingAppointments();

$user = User::find(1);
$doctorProfile = $user->doctor(); // If user is a doctor
```

### Creating and Updating

```php
// Create new records
$patient = Patient::create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'date_of_birth' => '1990-01-01'
]);

// Update existing records
$appointment = Appointment::find(1);
$appointment->diagnosis = 'Updated diagnosis';
$appointment->status = 'completed';
$appointment->save();

// Use helper methods
$appointment->complete(['diagnosis' => 'Flu', 'treatment_notes' => 'Rest and fluids']);
$appointment->cancel('Patient requested cancellation');
```

## 🔧 Advanced Features

### Search and Filtering

```php
// Advanced queries
$appointments = Appointment::getByDateRange('2024-01-01', '2024-01-31');
$urgentAppointments = Appointment::getByPriority('urgent');
$completedAppointments = Appointment::getByStatus('completed');

// Complex searches
$activeAlerts = AIAlert::getActiveAlerts();
$criticalAlerts = AIAlert::getCriticalAlerts();
$patientAlerts = AIAlert::getPatientAlerts(1);
```

### Status Management

```php
// Appointment workflow
$appointment = Appointment::find(1);
if ($appointment->canBeCompleted()) {
    $appointment->complete([
        'diagnosis' => 'Hypertension',
        'treatment_notes' => 'Prescribed ACE inhibitor',
        'follow_up_required' => true,
        'follow_up_date' => '2024-02-01'
    ]);
}

// User management
$user = User::find(1);
$user->activate();
$user->updateLastLogin();
```

### Display Methods

```php
// Get formatted display data
$patient = Patient::find(1);
$displayInfo = $patient->getDisplayInfo();
// Returns: ['id' => 1, 'full_name' => 'John Doe', 'age' => 34, ...]

$appointment = Appointment::find(1);
$displayInfo = $appointment->getDisplayInfo();
// Returns: ['patient_name' => 'John Doe', 'doctor_name' => 'Dr. Smith', ...]
```

## 📊 Statistics and Analytics

```php
// Get comprehensive statistics
$reminderStats = Reminder::getReminderStats();
$alertStats = AIAlert::getAlertStats();
$summaryStats = AppointmentSummary::getSummaryStats();
$complianceStats = HIPAAAuditLog::getComplianceStats();

// Patient statistics
$patient = Patient::find(1);
$totalAppointments = $patient->getTotalAppointments();
$completedAppointments = $patient->getCompletedAppointments();
```

## 🔍 Validation

```php
// Built-in validation methods
$patient = Patient::find(1);
$validEmail = $patient->validateEmail();
$validPhone = $patient->validatePhone();
$validDOB = $patient->validateDateOfBirth();

// Model state checking
$appointment = Appointment::find(1);
$canModify = $appointment->canBeModified();
$isPast = $appointment->isPast();
$hasConflicts = $appointment->hasConflicts();
```

## 🛠️ Utility Scripts

### Generate New Models
```bash
php scripts/generate_model.php ModelName table_name
```

### Test All Models
```bash
php scripts/test_models.php
```

## 🔄 Migration from Direct SQL

### Before (Controller with direct SQL):
```php
public function getPatient(int $id): void
{
    $patient = $this->db->fetch(
        "SELECT * FROM patients WHERE id = :id",
        ['id' => $id]
    );
    
    if (!$patient) {
        $this->error('Patient not found', 404);
    }
    
    $this->success($patient);
}
```

### After (Using Models):
```php
public function getPatient(int $id): void
{
    try {
        $patient = Patient::findOrFail($id);
        $this->success($patient->getDisplayInfo());
    } catch (Exception $e) {
        $this->error($e->getMessage(), $e->getCode());
    }
}
```

## 📋 Model Features Summary

| Model | Key Features |
|-------|-------------|
| `User` | Authentication, roles (admin/doctor), password management |
| `Doctor` | Scheduling, availability checking, working hours/days |
| `Patient` | Medical info, age calculation, appointment history |
| `Appointment` | Medical workflow, status management, conflict detection |
| `AppointmentSummary` | AI content parsing, confidence scoring, export formats |
| `AIAlert` | Priority management, acknowledgment, trend analysis |
| `Reminder` | Automated scheduling, delivery tracking, cleanup |
| `HIPAAAuditLog` | Compliance logging, activity tracking, security monitoring |

## 🎯 Benefits

1. **Clean Code**: Replace complex SQL with simple method calls
2. **Relationships**: Easy access to related data (`$appointment->patient()`)
3. **Validation**: Built-in data validation and business rules
4. **Type Safety**: Proper return types and error handling
5. **Maintainability**: Centralized business logic in model methods
6. **Testing**: Comprehensive test coverage included
7. **Performance**: Optimized queries with proper indexing support

## 🚀 Next Steps

1. **Test the models**: Run `php scripts/test_models.php`
2. **Update controllers**: Replace direct SQL queries with model calls
3. **Add custom methods**: Extend models with your specific business logic
4. **Performance tune**: Add caching and optimization as needed

Your models are ready to transform your medical clinic management system! 🏥✨
