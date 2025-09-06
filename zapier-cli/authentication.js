const testAuth = async (z, bundle) => {
  // Call the auth test endpoint to verify the API key works
  const response = await z.request({
    method: 'GET',
    url: 'https://mixpitch.com/api/zapier/auth/test',
  });
  
  if (response.status !== 200) {
    throw new Error('The API Key you supplied is invalid');
  }
  
  return response.data;
};

module.exports = {
  type: 'custom',
  fields: [
    {
      computed: false,
      key: 'api_key',
      required: true,
      label: 'API Key',
      type: 'string',
      helpText: 'Get your API key from your MixPitch account at https://mixpitch.com/zapier/setup'
    }
  ],
  test: testAuth,
  connectionLabel: '{{name}} ({{email}})',
};