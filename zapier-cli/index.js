const authentication = require('./authentication');
const newClientTrigger = require('./triggers/newClient');
const createClientAction = require('./creates/createClient');

// Add authentication to all requests
const addApiKeyToHeader = (request, z, bundle) => {
  if (bundle.authData.api_key) {
    request.headers.Authorization = `Bearer ${bundle.authData.api_key}`;
  }
  return request;
};

module.exports = {
  version: require('./package.json').version,
  platformVersion: require('zapier-platform-core').version,

  authentication: authentication,

  beforeRequest: [addApiKeyToHeader],

  triggers: {
    [newClientTrigger.key]: newClientTrigger,
  },

  creates: {
    [createClientAction.key]: createClientAction,
  },

  searchOrCreates: {},
};