# Medical Clinic Management System - Current Backend State Documentation

## **Architecture Overview**

**Tech Stack:**
- PHP 8+ with manual MVC architecture (Controllers only, no Models)
- MySQL 8.0+ with 12 database tables
- JWT Authentication via Firebase JWT library
- AI Integration via Groq AI Service
- Email/SMS via PHPMailer + Twilio
- No middleware layer (manual auth/validation in controllers)

---

## **Current Database Schema (12 Tables)**

### **Core Tables:**

**users** - User authentication and roles
```sql
- id, username, email, password_hash
- role (admin, doctor, nurse, receptionist, pharmacist)  
- first_name, last_name, phone, is_active
- last_login_at, created_at, updated_at
```

**doctors** - Doctor profiles and working schedules
```sql
- id, user_id (FK to users)
- specialty, license_number
- working_days (JSON), working_hours (JSON)
- consultation_duration (default 30 mins)
```

**patients** - Patient information and medical data
```sql
- id, first_name, last_name, email, phone
- date_of_birth, gender, address
- emergency_contact_name, emergency_contact_phone
- medical_notes, allergies, created_at, updated_at
```

**appointments** - Enhanced appointment system
```sql
- id, patient_id (FK), doctor_id (FK), created_by (FK)
- appointment_date, start_time, end_time
- status (scheduled, confirmed, completed, cancelled, no_show)
- appointment_type, priority (low, normal, high, urgent)
- notes, diagnosis, treatment_notes
- follow_up_required, follow_up_date
- UNIQUE constraint on doctor_id + date + time
```

**reminders** - Automated notification system
```sql
- id, appointment_id (FK)
- reminder_type (email/sms), scheduled_time, sent_at
- status, message_content
```

**inventory** - Medical supplies management
```sql
- id, item_name, item_type, quantity
- unit_price, supplier, expiry_date
- minimum_stock_level, last_updated_by
```

### **AI & Compliance Tables:**

**appointment_summaries** - AI-generated appointment summaries
```sql
- id, appointment_id (FK)
- summary_type, ai_generated_summary
- soap_notes, billing_codes, recommendations
- confidence_score, created_at
```

**ai_alerts** - Intelligent alert system
```sql
- id, alert_type, priority_level
- patient_id (FK), appointment_id (FK)
- message, ai_reasoning, status
- acknowledged_by, acknowledged_at
```

**hipaa_audit_log** - Compliance tracking
```sql
- id, user_id (FK), action, resource_type
- resource_id, details, ip_address
- user_agent, created_at
```

---

## **Current Backend Structure**

```
backend/
├── config/
│   ├── database.php      # DB connection config
│   ├── app.php          # JWT and app settings  
│   └── cors.php         # CORS headers
├── src/
│   ├── Controllers/     # 15 controllers (no Models!)
│   │   ├── BaseController.php
│   │   ├── AuthController.php
│   │   ├── AppointmentController.php
│   │   ├── PatientController.php
│   │   ├── DoctorController.php
│   │   ├── ReminderController.php
│   │   ├── InventoryController.php
│   │   ├── AITriageController.php
│   │   ├── AIAppointmentSummaryController.php
│   │   ├── AIDashboardController.php
│   │   ├── AIAlertController.php
│   │   ├── HIPAAComplianceController.php
│   │   └── DebugAppointmentController.php
│   ├── Services/        # Business logic services
│   │   ├── AITriageService.php
│   │   ├── AIAppointmentSummaryService.php
│   │   ├── AIStaffDashboardService.php
│   │   ├── AIAlertService.php
│   │   ├── GroqAIService.php
│   │   ├── HIPAAComplianceService.php
│   │   ├── ReminderService.php
│   │   ├── EnhancedReminderService.php
│   │   ├── EmailService.php
│   │   └── SMSService.php
│   ├── Utils/           # Core utilities
│   │   ├── Database.php
│   │   ├── JWTAuth.php
│   │   └── Migration.php
│   └── Middleware/      # ⚠️ EMPTY DIRECTORY
├── routes/
│   └── api.php          # All API route definitions
├── database/
│   ├── migrations/      # 12 migration files
│   └── seeds/           # Database seeders
├── scripts/             # Maintenance and setup scripts
│   ├── ai_features_demo.php
│   ├── process_reminders.php
│   ├── setup_ai_system.php
│   └── test_* scripts
└── public/
    └── index.php        # API entry point
```

---

## **Complete API Endpoints**

