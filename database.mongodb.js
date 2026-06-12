use('event_ticketing');

db.createCollection('tickets');

db.tickets.createIndex(
    {
        ticketToken: 1,
    },
    {
        unique: true,
        name: 'uniq_tickets_ticketToken',
    },
);

db.tickets.createIndex(
    {
        isCheckedIn: 1,
        createdAt: -1,
    },
    {
        name: 'idx_tickets_checkin_state_createdAt',
    },
);
