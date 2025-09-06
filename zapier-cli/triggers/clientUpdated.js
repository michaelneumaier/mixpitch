const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/triggers/clients/updated',
    method: 'GET',
    params: {
      since: bundle.meta.timestamp || new Date(Date.now() - 15 * 60 * 1000).toISOString()
    }
  });

  return response.data.data || [];
};

module.exports = {
  key: 'clientUpdated',
  noun: 'Client',
  display: {
    label: 'Client Updated',
    description: 'Triggers when an existing client is updated (name, status, tags, etc).',
  },
  operation: {
    perform: perform,
    sample: {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      company: 'Acme Corp',
      status: 'active',
      tags: ['vip', 'referral'],
      total_spent: 5000.00,
      total_projects: 3,
      last_contacted_at: '2024-01-15T10:30:00Z',
      created_at: '2023-01-15T10:30:00Z',
      updated_at: '2024-01-15T10:30:00Z',
      changes_detected: ['contacted'],
      latest_project: {
        id: 123,
        name: 'Album Mix',
        status: 'in_progress',
        created_at: '2024-01-10T09:00:00Z'
      }
    },
  },
};