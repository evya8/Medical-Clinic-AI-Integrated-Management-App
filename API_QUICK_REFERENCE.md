# 🚀 Quick API Reference - Medical Clinic Management System

## 🔗 **Base URL**: `http://localhost:8000/api`

## 🔐 **Authentication**
All endpoints except `/auth/*` and `/health` require JWT authentication.

**Header**: `Authorization: Bearer {jwt_token}`

---

## 📚 **Quick Reference Table**

| Category | Endpoint | Method | Purpose | Auth |
|----------|----------|--------|---------|------|
| **🔐 AUTH** | | | | |
| | `/auth/login` | POST | User login | ❌ |
| | `/auth/logout` | POST | User logout | ❌ |
| | `/auth/refresh` | POST | Refresh token | ❌ |
| **🧠 AI DASHBOARD** | | | | |
| | `/ai-dashboard/briefing` | GET | Daily AI briefing | ✅ |
| | `/ai-dashboard/status` | GET | Clinic status | ✅ |
| | `/ai-dashboard/tasks` | GET | Priority tasks | ✅ |
| | `/ai-dashboard/metrics` | GET | Performance data | ✅ |
| | `/ai-dashboard/summary` | GET | Complete dashboard | ✅ |
| **🩺 AI TRIAGE** | | | | |
| | `/ai-triage/analyze` | POST | Full triage analysis | ✅ |
| | `/ai-triage/quick-assessment` | POST | Quick triage | ✅ |
| | `/ai-triage/symptom-triage` | POST | Symptom analysis | ✅ |
| | `/ai-triage/stats` | GET | Triage analytics | ✅ |
| **📝 AI SUMMARIES** | | | | |
| | `/ai-summaries/generate` | POST | Generate summary | ✅ |
| | `/ai-summaries/soap` | POST | SOAP note | ✅ |
| | `/ai-summaries/billing` | POST | Billing summary | ✅ |
| | `/ai-summaries/{id}` | GET | Get summary | ✅ |
| **🚨 AI ALERTS** | | | | |
| | `/ai-alerts/generate` | POST | Generate alerts | ✅ |
| | `/ai-alerts/dashboard` | GET | Alert overview | ✅ |
| | `/ai-alerts/active` | GET | Active alerts | ✅ |
| | `/ai-alerts/acknowledge` | POST | Acknowledge alert | ✅ |
| **👥 PATIENTS** | | | | |
| | `/patients` | GET | List patients | ✅ |
| | `/patients` | POST | Create patient | ✅ |
| | `/patients/{id}` | GET | Get patient | ✅ |
| | `/patients/{id}` | PUT | Update patient | ✅ |
| **📅 APPOINTMENTS** | | | | |
| | `/appointments` | GET | List appointments | ✅ |
| | `/appointments` | POST | Create appointment | ✅ |
| | `/appointments/{id}` | GET | Get appointment | ✅ |
| | `/appointments/{id}` | PUT | Update appointment | ✅ |
| | `/appointments/available-slots` | GET | Check availability | ✅ |
| **👨‍⚕️ DOCTORS** | | | | |
| | `/doctors` | GET | List doctors | ✅ |
| | `/doctors` | POST | Create doctor | ✅ |
| | `/doctors/{id}` | GET | Get doctor | ✅ |
| **📋 QUESTIONNAIRES** | | | | |
| | `/questionnaires` | GET | List surveys | ✅ |
| | `/questionnaires` | POST | Create survey | ✅ |
| | `/questionnaires/{id}/responses` | POST | Submit response | ✅ |
| **🔔 REMINDERS** | | | | |
| | `/reminders` | GET | List reminders | ✅ |
| | `/reminders/send` | POST | Send reminder | ✅ |
| | `/reminders/schedule` | POST | Schedule reminders | ✅ |
| **📦 INVENTORY** | | | | |
| | `/inventory` | GET | List items | ✅ |
| | `/inventory` | POST | Add item | ✅ |
| | `/inventory/{id}` | PUT | Update stock | ✅ |
| **💰 BILLING** | | | | |
| | `/bills` | GET | List bills | ✅ |
| | `/bills` | POST | Create bill | ✅ |
| | `/bills/{id}` | PUT | Update payment | ✅ |
| **❤️ SYSTEM** | | | | |
| | `/health` | GET | API health check | ❌ |

---

