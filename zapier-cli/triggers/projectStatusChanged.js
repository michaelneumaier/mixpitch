const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/triggers/projects/status-changed',
    method: 'GET',
    params: {
      since: bundle.meta.timestamp || new Date(Date.now() - 15 * 60 * 1000).toISOString()
    }
  });

  return response.data.data || [];
};

module.exports = {
  key: 'projectStatusChanged',
  noun: 'Project Status',
  display: {
    label: 'Client Project Status Changed',
    description: 'Triggers when a client project or pitch status changes.',
  },
  operation: {
    perform: perform,
    sample: {
      id: 'pitch_456',
      type: 'pitch_status_change',
      project_id: 123,
      project_name: 'Album Mix & Master',
      pitch_id: 456,
      pitch_status: 'ready_for_review',
      previous_pitch_status: 'in_progress',
      payment_status: 'unpaid',
      updated_at: '2024-01-20T16:45:00Z',
      project: {
        id: 123,
        name: 'Album Mix & Master',
        title: 'Professional Album Mixing',
        status: 'in_progress',
        workflow_type: 'client_management',
        budget: 2500,
        payment_amount: 2500.00,
        deadline: '2024-02-15',
        created_at: '2024-01-15T14:30:00Z'
      },
      client: {
        id: 42,
        email: 'artist@example.com',
        name: 'Sarah Johnson',
        company: 'Indie Records',
        status: 'active',
        tags: ['indie', 'rock']
      },
      pitch: {
        id: 456,
        status: 'ready_for_review',
        payment_status: 'unpaid',
        payment_amount: 2500.00,
        created_at: '2024-01-15T14:30:00Z',
        updated_at: '2024-01-20T16:45:00Z'
      },
      is_completion: false,
      is_client_approval: false,
      is_ready_for_review: true,
      requires_client_action: true,
      producer_dashboard_url: 'https://mixpitch.com/projects/123',
      client_portal_url: 'https://mixpitch.com/client/portal/abc123'
    },
  },
};