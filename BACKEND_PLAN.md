# Medical Clinic Management System - Backend Architecture Plan

## **Tech Stack**
- **Backend:** PHP 8+ with MVC architecture
- **Database:** MySQL 8.0+
- **Authentication:** JWT tokens with role-based access
- **Email/SMS:** PHPMailer + Twilio API
- **Security:** Password hashing, input validation, basic encryption

---

## **Database Schema Design**

### **Core Tables:**

**users**
```sql
- id (primary key)
- username, email, password_hash
- role (doctor, nurse, receptionist, admin, pharmacist)
- first_name, last_name, phone
- created_at, updated_at, is_active
```

**doctors**
```sql
- id (primary key)
- user_id (foreign key to users)
- specialty, license_number
- consultation_duration (default 30 mins)
- working_days (JSON: ["monday", "tuesday"...])
- working_hours (JSON: {"start": "09:00", "end": "17:00"})
```

**patients**
```sql
- id (primary key)
- first_name, last_name, email, phone
- date_of_birth, gender, address
- emergency_contact_name, emergency_contact_phone
- medical_notes, allergies
- created_at, updated_at
```

**appointments**
```sql
- id (primary key)
- patient_id, doctor_id (foreign keys)
- appointment_date, start_time, end_time
- status (scheduled, completed, cancelled, no-show)
- appointment_type, notes, priority
- created_by (user_id), created_at, updated_at
```

**questionnaires**
```sql
- id (primary key)
- title, description, is_active
- questions (JSON structure for branching logic)
- created_by, created_at, updated_at
```

**questionnaire_responses**
```sql
- id (primary key)
- questionnaire_id, patient_id
- responses (JSON), completed_at
- submitted_by (user_id)
```

**reminders**
```sql
- id (primary key)
- appointment_id, reminder_type (email/sms)
- scheduled_time, sent_at, status
- message_content
```

**inventory**
```sql
- id (primary key)
- item_name, item_type, quantity
- unit_price, supplier, expiry_date
- minimum_stock_level, last_updated_by
```

**bills**
```sql
- id (primary key)
- patient_id, appointment_id
- amount, status (pending, paid, overdue)
- services (JSON), created_at, paid_at
```

---

## **Backend Folder Structure**

```
/medical-clinic-management/
├── backend/
│   ├── config/
│   │   ├── database.php
│   │   ├── app.php
│   │   └── cors.php
│   ├── src/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── AppointmentController.php
│   │   │   ├── PatientController.php
│   │   │   ├── DoctorController.php
│   │   │   ├── QuestionnaireController.php
│   │   │   ├── ReminderController.php
│   │   │   ├── InventoryController.php
│   │   │   └── BillController.php
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Patient.php
│   │   │   ├── Doctor.php
│   │   │   ├── Appointment.php
│   │   │   ├── Questionnaire.php
│   │   │   └── (other models...)
│   │   ├── Services/
│   │   │   ├── AuthService.php
│   │   │   ├── SmartScheduler.php (AI Calendar logic)
│   │   │   ├── ReminderService.php
│   │   │   ├── EmailService.php
│   │   │   ├── SMSService.php
│   │   │   └── ReportService.php
│   │   ├── Middleware/
│   │   │   ├── AuthMiddleware.php
│   │   │   ├── RoleMiddleware.php
│   │   │   └── ValidationMiddleware.php
│   │   └── Utils/
│   │       ├── Database.php
│   │       ├── Validator.php
│   │       ├── Logger.php
│   │       └── Encryption.php
│   ├── public/
│   │   └── index.php (API entry point)
│   ├── routes/
│   │   └── api.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeds/
│   └── vendor/ (Composer dependencies)
```

---

## **Key API Endpoints**

### **Authentication**
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user info

### **Smart Calendar (AI Scheduling)**
- `GET /api/appointments/available-slots` - Get smart suggestions
- `POST /api/appointments` - Create appointment
- `GET /api/appointments` - List appointments (with filters)
- `PUT /api/appointments/{id}` - Update appointment
- `DELETE /api/appointments/{id}` - Cancel appointment

### **Patients**
- `GET /api/patients` - List patients
- `POST /api/patients` - Create patient
- `GET /api/patients/{id}` - Get patient details
- `PUT /api/patients/{id}` - Update patient

### **Questionnaires**
- `GET /api/questionnaires` - List questionnaires
- `POST /api/questionnaires` - Create questionnaire
- `GET /api/questionnaires/{id}` - Get questionnaire with branching logic
- `POST /api/questionnaires/{id}/responses` - Submit responses

### **Reminders**
- `GET /api/reminders` - List scheduled reminders
- `POST /api/reminders/send` - Manual reminder trigger
- `GET /api/reminders/logs` - View sent reminders

---

## **Core Backend Services**

### **SmartScheduler Service (AI Calendar)**
```php
class SmartScheduler {
    // Find available slots based on doctor specialty
    public function getAvailableSlots($doctorId, $date, $duration)
    
    // Prevent double booking
    public function validateAppointment($doctorId, $startTime, $endTime)
    
    // Auto-suggest optimal times
    public function suggestOptimalSlots($specialty, $priority, $preferredTimes)
    
    // Handle rescheduling conflicts
    public function resolveSchedulingConflict($appointmentId, $newTime)
}
```

### **ReminderService**
```php
class ReminderService {
    // Schedule automatic reminders
    public function scheduleReminders($appointmentId)
    
    // Send email reminders
    public function sendEmailReminder($appointmentId)
    
    // Send SMS reminders  
    public function sendSMSReminder($appointmentId)
    
    // Process reminder queue
    public function processReminderQueue()
}
```

---

## **Security Implementation**

### **Authentication & Authorization**
- JWT tokens with 24-hour expiry
- Role-based access control (RBAC)
- Password hashing with bcrypt
- Rate limiting on API endpoints

### **Data Protection**
- Input validation and sanitization
- SQL injection prevention (prepared statements)
- Audit logging for sensitive operations
- Basic field encryption for medical notes

### **Access Control Matrix**
```
Feature                | Admin | Doctor | Nurse | Receptionist | Pharmacist
--------------------- |-------|---------|--------|-------------|------------
View All Patients     |   ✓   |    ✓   |   ✓   |      ✓      |     ✗
Create Appointments    |   ✓   |    ✓   |   ✓   |      ✓      |     ✗
View Medical Records   |   ✓   |    ✓   |   ✓   |      ✗      |     ✗
Manage Inventory       |   ✓   |    ✗   |   ✗   |      ✗      |     ✓
Generate Reports       |   ✓   |    ✓   |   ✗   |      ✗      |     ✗
```

---

## **Next Steps**
1. Set up basic project structure
2. Configure database connection
3. Implement authentication system
4. Build core models and controllers
5. Develop smart scheduling logic
6. Integrate email/SMS services
7. Create questionnaire system with branching logic

Should we start implementing the basic project structure and database setup?
