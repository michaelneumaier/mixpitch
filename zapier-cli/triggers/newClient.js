const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/triggers/clients/new',
    method: 'GET',
    params: {
      since: bundle.meta.timestamp || new Date(Date.now() - 15 * 60 * 1000).toISOString()
    }
  });

  return response.data.data || [];
};

module.exports = {
  key: 'newClient',
  noun: 'Client',
  display: {
    label: 'New Client Added',
    description: 'Triggers when a new client is added to your MixPitch account.',
  },
  operation: {
    perform: perform,
    sample: {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      company: 'Acme Corp',
      status: 'active',
      created_at: '2024-01-15T10:30:00Z',
      total_projects: 2,
      tags: ['vip', 'referral']
    },
  },
};