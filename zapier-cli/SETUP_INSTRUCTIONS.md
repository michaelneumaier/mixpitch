# MixPitch Zapier Integration Setup Instructions

## What You Need to Do Next

You have a **MixPitch** integration (App230285) in your Zapier developer account that needs to be connected to the API endpoints we just built.

## Step-by-Step Setup:

### 1. Link Your Local Code to Zapier Integration
```bash
cd zapier-cli
zapier link
# Select: MixPitch (230285) when prompted
```

### 2. Get Your API Key
Visit: http://mixpitch.test/zapier/setup (while logged in to MixPitch)
- Click "Generate API Key" 
- Copy the generated token (looks like: `3|OWVnduQmKwbXWbvgj1ErnHfPYJ19LYABXnGTDKnC13e3b083`)

### 3. Update Your Integration Code
The files we created in `zapier-cli/` contain the integration logic:

**Authentication** (`authentication.js`):
- Tests against: `http://mixpitch.test/api/zapier/auth/test`
- Validates API key and returns user info

**Triggers** (`triggers/newClient.js`):
- Polls: `http://mixpitch.test/api/zapier/triggers/clients/new`
- Returns new clients since last check

**Actions** (`creates/createClient.js`):
- Posts to: `http://mixpitch.test/api/zapier/actions/clients/create`
- Creates new clients in your MixPitch account

### 4. Push Your Integration to Zapier
```bash
# Validate the integration structure
zapier validate

# Push to your Zapier developer account
zapier push

# Create a version for testing
zapier versions:create 1.0.0
```

### 5. Test Your Integration
```bash
# Test locally first
zapier test

# Test specific parts
zapier test --grep="authentication"
zapier test --grep="triggers"
zapier test --grep="creates"
```

### 6. Update Test Files (Optional)
Replace `'YOUR_API_KEY_HERE'` in these files with your actual API key:
- `test/authentication.test.js`
- `test/triggers.test.js`
- `test/creates.test.js`

### 7. Test in Zapier Platform
Once pushed:
1. Go to https://developer.zapier.com/
2. Find your MixPitch integration
3. Click "Test" to try the authentication
4. Create test Zaps with your triggers and actions

## What Each File Does:

### `index.js`
Main integration file that:
- Configures authentication (Bearer token)
- Registers your triggers and actions
- Sets up request middleware to add API key headers

### `authentication.js` 
Custom authentication that:
- Prompts user for their MixPitch API key
- Tests the key against your API
- Stores the key for future requests

### `triggers/newClient.js`
Polling trigger that:
- Checks for new clients every 15 minutes
- Returns client data when found
- Handles pagination and timestamps

### `creates/createClient.js`
Action that:
- Creates new clients in MixPitch
- Accepts email, name, company, phone, notes, tags
- Returns created client data

## Understanding the Architecture:

```
Zapier User → Zapier Platform → Your Integration Code → MixPitch API → Your Database
```

1. **User configures Zap** with their MixPitch API key
2. **Zapier calls your integration** (hosted on Zapier's servers)
3. **Your integration calls your API** at mixpitch.test (will be your production domain)
4. **Your API returns data** to Zapier
5. **Zapier completes the automation**

## For Production:
- Update URLs in your integration files from `http://mixpitch.test` to your production domain
- Make sure your production API has CORS configured for Zapier
- Use environment variables for different environments (dev/staging/prod)

## Current Status:
✅ API endpoints built and tested
✅ Integration code created
✅ Developer account ready
🔄 Need to: Link, validate, and push to Zapier

The integration is ready - you just need to connect your local code to your Zapier developer account!