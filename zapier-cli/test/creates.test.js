const zapier = require('zapier-platform-core');

const App = require('../index');
const appTester = zapier.createAppTester(App);

describe('Creates', () => {
  const bundle = {
    authData: {
      api_key: 'YOUR_API_KEY_HERE' // Replace with actual API key
    },
    inputData: {
      email: 'zapier-test-' + Date.now() + '@example.com',
      name: 'Zapier Test Client',
      company: 'Test Company LLC',
      tags: ['zapier', 'test']
    }
  };

  it('should create a new client', async () => {
    const response = await appTester(App.creates.createClient.operation.perform, bundle);
    
    response.should.have.property('id');
    response.should.have.property('email', bundle.inputData.email);
    response.should.have.property('name', bundle.inputData.name);
    response.should.have.property('status', 'active');
    response.should.have.property('was_created');
  });
});