### **Authentication**
```
POST /api/auth/login          # User login with JWT
POST /api/auth/logout         # User logout  
POST /api/auth/refresh        # ⚠️ Refresh token (implementation unclear)
```

### **AI-Powered Features** 
```
GET  /api/ai-dashboard/briefing           # Daily clinic briefing
GET  /api/ai-dashboard/status             # Real-time clinic status
GET  /api/ai-dashboard/tasks              # Priority tasks
GET  /api/ai-dashboard/metrics            # Performance metrics
GET  /api/ai-dashboard/summary            # Dashboard overview
GET  /api/ai-dashboard/test-ai            # Test AI connection
POST /api/ai-dashboard/analyze            # Custom analysis
POST /api/ai-dashboard/refresh            # Refresh dashboard

GET  /api/ai-triage/stats                 # Triage statistics
POST /api/ai-triage/analyze               # Analyze patient case
POST /api/ai-triage/batch-analyze         # Batch patient analysis
POST /api/ai-triage/symptom-triage        # Symptom-based triage
POST /api/ai-triage/referral-recommendations  # Generate referrals
POST /api/ai-triage/quick-assessment      # Quick triage
PUT  /api/ai-triage/update-priority       # Update triage priority

GET  /api/ai-summaries/stats              # Summary statistics
GET  /api/ai-summaries/{id}               # Get appointment summary
POST /api/ai-summaries/generate           # Generate summary
POST /api/ai-summaries/soap               # Generate SOAP notes
POST /api/ai-summaries/billing            # Billing summary
POST /api/ai-summaries/patient            # Patient summary
POST /api/ai-summaries/batch              # Batch generate
POST /api/ai-summaries/export             # Export summary
PUT  /api/ai-summaries/update             # Update summary

GET  /api/ai-alerts/dashboard             # Alert dashboard
GET  /api/ai-alerts/active                # Active alerts
GET  /api/ai-alerts/patient               # Patient-specific alerts
GET  /api/ai-alerts/analytics             # Alert analytics
POST /api/ai-alerts/generate              # Generate intelligent alerts
POST /api/ai-alerts/safety                # Patient safety alerts
POST /api/ai-alerts/operational           # Operational alerts
POST /api/ai-alerts/quality               # Quality alerts
POST /api/ai-alerts/acknowledge           # Acknowledge alert
POST /api/ai-alerts/bulk-acknowledge      # Bulk acknowledge
PUT  /api/ai-alerts/update                # Update alert
```

### **Core Medical System**
```
GET  /api/appointments                    # List appointments (with filters)
GET  /api/appointments/available-slots    # Get available time slots
GET  /api/appointments/{id}               # Get appointment details
POST /api/appointments                    # Create appointment
PUT  /api/appointments/{id}               # Update appointment
DELETE /api/appointments/{id}             # Cancel appointment

GET  /api/patients                        # List patients (with search)
GET  /api/patients/{id}                   # Get patient details
POST /api/patients                        # Create patient
PUT  /api/patients/{id}                   # Update patient
DELETE /api/patients/{id}                 # Delete patient

GET  /api/doctors                         # List doctors
GET  /api/doctors/{id}                    # Get doctor details
POST /api/doctors                         # Create doctor
PUT  /api/doctors/{id}                    # Update doctor
```

### **Notifications & Inventory**
```
GET  /api/reminders                       # List reminders
GET  /api/reminders/stats                 # Reminder statistics
GET  /api/reminders/test-email            # Test email service
GET  /api/reminders/test-sms              # Test SMS service
GET  /api/reminders/validate-phone        # Validate phone number
GET  /api/reminders/message-status        # Check message status
POST /api/reminders/send                  # Send manual reminder
POST /api/reminders/schedule              # Schedule reminders
POST /api/reminders/process               # Process reminder queue
POST /api/reminders/test-email            # Send test email
POST /api/reminders/test-sms              # Send test SMS
DELETE /api/reminders/cancel              # Cancel reminders

GET  /api/inventory                       # List inventory
GET  /api/inventory/{id}                  # Get inventory item
POST /api/inventory                       # Create inventory item
PUT  /api/inventory/{id}                  # Update inventory item
DELETE /api/inventory/{id}                # Delete inventory item
```

### **Debug & Health**
```
GET  /api/health                          # API health check
GET  /api/debug/appointment-sql           # Debug appointment creation
POST /api/debug/test-insert               # Test database insert
```

---

## **Key Data Structures**

