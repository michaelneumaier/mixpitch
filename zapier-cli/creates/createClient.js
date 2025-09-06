const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/actions/clients/create',
    method: 'POST',
    body: JSON.stringify(bundle.inputData),
    headers: {
      'Content-Type': 'application/json',
    },
  });

  return response.data.data;
};

module.exports = {
  key: 'createClient',
  noun: 'Client',
  display: {
    label: 'Create Client',
    description: 'Creates a new client in your MixPitch account.',
  },
  operation: {
    inputFields: [
      {
        key: 'email',
        label: 'Email',
        type: 'string',
        required: true,
        helpText: 'The client\'s email address'
      },
      {
        key: 'name',
        label: 'Name',
        type: 'string',
        required: false,
        helpText: 'The client\'s full name'
      },
      {
        key: 'company',
        label: 'Company',
        type: 'string',
        required: false,
        helpText: 'The client\'s company name'
      },
      {
        key: 'phone',
        label: 'Phone',
        type: 'string',
        required: false,
        helpText: 'The client\'s phone number'
      },
      {
        key: 'notes',
        label: 'Notes',
        type: 'text',
        required: false,
        helpText: 'Any notes about the client'
      },
      {
        key: 'tags',
        label: 'Tags',
        type: 'string',
        list: true,
        required: false,
        helpText: 'Tags to organize the client (one per line)'
      }
    ],
    perform: perform,
    sample: {
      id: 2,
      name: 'Jane Smith',
      email: 'jane@example.com',
      company: 'Design Co',
      status: 'active',
      created_at: '2024-01-15T16:00:00Z',
      was_created: true
    },
  },
};