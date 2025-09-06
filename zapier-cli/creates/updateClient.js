const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/actions/clients/update',
    method: 'POST',
    body: JSON.stringify(bundle.inputData),
    headers: {
      'Content-Type': 'application/json',
    },
  });

  return response.data.data;
};

module.exports = {
  key: 'updateClient',
  noun: 'Client',
  display: {
    label: 'Update Client',
    description: 'Updates an existing client in your MixPitch account.',
  },
  operation: {
    inputFields: [
      {
        key: 'id',
        label: 'Client ID',
        type: 'integer',
        required: false,
        helpText: 'The ID of the client to update. Either ID or email is required.',
        dynamic: 'clientList.id.name'
      },
      {
        key: 'email',
        label: 'Client Email',
        type: 'string',
        required: false,
        helpText: 'Find client by email if ID is not provided.'
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
        key: 'status',
        label: 'Status',
        type: 'string',
        required: false,
        choices: {
          active: 'Active',
          inactive: 'Inactive',
          blocked: 'Blocked'
        },
        helpText: 'Client status'
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
      },
      {
        key: 'append_tags',
        label: 'Append Tags',
        type: 'boolean',
        required: false,
        helpText: 'If true, append tags to existing ones instead of replacing'
      },
      {
        key: 'mark_as_contacted',
        label: 'Mark as Contacted',
        type: 'boolean',
        required: false,
        helpText: 'Update the last contacted timestamp to now'
      }
    ],
    perform: perform,
    sample: {
      id: 2,
      name: 'Jane Smith',
      email: 'jane@example.com',
      company: 'Design Co',
      status: 'active',
      tags: ['vip', 'referral'],
      total_spent: 10000.00,
      total_projects: 5,
      last_contacted_at: '2024-01-15T16:00:00Z',
      created_at: '2023-06-15T09:00:00Z',
      updated_at: '2024-01-15T16:00:00Z',
      fields_updated: ['name', 'tags'],
      was_contacted: true
    },
  },
};