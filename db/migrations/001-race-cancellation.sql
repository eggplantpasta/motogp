pragma foreign_keys = off;

begin transaction;

alter table events
add column cancelled_at datetime default null;

create table balance_transactions_new (
    transaction_id integer primary key,
    user_id integer not null,
    event_id integer,
    bid_id integer,
    transaction_type text not null,
    amount integer not null,
    created_at datetime not null default current_timestamp,

    foreign key (user_id)
        references users(user_id)
        on delete cascade,

    foreign key (event_id)
        references events(event_id),

    foreign key (bid_id)
        references bids(bid_id),

    check (
        transaction_type in (
            'opening_balance',
            'winning_bid',
            'bid_refund',
            'payout',
            'admin_adjustment'
        )
    ),

    check (
        (transaction_type = 'opening_balance' and amount >= 0)
        or
        (transaction_type = 'winning_bid' and amount <= 0)
        or
        (transaction_type = 'bid_refund' and amount >= 0)
        or
        (transaction_type = 'payout' and amount >= 0)
        or
        (transaction_type = 'admin_adjustment')
    )
);

insert into balance_transactions_new (
    transaction_id,
    user_id,
    event_id,
    bid_id,
    transaction_type,
    amount,
    created_at
)
select
    transaction_id,
    user_id,
    event_id,
    bid_id,
    transaction_type,
    amount,
    created_at
from balance_transactions;

drop table balance_transactions;

alter table balance_transactions_new
rename to balance_transactions;

create unique index idx_balance_transactions_opening
on balance_transactions (user_id)
where transaction_type = 'opening_balance';

commit;

pragma foreign_keys = on;
