const authentication = require('./authentication');

// Triggers
const newClientTrigger = require('./triggers/newClient');
const clientUpdatedTrigger = require('./triggers/clientUpdated');
const reminderDueTrigger = require('./triggers/reminderDue');
const clientListTrigger = require('./triggers/clientList');

// Phase 2 - Project Integration Triggers
const clientProjectCreatedTrigger = require('./triggers/clientProjectCreated');
const projectStatusChangedTrigger = require('./triggers/projectStatusChanged');

// Actions
const createClientAction = require('./creates/createClient');
const updateClientAction = require('./creates/updateClient');
const addReminderAction = require('./creates/addReminder');

// Phase 2 - Project Actions
const createProjectAction = require('./creates/createProject');

// Searches
const findClientSearch = require('./searches/findClient');

// Phase 2 - Project Searches
const findProjectsSearch = require('./searches/findProjects');

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
    [clientUpdatedTrigger.key]: clientUpdatedTrigger,
    [reminderDueTrigger.key]: reminderDueTrigger,
    [clientListTrigger.key]: clientListTrigger,
    
    // Phase 2 - Project Integration
    [clientProjectCreatedTrigger.key]: clientProjectCreatedTrigger,
    [projectStatusChangedTrigger.key]: projectStatusChangedTrigger,
  },

  creates: {
    [createClientAction.key]: createClientAction,
    [updateClientAction.key]: updateClientAction,
    [addReminderAction.key]: addReminderAction,
    
    // Phase 2 - Project Actions
    [createProjectAction.key]: createProjectAction,
  },

  searches: {
    [findClientSearch.key]: findClientSearch,
    
    // Phase 2 - Project Searches
    [findProjectsSearch.key]: findProjectsSearch,
  },

  searchOrCreates: {
    [findClientSearch.key]: {
      key: findClientSearch.key,
      display: {
        label: 'Find or Create Client',
        description: 'Search for a client, with option to create if not found.',
      },
      search: findClientSearch.key,
      create: createClientAction.key,
    },
    [findProjectsSearch.key]: {
      key: findProjectsSearch.key,
      display: {
        label: 'Find or Create Project',
        description: 'Search for a client project, with option to create if not found.',
      },
      search: findProjectsSearch.key,
      create: createProjectAction.key,
    },
  },
};