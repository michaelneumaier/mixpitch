# MixPitch Zapier Integration Setup Guide
## Platform Setup & Account Requirements

### 🎯 **Overview**

This guide covers everything you need to do on the Zapier side to create and publish your MixPitch integration, from account setup to app submission.

---

## 📋 **Prerequisites Checklist**

### **Business Requirements**
- [ ] MixPitch API endpoints implemented and deployed
- [ ] API documentation complete
- [ ] Test environment with sample data available
- [ ] Production environment ready for webhook testing
- [ ] Support documentation prepared

### **Zapier Platform Requirements**
- [ ] Zapier Developer Account (free)
- [ ] Company/business information ready
- [ ] App icon and branding assets (512x512px PNG)
- [ ] Integration description and marketing copy
- [ ] Test user accounts for beta testing

---

## 1️⃣ **Create Zapier Developer Account**

### **Step 1: Sign Up**
1. Go to [https://zapier.com/app/developer](https://zapier.com/app/developer)
2. Click "Build an Integration" 
3. Sign up with business email (recommended: use your MixPitch domain)
4. Choose "I'm building an integration for my own app"

### **Step 2: Developer Profile Setup**
```
Company: MixPitch
Website: https://mixpitch.com
Description: Music collaboration platform for producers and artists
```

### **Account Type Needed**
- **Free Developer Account**: Sufficient for development and testing
- **No paid Zapier subscription required** for app development
- Users will need Zapier accounts to use your integration

---

## 2️⃣ **Create Your MixPitch Integration**

### **Step 1: Create New Integration**
1. In Zapier Developer Dashboard, click "Create an Integration"
2. Choose "Build from Scratch"
3. Fill out basic information:

```
Integration Name: MixPitch
Description: Automate your music production client workflow
Category: Project Management
Website: https://mixpitch.com
```

### **Step 2: Integration Configuration**
```json
{
  "name": "MixPitch",
  "description": "Automate client management, project creation, and communication for music producers",
  "homepage_url": "https://mixpitch.com",
  "help_url": "https://mixpitch.com/help/zapier",
  "intended_audience": "Music producers managing client projects",
  "role": "Music Producer, Audio Professional, Freelancer",
  "categories": ["Project Management", "CRM", "Music & Audio"]
}
```

---

## 3️⃣ **Configure Authentication**

### **Authentication Type: API Key**
Since we're using Laravel Sanctum tokens, configure as API Key authentication:

1. **Authentication Type**: API Key
2. **API Key Location**: Header
3. **Header Name**: `Authorization`
4. **Header Value Template**: `Bearer {{api_key}}`

### **Test Authentication Endpoint**
Set up a test endpoint to verify API keys work:
```
Method: GET
URL: https://mixpitch.com/api/zapier/auth/test
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "name": "Producer Name",
    "email": "producer@example.com"
  }
}
```

### **Connection Label**
Template for how connections appear to users:
```
{{user.name}} ({{user.email}})
```

---

## 4️⃣ **Set Up Triggers**

### **Trigger 1: New Client Added**
```json
{
  "key": "new_client",
  "noun": "Client",
  "display": {
    "label": "New Client Added",
    "description": "Triggers when a new client is added to your MixPitch account"
  },
  "operation": {
    "type": "polling",
    "perform": {
      "url": "https://mixpitch.com/api/zapier/triggers/clients/new",
      "method": "GET",
      "params": {
        "since": "{{bundle.meta.timestamp}}"
      }
    }
  }
}
```

### **Trigger 2: Project Status Changed**
```json
{
  "key": "project_status_changed",
  "noun": "Project",
  "display": {
    "label": "Project Status Changed", 
    "description": "Triggers when a client management project status changes"
  },
  "operation": {
    "type": "polling",
    "perform": {
      "url": "https://mixpitch.com/api/zapier/triggers/projects/status-changed",
      "method": "GET",
      "params": {
        "since": "{{bundle.meta.timestamp}}"
      }
    }
  }
}
```

### **Trigger 3: Client Approved Project (Webhook)**
```json
{
  "key": "client_approved",
  "noun": "Approval",
  "display": {
    "label": "Client Approved Project",
    "description": "Triggers instantly when a client approves a project"
  },
  "operation": {
    "type": "hook",
    "perform": {
      "url": "https://mixpitch.com/api/zapier/webhooks/subscribe",
      "method": "POST",
      "body": {
        "target_url": "{{bundle.targetUrl}}",
        "event": "client_approved"
      }
    },
    "performUnsubscribe": {
      "url": "https://mixpitch.com/api/zapier/webhooks/unsubscribe",
      "method": "DELETE",
      "body": {
        "target_url": "{{bundle.targetUrl}}",
        "event": "client_approved"
      }
    }
  }
}
```

---

## 5️⃣ **Set Up Actions**

### **Action 1: Create Client**
```json
{
  "key": "create_client",
  "noun": "Client",
  "display": {
    "label": "Create Client",
    "description": "Add a new client to your MixPitch account"
  },
  "operation": {
    "perform": {
      "url": "https://mixpitch.com/api/zapier/actions/clients/create",
      "method": "POST",
      "body": {
        "email": "{{bundle.inputData.email}}",
        "name": "{{bundle.inputData.name}}",
        "company": "{{bundle.inputData.company}}",
        "phone": "{{bundle.inputData.phone}}",
        "notes": "{{bundle.inputData.notes}}",
        "tags": "{{bundle.inputData.tags}}"
      }
    }
  },
  "inputFields": [
    {
      "key": "email",
      "label": "Email",
      "type": "string",
      "required": true,
      "helpText": "Client's email address"
    },
    {
      "key": "name", 
      "label": "Name",
      "type": "string",
      "required": false,
      "helpText": "Client's full name"
    },
    {
      "key": "company",
      "label": "Company",
      "type": "string", 
      "required": false
    },
    {
      "key": "phone",
      "label": "Phone",
      "type": "string",
      "required": false
    },
    {
      "key": "notes",
      "label": "Notes",
      "type": "text",
      "required": false
    },
    {
      "key": "tags",
      "label": "Tags",
      "type": "string",
      "list": true,
      "required": false,
      "helpText": "Comma-separated list of tags"
    }
  ]
}
```

### **Action 2: Create Project**
```json
{
  "key": "create_project",
  "noun": "Project",
  "display": {
    "label": "Create Client Project",
    "description": "Create a new client management project in MixPitch"
  },
  "operation": {
    "perform": {
      "url": "https://mixpitch.com/api/zapier/actions/projects/create",
      "method": "POST",
      "body": {
        "name": "{{bundle.inputData.name}}",
        "description": "{{bundle.inputData.description}}",
        "client_email": "{{bundle.inputData.client_email}}",
        "client_name": "{{bundle.inputData.client_name}}",
        "budget": "{{bundle.inputData.budget}}",
        "deadline": "{{bundle.inputData.deadline}}"
      }
    }
  },
  "inputFields": [
    {
      "key": "name",
      "label": "Project Name",
      "type": "string",
      "required": true
    },
    {
      "key": "description",
      "label": "Description",
      "type": "text",
      "required": true
    },
    {
      "key": "client_email",
      "label": "Client Email",
      "type": "string",
      "required": true
    },
    {
      "key": "client_name",
      "label": "Client Name",
      "type": "string",
      "required": false
    },
    {
      "key": "budget",
      "label": "Budget",
      "type": "number",
      "required": false
    },
    {
      "key": "deadline",
      "label": "Deadline",
      "type": "datetime",
      "required": false
    }
  ]
}
```

---

## 6️⃣ **Set Up Searches**

### **Search 1: Find Client**
```json
{
  "key": "find_client",
  "noun": "Client",
  "display": {
    "label": "Find Client",
    "description": "Find an existing client by email address"
  },
  "operation": {
    "perform": {
      "url": "https://mixpitch.com/api/zapier/searches/clients/find",
      "method": "GET",
      "params": {
        "email": "{{bundle.inputData.email}}"
      }
    }
  },
  "inputFields": [
    {
      "key": "email",
      "label": "Client Email",
      "type": "string",
      "required": true,
      "helpText": "Email address to search for"
    }
  ]
}
```

---

## 7️⃣ **Testing Setup**

### **Create Test Data**
Before testing in Zapier, ensure your API has sample data:

```bash
# Run this command to create test data
php artisan zapier:setup-test-data {your-user-id}
```

### **Test in Zapier Platform**
1. **Authentication Test**: Test API key connection
2. **Trigger Tests**: Verify triggers return sample data
3. **Action Tests**: Test creating clients and projects
4. **Search Tests**: Test finding existing records

### **Sample Data Requirements**
- At least 3 test clients
- At least 2 test projects (different statuses)
- At least 1 completed project
- Sample events and status changes

---

## 8️⃣ **App Submission & Review**

### **Pre-Submission Checklist**
- [ ] All triggers, actions, and searches tested
- [ ] Authentication working properly
- [ ] Error handling tested (invalid data, network errors)
- [ ] Rate limiting handling implemented
- [ ] Documentation complete
- [ ] App icon and branding uploaded

### **App Review Process**
1. **Submit for Review**: Click "Submit for Review" in developer dashboard
2. **Review Timeline**: Typically 5-10 business days
3. **Common Review Items**:
   - Authentication flow
   - Error handling
   - Data accuracy
   - Documentation quality
   - User experience

### **App Approval Requirements**
- Must handle errors gracefully
- Must respect rate limits  
- Must provide helpful error messages
- Must follow Zapier's style guidelines
- Must be fully functional in production

---

## 9️⃣ **Launch Preparation**

### **Beta Testing Phase**
1. **Invite Beta Users**: Share private integration URL
2. **Collect Feedback**: Monitor usage and gather feedback
3. **Fix Issues**: Address any bugs or usability problems
4. **Performance Monitoring**: Watch API performance and error rates

### **Public Launch**
1. **App Goes Live**: Available in Zapier App Directory
2. **Marketing Assets**: Zapier provides marketing materials
3. **Analytics Access**: Track usage through Zapier dashboard
4. **Support Setup**: Be ready to handle user questions

### **Post-Launch Monitoring**
- **Usage Analytics**: Track adoption and popular triggers/actions
- **Error Monitoring**: Watch for API errors or integration issues  
- **User Feedback**: Respond to reviews and support requests
- **Feature Requests**: Plan future enhancements based on usage

---

## 🔧 **Technical Requirements Summary**

### **API Endpoints You Must Implement**
```
Authentication:
GET /api/zapier/auth/test

Triggers:
GET /api/zapier/triggers/clients/new
GET /api/zapier/triggers/projects/status-changed

Actions: 
POST /api/zapier/actions/clients/create
POST /api/zapier/actions/projects/create
POST /api/zapier/actions/projects/comment

Searches:
GET /api/zapier/searches/clients/find
GET /api/zapier/searches/projects/find

Webhooks:
POST /api/zapier/webhooks/subscribe
DELETE /api/zapier/webhooks/unsubscribe
```

### **Required Response Formats**
All responses must follow consistent format:
```json
{
  "success": true,
  "data": [...],
  "message": "Optional success message"
}
```

Error responses:
```json
{
  "success": false,
  "error": "Human-readable error message"
}
```

---

## 📞 **Support & Resources**

### **Zapier Resources**
- [Developer Documentation](https://zapier.com/developer/documentation/)
- [Platform UI Guide](https://zapier.com/developer/documentation/v2/platform-ui/)
- [Best Practices](https://zapier.com/developer/documentation/v2/best-practices/)

### **Getting Help**
- **Zapier Developer Community**: [community.zapier.com](https://community.zapier.com)
- **Support Email**: developer@zapier.com
- **Office Hours**: Check Zapier developer portal for scheduled sessions

### **Timeline Expectations**
- **Development**: 6 weeks (based on your existing API foundation)
- **Testing**: 1-2 weeks
- **Zapier Review**: 5-10 business days
- **Beta Testing**: 2-4 weeks
- **Total Time to Launch**: ~3-4 months

---

## ✅ **Next Immediate Actions**

1. **Create Zapier Developer Account** (15 minutes)
2. **Start Integration Setup** (1 hour) 
3. **Implement Authentication Test Endpoint** (your development team)
4. **Begin with New Client Trigger** (easiest to implement and test)

The Zapier platform setup is straightforward once your API endpoints are ready. Your excellent codebase foundation means the API implementation will be fast, making the overall timeline very achievable.