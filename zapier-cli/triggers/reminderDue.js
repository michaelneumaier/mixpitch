const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/triggers/reminders/due',
    method: 'GET',
    params: {
      since: bundle.meta.timestamp || new Date(Date.now() - 15 * 60 * 1000).toISOString()
    }
  });

  return response.data.data || [];
};

module.exports = {
  key: 'reminderDue',
  noun: 'Reminder',
  display: {
    label: 'Client Reminder Due',
    description: 'Triggers when a client reminder becomes due or overdue.',
  },
  operation: {
    perform: perform,
    sample: {
      id: 42,
      due_at: '2024-01-15T14:00:00Z',
      note: 'Follow up on project proposal',
      status: 'pending',
      is_overdue: true,
      hours_overdue: 2,
      due_in_words: '2 hours ago',
      created_at: '2024-01-10T10:00:00Z',
      client: {
        id: 1,
        email: 'john@example.com',
        name: 'John Doe',
        company: 'Acme Corp',
        phone: '+1-555-0123',
        status: 'active',
        tags: ['vip'],
        total_spent: 5000.00,
        total_projects: 3,
        last_contacted_at: '2024-01-10T10:30:00Z'
      },
      latest_project: {
        id: 123,
        name: 'Album Mix',
        status: 'in_progress',
        created_at: '2024-01-10T09:00:00Z'
      }
    },
  },
};