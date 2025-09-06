const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/actions/projects/create',
    method: 'POST',
    body: JSON.stringify(bundle.inputData),
    headers: {
      'Content-Type': 'application/json',
    },
  });

  return response.data.data;
};

module.exports = {
  key: 'createProject',
  noun: 'Project',
  display: {
    label: 'Create Client Project',
    description: 'Creates a new project for a client in your MixPitch account.',
  },
  operation: {
    inputFields: [
      {
        key: 'name',
        label: 'Project Name',
        type: 'string',
        required: true,
        helpText: 'The name/title of the project'
      },
      {
        key: 'client_id',
        label: 'Client',
        type: 'integer',
        required: false,
        helpText: 'Select the client for this project',
        dynamic: 'clientList.id.name'
      },
      {
        key: 'client_email',
        label: 'Client Email',
        type: 'string',
        required: false,
        helpText: 'Alternative: Find client by email if not selected above'
      },
      {
        key: 'description',
        label: 'Description',
        type: 'text',
        required: false,
        helpText: 'Project description and requirements'
      },
      {
        key: 'project_type',
        label: 'Project Type',
        type: 'string',
        required: false,
        choices: {
          mixing: 'Mixing',
          mastering: 'Mastering',
          mixing_mastering: 'Mixing & Mastering',
          production: 'Production',
          songwriting: 'Songwriting',
          other: 'Other'
        },
        helpText: 'Type of audio service needed'
      },
      {
        key: 'budget',
        label: 'Budget',
        type: 'integer',
        required: false,
        helpText: 'Project budget in dollars (whole number)'
      },
      {
        key: 'payment_amount',
        label: 'Payment Amount',
        type: 'number',
        required: false,
        helpText: 'Exact payment amount (can include cents)'
      },
      {
        key: 'deadline',
        label: 'Deadline',
        type: 'datetime',
        required: false,
        helpText: 'Project deadline date'
      },
      {
        key: 'notes',
        label: 'Notes',
        type: 'text',
        required: false,
        helpText: 'Internal notes about the project'
      },
      {
        key: 'is_prioritized',
        label: 'High Priority',
        type: 'boolean',
        required: false,
        default: 'false',
        helpText: 'Mark this project as high priority'
      },
      {
        key: 'is_private',
        label: 'Private Project',
        type: 'boolean',
        required: false,
        default: 'false',
        helpText: 'Make this project private (not publicly visible)'
      },
      {
        key: 'auto_allow_access',
        label: 'Auto-Allow Client Access',
        type: 'boolean',
        required: false,
        default: 'true',
        helpText: 'Automatically give client access to view progress'
      },
      {
        key: 'create_client_if_missing',
        label: 'Create Client if Missing',
        type: 'boolean',
        required: false,
        default: 'false',
        helpText: 'Create a new client if email doesn\'t exist'
      },
      {
        key: 'client_name',
        label: 'New Client Name',
        type: 'string',
        required: false,
        helpText: 'Name for new client (if creating)'
      },
      {
        key: 'client_company',
        label: 'New Client Company',
        type: 'string',
        required: false,
        helpText: 'Company for new client (if creating)'
      }
    ],
    perform: perform,
    sample: {
      id: 789,
      name: 'EP Mixing Project',
      title: 'EP Mixing Project',
      description: 'Mix 5-track indie rock EP',
      status: 'pending',
      workflow_type: 'client_management',
      project_type: 'mixing',
      budget: 1500,
      payment_amount: 1500.00,
      deadline: '2024-03-01',
      is_prioritized: false,
      is_private: false,
      created_at: '2024-01-22T10:00:00Z',
      updated_at: '2024-01-22T10:00:00Z',
      client: {
        id: 55,
        email: 'band@example.com',
        name: 'The Rockers',
        company: 'Rock Music LLC',
        status: 'active',
        was_created: false,
        total_projects: 1
      },
      pitch: {
        id: 678,
        status: 'pending',
        payment_status: 'unpaid',
        created_at: '2024-01-22T10:00:00Z'
      },
      producer_dashboard_url: 'https://mixpitch.com/projects/789',
      client_portal_url: 'https://mixpitch.com/client/portal/def456',
      was_created: true,
      client_was_created: false,
      pitch_created: true
    },
  },
};