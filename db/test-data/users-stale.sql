insert into users (
    username,
    password,
    email,
    admin,
    approved_at,
    balance
) values
('staleuser1', 'passwordhash1', 'staleuser1@example.com', 0, NULL, 0),
('staleuser2', 'passwordhash2', 'staleuser2@example.com', 0, NULL, 0),
('staleuser3', 'passwordhash3', 'staleuser3@example.com', 0, NULL, 0);

update users
set created_at =  datetime('now', '-8 days')
where username in ('staleuser1', 'staleuser2', 'staleuser3');
