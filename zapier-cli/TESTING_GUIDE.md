# MixPitch Zapier Integration Testing Guide

## Quick Setup & Testing

Your Zapier integration is working! Here's what we've successfully implemented and tested:

### ✅ Working API Endpoints

All endpoints are accessible at: `http://mixpitch.test/api/zapier/`

#### 1. Authentication Test
```bash
curl -H "Authorization: Bearer YOUR_API_KEY" http://mixpitch.test/api/zapier/auth/test
```

#### 2. New Client Trigger (Polling)
```bash
curl -H "Authorization: Bearer YOUR_API_KEY" "http://mixpitch.test/api/zapier/triggers/clients/new"
```

#### 3. Create Client Action
```bash
curl -X POST -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{"email":"test@example.com","name":"Test Client","company":"Test Co"}' \
     http://mixpitch.test/api/zapier/actions/clients/create
```

### 🔑 API Key Generation

**From Laravel Tinker:**
```php
// Generate API key for any user
$user = \App\Models\User::find(USER_ID);
$token = $user->createToken('Zapier Integration', ['zapier-client-management'])->plainTextToken;
echo "API Key: {$token}";
```

**From Web Interface:**
Visit: http://mixpitch.test/zapier/setup (requires login)

### 📋 Test Results

**✅ Authentication Endpoint:** Working
- Returns user info and connection status

**✅ New Client Trigger:** Working  
- Returns empty array when no new clients
- Returns client data when clients exist since specified time

**✅ Create Client Action:** Working
- Successfully creates new client
- Returns structured response with `was_created` flag

### 🚀 Next Steps for Full Zapier CLI Testing

Once the npm dependencies are resolved, you can:

1. **Update test files with your API key:**
   ```bash
   # Replace 'YOUR_API_KEY_HERE' in these files:
   - test/authentication.test.js
   - test/triggers.test.js  
   - test/creates.test.js
   ```

2. **Run Zapier CLI tests:**
   ```bash
   npm test                    # Run all tests
   zapier test                # Run with Zapier's test runner
   zapier validate            # Validate integration structure
   ```

3. **Test individual components:**
   ```bash
   zapier test --grep="authentication"
   zapier test --grep="triggers" 
   zapier test --grep="creates"
   ```

### 📁 Integration Structure

```
zapier-cli/
├── index.js              # Main integration file
├── authentication.js     # Custom auth config
├── triggers/
│   └── newClient.js      # New client polling trigger
├── creates/
│   └── createClient.js   # Create client action
└── test/
    ├── authentication.test.js
    ├── triggers.test.js
    └── creates.test.js
```

### 🔧 Configuration

The integration is configured for:
- **Base URL:** http://mixpitch.test
- **Authentication:** Bearer token (API key)
- **Polling Interval:** 15 minutes (default)
- **Input Fields:** email, name, company, phone, notes, tags

### 🎯 What's Ready for Production

Your core Zapier integration foundation is complete:
- ✅ API authentication system
- ✅ Client management triggers and actions  
- ✅ Consistent response formatting
- ✅ Database structure for webhooks and usage tracking
- ✅ Laravel test coverage

All API endpoints are working correctly and ready for Zapier to consume!