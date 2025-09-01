# Refresh Token System Implementation

## Setup Instructions

### 1. Run Database Migration
```bash
cd backend
php scripts/migrate_refresh_tokens.php
```

### 2. Update Environment Variables
Add to `.env`:
```env
JWT_ACCESS_SECRET=your_access_secret_key_here
JWT_REFRESH_SECRET=your_refresh_secret_key_here  
JWT_ACCESS_EXPIRY=900
JWT_REFRESH_EXPIRY=604800
```

### 3. Test Implementation
```bash
php scripts/test_refresh_tokens.php
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login with email/password, returns access + refresh tokens
- `POST /api/auth/refresh` - Exchange refresh token for new token pair
- `POST /api/auth/logout` - Logout from all devices (revoke all refresh tokens)
- `POST /api/auth/logout-single` - Logout from single device (revoke specific refresh token)

### User Management
- `GET /api/auth/me` - Get current user info (requires access token)
- `GET /api/auth/sessions` - Get active sessions/devices (requires access token)
- `DELETE /api/auth/sessions/{jti}` - Revoke specific session (requires access token)

### Admin Only
- `GET /api/auth/admin/token-stats` - View token statistics
- `POST /api/auth/admin/cleanup-tokens` - Cleanup expired tokens

## Frontend Integration

### Login Flow
```javascript
// Login request
const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
});

const { tokens, user } = await response.json();

// Store tokens
localStorage.setItem('access_token', tokens.access_token);
localStorage.setItem('refresh_token', tokens.refresh_token);
```

### Automatic Token Refresh
```javascript
// Axios interceptor for automatic token refresh
axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 401) {
            const refreshToken = localStorage.getItem('refresh_token');
            
            if (refreshToken) {
                try {
                    const response = await axios.post('/api/auth/refresh', {
                        refresh_token: refreshToken
                    });
                    
                    const { tokens } = response.data;
                    localStorage.setItem('access_token', tokens.access_token);
                    localStorage.setItem('refresh_token', tokens.refresh_token);
                    
                    // Retry original request
                    error.config.headers.Authorization = `Bearer ${tokens.access_token}`;
                    return axios.request(error.config);
                } catch (refreshError) {
                    // Refresh failed, redirect to login
                    localStorage.clear();
                    window.location.href = '/login';
                }
            }
        }
        
        return Promise.reject(error);
    }
);
```

## Token Lifecycle

### Access Token (15 minutes)
- Short-lived for security
- Contains user information
- Used for API requests
- Automatically refreshed when expired

### Refresh Token (7 days)
- Long-lived for convenience
- Stored in database with revocation support
- Used only to generate new token pairs
- Tracked per device/session

## Security Features

### Token Storage
- Refresh tokens stored as SHA256 hashes
- Unique JTI (JWT ID) for each token
- User agent and IP tracking
- Automatic cleanup of expired tokens

### Revocation
- Single device logout (revoke specific token)
- All devices logout (revoke all user tokens)
- Admin token management
- Automatic cleanup on user deactivation

### Monitoring
- Token usage statistics
- Session management per user
- Failed refresh attempt tracking
- Cleanup metrics and reporting

## Production Deployment

### Cron Job Setup
```bash
# Add to crontab for automatic cleanup
0 2 * * * cd /path/to/backend && php scripts/cleanup_refresh_tokens.php
```

### Security Considerations
- Use strong, unique secrets for access and refresh tokens
- Set appropriate token expiry times
- Monitor token usage patterns
- Implement rate limiting on refresh endpoint
- Log security events and failed attempts

### Database Maintenance
- Refresh tokens table grows over time
- Regular cleanup removes expired/revoked tokens
- Consider partitioning for high-traffic systems
- Monitor table size and query performance

## Migration from Single Token System

### Phase 1: Deploy Dual System
1. Deploy refresh token code alongside existing auth
2. Update frontend to use new login endpoint
3. Both old and new auth systems work simultaneously

### Phase 2: Migrate Users
1. Force re-login to generate refresh tokens
2. Monitor adoption rates
3. Provide user education about new session management

### Phase 3: Remove Legacy System
1. Disable old JWT endpoints
2. Remove legacy auth code
3. Update all API documentation
