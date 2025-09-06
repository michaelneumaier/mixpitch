const zapier = require('zapier-platform-core');

// Use your actual API key here (replace with the one from /zapier/setup)
const App = require('../index');
const appTester = zapier.createAppTester(App);

describe('Authentication', () => {
  it('should authenticate with valid API key', async () => {
    const bundle = {
      authData: {
        api_key: 'YOUR_API_KEY_HERE' // Replace with actual API key
      }
    };

    const response = await appTester(App.authentication.test, bundle);
    response.should.have.property('user_id');
    response.should.have.property('name');
    response.should.have.property('email');
    response.integration_status.should.eql('connected');
  });

  it('should reject invalid API key', async () => {
    const bundle = {
      authData: {
        api_key: 'invalid_key'
      }
    };

    try {
      await appTester(App.authentication.test, bundle);
      throw new Error('Should have thrown an error');
    } catch (error) {
      error.message.should.containEql('API Key');
    }
  });
});