### **User Authentication**
```php
// JWT Token Payload
{
    'iss': 'medical-clinic',
    'iat': timestamp,
    'exp': timestamp,
    'user_id': int,
    'username': string,
    'email': string, 
    'role': 'admin|doctor|nurse|receptionist|pharmacist',
    'first_name': string,
    'last_name': string
}
```

### **Appointment Structure**  
```php
{
    'id': int,
    'patient_id': int,
    'doctor_id': int,
    'appointment_date': 'YYYY-MM-DD',
    'start_time': 'HH:MM:SS',
    'end_time': 'HH:MM:SS',
    'status': 'scheduled|confirmed|completed|cancelled|no_show',
    'appointment_type': string,
    'priority': 'low|normal|high|urgent',
    'notes': text,
    'diagnosis': text,
    'treatment_notes': text,
    'follow_up_required': boolean,
    'follow_up_date': 'YYYY-MM-DD'
}
```

### **AI Analysis Response**
```php
{
    'success': boolean,
    'confidence_score': float,
    'analysis_type': string,
    'recommendations': array,
    'priority_level': 'low|medium|high|critical',
    'ai_reasoning': string,
    'suggested_actions': array
}
```

---

## **Scripts and Their Roles**

### **Setup & Testing Scripts**
- `setup_ai_system.php` - Initialize AI services and test connections
- `validate_ai_system.php` - Verify AI system integrity  
- `test_ai_direct.php` - Direct AI service testing
- `test_database.php` - Database connection testing
- `ai_features_demo.php` - Demonstrate AI capabilities

### **Maintenance Scripts**
- `process_reminders.php` - Process scheduled reminders (cron job)
- `test_fixes.php` - Test recent bug fixes
- `verify_fixes.php` - Verify applied fixes

---

## **Critical Missing Components & Issues**

### **⚠️ Empty Models Directory**
**Problem:** Controllers access database directly, no ORM layer
```php
// Current: Direct SQL in controllers
$user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);

// Missing: Proper model classes  
// $user = User::find($id);
```

### **⚠️ No Middleware Layer**  
**Problem:** Manual authentication/authorization in every controller method
```php
// Current: Manual checks in each method
public function getPatients() {
    $this->requireAuth();              // Manual
    $this->requireRole(['doctor']);    // Manual
    // ... controller logic
}

// Missing: Automatic middleware protection
```

### **⚠️ Refresh Token Implementation Unclear**
**Problem:** Route exists but implementation details unknown
- `POST /api/auth/refresh` endpoint defined in routes
- Actual refresh token logic not verified in AuthController

### **⚠️ Removed Functionality**
**Confirmed Removed:**
- Questionnaire system (QuestionnaireController referenced but removed)
- Bills management system (BillController referenced but removed)  
- SmartScheduler service (replaced with AI calendar functionality)

---

## **Security Implementation**

### **Current Security Measures:**
- JWT tokens with configurable expiry
- Password hashing with bcrypt
- Role-based access control (manual in controllers)
- Input validation and sanitization in BaseController
- HIPAA audit logging for compliance
- SQL injection prevention via prepared statements

### **Security Gaps:**
- No automatic route protection (middleware missing)
- No rate limiting implementation  
- Manual auth checks prone to human error
- No centralized validation patterns

---

## **Integration Points**

### **External Services:**
- **Groq AI Service** - Powers all AI features (triage, summaries, alerts)
- **PHPMailer** - Email notifications and reminders
- **Twilio SDK** - SMS notifications
- **Firebase JWT** - Token generation and validation

### **AI Features Integration:**
- AI Triage analyzes patient symptoms and suggests priority levels
- AI Summaries generate SOAP notes and appointment documentation  
- AI Alerts monitor for safety/quality issues and operational inefficiencies
- AI Dashboard provides intelligent analytics and daily briefings

---

## **Deployment & Configuration**

### **Environment Configuration:**
- `.env` file for sensitive data (API keys, DB credentials)
- `config/` directory for application settings
- `composer.json` defines dependencies and scripts

### **Available Composer Scripts:**
```bash
composer start     # Start dev server on localhost:8000
composer migrate   # Run database migrations  
composer seed      # Seed database with test data
composer fresh     # Fresh migration (drop and recreate)
```

### **Database Migration System:**
- 12 migration files in chronological order
- Migration utility in `Utils/Migration.php`
- Supports up/down migrations with rollback capability

This documentation represents the current **actual** state of the backend system as implemented, including all AI enhancements, missing components, and architectural decisions.