## 🎯 **Frontend Component Mapping**

### **Dashboard Page** → AI Dashboard Endpoints
- Daily briefing widget → `GET /ai-dashboard/briefing`
- Clinic status → `GET /ai-dashboard/status`  
- Priority tasks → `GET /ai-dashboard/tasks`
- Performance metrics → `GET /ai-dashboard/metrics`

### **Triage Page** → AI Triage Endpoints
- Patient analysis → `POST /ai-triage/analyze`
- Quick assessment → `POST /ai-triage/quick-assessment`
- Symptom checker → `POST /ai-triage/symptom-triage`

### **Appointments Page** → Appointment Endpoints
- Calendar view → `GET /appointments`
- Scheduling → `POST /appointments`
- Availability → `GET /appointments/available-slots`

### **Patients Page** → Patient Endpoints  
- Patient list → `GET /patients`
- Patient details → `GET /patients/{id}`
- Add/edit patient → `POST/PUT /patients`

### **Alerts Page** → AI Alert Endpoints
- Alert dashboard → `GET /ai-alerts/dashboard`
- Active alerts → `GET /ai-alerts/active`
- Alert actions → `POST /ai-alerts/acknowledge`

---

## 📱 **Response Format**

### **Success Response**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* actual data */ },
  "timestamp": "2024-08-24 12:00:00"
}
```

### **Error Response** 
```json
{
  "success": false,
  "message": "Error description",
  "timestamp": "2024-08-24 12:00:00"
}
```

### **AI Feature Response**
```json
{
  "success": true,
  "data": { /* AI analysis results */ },
  "model_used": "llama3-8b-8192",
  "response_time_ms": 1250,
  "tokens_used": 450
}
```

---

## ⚡ **Key Integration Points**

### **Authentication Flow**
1. `POST /auth/login` → Get JWT token
2. Store token in localStorage/sessionStorage  
3. Include in all API headers: `Authorization: Bearer {token}`
4. Handle 401 responses → Redirect to login

### **Real-time Updates**
- Poll `/ai-dashboard/status` every 30 seconds
- Poll `/ai-alerts/active` every 60 seconds  
- Refresh after POST operations

### **AI Processing**
- Show loading state (1-2 second response times)
- Handle fallback responses gracefully
- Display AI confidence levels where available

### **Error Handling**
- 401: Redirect to login
- 403: Show permission error
- 500: Show generic error message
- Network errors: Show offline message

---

## 🎨 **UI Components Needed**

### **Core Components**
- `LoginForm` → `/auth/login`
- `Dashboard` → AI dashboard endpoints
- `PatientList` → `/patients`
- `AppointmentCalendar` → `/appointments`
- `TriageAssistant` → AI triage endpoints
- `AlertCenter` → AI alert endpoints

### **Shared Components**
- `LoadingSpinner` - For AI processing
- `ErrorBoundary` - For error handling
- `NotificationToast` - For success/error messages
- `ConfirmDialog` - For destructive actions
- `SearchInput` - With debounced API calls

---

## 💡 **Development Tips**

### **Performance**
- Cache patient/doctor lists locally
- Debounce search inputs (300ms)
- Lazy load large data sets
- Show skeleton screens during loading

### **User Experience**
- Always show loading states for AI features
- Provide clear success/error feedback
- Use optimistic updates where possible
- Implement keyboard shortcuts for power users

### **Security**
- Never store sensitive data in localStorage
- Implement automatic token refresh
- Clear auth data on logout
- Validate all inputs client-side

---

## 🚀 **Getting Started**

### **1. Set up API client**
```javascript
const API_BASE = 'http://localhost:8000/api';

const apiClient = {
  get: (endpoint) => fetch(`${API_BASE}${endpoint}`, {
    headers: { 'Authorization': `Bearer ${getToken()}` }
  }),
  post: (endpoint, data) => fetch(`${API_BASE}${endpoint}`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${getToken()}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  })
};
```

### **2. Test authentication**
```javascript
// Test login
const loginResponse = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'test@test.com', password: 'password' })
});
```

### **3. Test AI features**
```javascript
// Test AI dashboard
const briefing = await apiClient.get('/ai-dashboard/briefing');
const data = await briefing.json();
console.log('Daily briefing:', data);
```

---

**🏥 Ready to build the future of medical practice management! 🚀**

All backend endpoints are operational and waiting for your frontend magic!
