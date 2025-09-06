const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/searches/clients',
    method: 'GET',
    params: bundle.inputData
  });

  return response.data.data || [];
};

module.exports = {
  key: 'findClient',
  noun: 'Client',
  display: {
    label: 'Find Client',
    description: 'Search for clients by email, name, company, or other criteria.',
  },
  operation: {
    inputFields: [
      {
        key: 'query',
        label: 'Search Query',
        type: 'string',
        required: false,
        helpText: 'General search across email, name, company, and notes'
      },
      {
        key: 'email',
        label: 'Email',
        type: 'string',
        required: false,
        helpText: 'Search for exact email match'
      },
      {
        key: 'name',
        label: 'Name',
        type: 'string',
        required: false,
        helpText: 'Search by client name (partial match)'
      },
      {
        key: 'company',
        label: 'Company',
        type: 'string',
        required: false,
        helpText: 'Search by company name (partial match)'
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
        helpText: 'Filter by client status'
      },
      {
        key: 'tags',
        label: 'Tags',
        type: 'string',
        list: true,
        required: false,
        helpText: 'Filter by tags (must have all specified tags)'
      },
      {
        key: 'limit',
        label: 'Limit',
        type: 'integer',
        required: false,
        default: '10',
        helpText: 'Maximum number of results to return (1-50)'
      }
    ],
    perform: perform,
    sample: {
      id: 1,
      email: 'john@example.com',
      name: 'John Doe',
      company: 'Acme Corp',
      phone: '+1-555-0123',
      status: 'active',
      tags: ['vip', 'referral'],
      timezone: 'America/New_York',
      notes: 'Prefers phone calls in the morning',
      total_spent: 5000.00,
      total_projects: 3,
      last_contacted_at: '2024-01-10T10:30:00Z',
      created_at: '2023-01-15T09:00:00Z',
      updated_at: '2024-01-10T10:30:00Z',
      days_since_contact: 5,
      active_reminders_count: 2,
      latest_project: {
        id: 123,
        name: 'Album Mix',
        status: 'in_progress',
        created_at: '2024-01-05T14:00:00Z'
      },
      search_score: 8.5
    },
  },
};