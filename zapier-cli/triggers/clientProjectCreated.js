const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/triggers/projects/client-created',
    method: 'GET',
    params: {
      since: bundle.meta.timestamp || new Date(Date.now() - 15 * 60 * 1000).toISOString()
    }
  });

  return response.data.data || [];
};

module.exports = {
  key: 'clientProjectCreated',
  noun: 'Project',
  display: {
    label: 'New Client Project Created',
    description: 'Triggers when a new project is created for a client.',
  },
  operation: {
    perform: perform,
    sample: {
      id: 123,
      name: 'Album Mix & Master',
      title: 'Professional Album Mixing',
      description: 'Full album mixing and mastering for rock album',
      status: 'pending',
      workflow_type: 'client_management',
      project_type: 'mixing',
      budget: 2500,
      payment_amount: 2500.00,
      deadline: '2024-02-15',
      is_prioritized: false,
      is_private: false,
      created_at: '2024-01-15T14:30:00Z',
      updated_at: '2024-01-15T14:30:00Z',
      client: {
        id: 42,
        email: 'artist@example.com',
        name: 'Sarah Johnson',
        company: 'Indie Records',
        phone: '+1-555-0123',
        status: 'active',
        tags: ['indie', 'rock'],
        total_spent: 5000.00,
        total_projects: 3
      },
      pitch: {
        id: 456,
        status: 'pending',
        payment_status: 'unpaid',
        created_at: '2024-01-15T14:30:00Z'
      },
      producer_dashboard_url: 'https://mixpitch.com/projects/123',
      client_portal_url: 'https://mixpitch.com/client/portal/abc123',
      has_client_access: true,
      requires_license: false,
      total_files: 0
    },
  },
};