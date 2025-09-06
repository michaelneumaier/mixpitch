const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/searches/projects',
    method: 'GET',
    params: bundle.inputData
  });

  return response.data.data || [];
};

module.exports = {
  key: 'findProjects',
  noun: 'Project',
  display: {
    label: 'Find Client Projects',
    description: 'Search for client projects by various criteria.',
  },
  operation: {
    inputFields: [
      {
        key: 'client_id',
        label: 'Client',
        type: 'integer',
        required: false,
        helpText: 'Find projects for specific client',
        dynamic: 'clientList.id.name'
      },
      {
        key: 'client_email',
        label: 'Client Email',
        type: 'string',
        required: false,
        helpText: 'Find projects by client email'
      },
      {
        key: 'project_name',
        label: 'Project Name',
        type: 'string',
        required: false,
        helpText: 'Search by project name (partial match)'
      },
      {
        key: 'status',
        label: 'Project Status',
        type: 'string',
        required: false,
        choices: {
          pending: 'Pending',
          in_progress: 'In Progress',
          ready_for_review: 'Ready for Review',
          approved: 'Approved',
          completed: 'Completed',
          denied: 'Denied'
        },
        helpText: 'Filter by project status'
      },
      {
        key: 'pitch_status',
        label: 'Pitch Status',
        type: 'string',
        required: false,
        choices: {
          pending: 'Pending',
          in_progress: 'In Progress',
          ready_for_review: 'Ready for Review',
          approved: 'Approved',
          completed: 'Completed',
          client_revisions_requested: 'Client Revisions Requested'
        },
        helpText: 'Filter by pitch status'
      },
      {
        key: 'payment_status',
        label: 'Payment Status',
        type: 'string',
        required: false,
        choices: {
          unpaid: 'Unpaid',
          paid: 'Paid',
          pending: 'Pending',
          refunded: 'Refunded'
        },
        helpText: 'Filter by payment status'
      },
      {
        key: 'is_prioritized',
        label: 'High Priority Only',
        type: 'boolean',
        required: false,
        helpText: 'Show only high priority projects'
      },
      {
        key: 'created_after',
        label: 'Created After',
        type: 'datetime',
        required: false,
        helpText: 'Show projects created after this date'
      },
      {
        key: 'created_before',
        label: 'Created Before',
        type: 'datetime',
        required: false,
        helpText: 'Show projects created before this date'
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
      id: 123,
      name: 'Album Mix & Master',
      title: 'Professional Album Mixing',
      description: 'Full album mixing and mastering for rock album',
      status: 'in_progress',
      workflow_type: 'client_management',
      project_type: 'mixing',
      budget: 2500,
      payment_amount: 2500.00,
      deadline: '2024-02-15',
      is_prioritized: true,
      is_private: false,
      created_at: '2024-01-15T14:30:00Z',
      updated_at: '2024-01-20T16:45:00Z',
      client: {
        id: 42,
        email: 'artist@example.com',
        name: 'Sarah Johnson',
        company: 'Indie Records',
        phone: '+1-555-0123',
        status: 'active',
        tags: ['indie', 'rock'],
        total_spent: 7500.00,
        total_projects: 3,
        last_contacted_at: '2024-01-18T09:30:00Z'
      },
      pitch: {
        id: 456,
        status: 'in_progress',
        payment_status: 'unpaid',
        payment_amount: 2500.00,
        created_at: '2024-01-15T14:30:00Z',
        updated_at: '2024-01-20T16:45:00Z'
      },
      days_since_created: 5,
      days_until_deadline: 25,
      total_files: 8,
      total_pitch_files: 3,
      producer_dashboard_url: 'https://mixpitch.com/projects/123',
      client_portal_url: 'https://mixpitch.com/client/portal/abc123',
      is_overdue: false,
      requires_attention: false,
      next_action: 'Continue work and submit for review'
    },
  },
};