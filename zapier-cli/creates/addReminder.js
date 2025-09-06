const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/actions/reminders/create',
    method: 'POST',
    body: JSON.stringify(bundle.inputData),
    headers: {
      'Content-Type': 'application/json',
    },
  });

  return response.data.data;
};

module.exports = {
  key: 'addReminder',
  noun: 'Reminder',
  display: {
    label: 'Add Client Reminder',
    description: 'Creates a new reminder for a client in your MixPitch account.',
  },
  operation: {
    inputFields: [
      {
        key: 'client_id',
        label: 'Client',
        type: 'integer',
        required: false,
        helpText: 'Select the client for this reminder',
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
        key: 'note',
        label: 'Reminder Note',
        type: 'text',
        required: true,
        helpText: 'What do you need to remember about this client?'
      },
      {
        key: 'due_at',
        label: 'Due Date/Time',
        type: 'datetime',
        required: false,
        helpText: 'When is this reminder due? (ISO 8601 format)'
      },
      {
        key: 'due_in_hours',
        label: 'Due in Hours',
        type: 'integer',
        required: false,
        helpText: 'Alternative: Set reminder due in X hours from now'
      },
      {
        key: 'due_in_days',
        label: 'Due in Days',
        type: 'integer',
        required: false,
        helpText: 'Alternative: Set reminder due in X days from now'
      },
      {
        key: 'create_client_if_missing',
        label: 'Create Client if Missing',
        type: 'boolean',
        required: false,
        default: 'false',
        helpText: 'If client email doesn\'t exist, create a new client'
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
      id: 123,
      due_at: '2024-01-16T14:00:00Z',
      note: 'Follow up on project proposal',
      status: 'pending',
      created_at: '2024-01-15T10:00:00Z',
      due_in_hours: 28,
      due_in_days: 1,
      due_in_words: 'in 1 day',
      client: {
        id: 1,
        email: 'john@example.com',
        name: 'John Doe',
        company: 'Acme Corp',
        status: 'active',
        was_created: false
      },
      was_created: true
    },
  },
};