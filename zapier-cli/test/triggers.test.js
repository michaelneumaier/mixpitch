const zapier = require('zapier-platform-core');

const App = require('../index');
const appTester = zapier.createAppTester(App);

describe('Triggers', () => {
  const bundle = {
    authData: {
      api_key: 'YOUR_API_KEY_HERE' // Replace with actual API key
    }
  };

  it('should load new clients', async () => {
    const response = await appTester(App.triggers.newClient.operation.perform, bundle);
    response.should.be.an.Array();
    
    // If you have test data, check the structure
    if (response.length > 0) {
      const client = response[0];
      client.should.have.property('id');
      client.should.have.property('email');
      client.should.have.property('name');
      client.should.have.property('status');
    }
  });
});