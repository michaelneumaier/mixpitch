const perform = async (z, bundle) => {
  const response = await z.request({
    url: 'https://mixpitch.com/api/zapier/searches/clients',
    method: 'GET',
    params: {
      limit: 50
    }
  });

  // Format for dynamic dropdown
  return response.data.data.map((client) => ({
    id: client.id,
    name: client.name || client.email,
    // Include extra info in the label for clarity
    label: client.name ? `${client.name} (${client.email})` : client.email
  }));
};

module.exports = {
  key: 'clientList',
  noun: 'Client',
  display: {
    label: 'Client List',
    description: 'A list of clients for dropdown selection.',
    hidden: true, // Hide from the UI as it's only for dynamic dropdowns
  },
  operation: {
    perform: perform,
  },
};