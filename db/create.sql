create table if not exists users (
    user_id integer primary key,
    username text not null,
    password text not null,
    email text not null,
    admin integer not null default 0,
    created_at datetime not null default current_timestamp
);