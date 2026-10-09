insert into bids (
    user_id,
    rider_id,
    event_id,
    bid_number,
    amount
) values

-- thailand
-- Brett
(1, 22, 1, 1, 8),
(1, 12, 1, 2, 6),
(1, 14, 1, 3, 4),

-- Alice
(3, 22, 1, 1, 8),
(3, 18, 1, 2, 7),
(3, 6,  1, 3, 3),

-- Bob
(4, 22, 1, 1, 5),
(4, 18, 1, 2, 7),
(4, 12, 1, 3, 6),

-- Charlie
(5, 22, 1, 1, 8),
(5, 17, 1, 2, 5),
(5, 14, 1, 3, 2),

-- Daisy
(6, 18, 1, 1, 4),
(6, 17, 1, 2, 9),
(6, 6,  1, 3, 1),

-- brazil
-- Brett
(1, 22, 2, 1, 8),
(1, 12, 2, 2, 6),
(1, 14, 2, 3, 4),

-- Alice
(3, 22, 2, 1, 8),
(3, 18, 2, 2, 7),
(3, 6,  2, 3, 3),

-- Bob
(4, 22, 2, 1, 5),
(4, 18, 2, 2, 7),
(4, 12, 2, 3, 6),

-- Charlie
(5, 22, 2, 1, 8),
(5, 17, 2, 2, 5),
(5, 14, 2, 3, 2),

-- Daisy
(6, 18, 2, 1, 4),
(6, 17, 2, 2, 9),
(6, 6,  2, 3, 1);

-- Proportional bid reduction test scenarios.
-- Set bids to 10, 6, 4 for four players in Thailand.

update bids
set amount = case bid_number
    when 1 then 10
    when 2 then 6
    when 3 then 4
end
where event_id = 1
and user_id in (1, 3, 4, 5);

-- Replace rider 22 with rider 18 in Thailand.
-- Rider 18 has a seeded race result, allowing payout settlement.

update bids
set rider_id = 21
where event_id = 1
and rider_id = 22;

-- Give each player a different available balance.

update users
set balance = case user_id
    when 3 then 10
    when 4 then 7
    when 5 then 0
end
where user_id in (3, 4, 5);

-- Record the test balance adjustments in the statement.

insert into balance_transactions (
    user_id,
    transaction_type,
    amount
) values
(3, 'admin_adjustment', -10),
(4, 'admin_adjustment', -13),
(5, 'admin_adjustment', -20);
