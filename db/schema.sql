
create table if not exists country (
	name	text,
	alpha_2	text,
	country_code integer
);

create table if not exists users (
    user_id integer primary key,
    username text not null,
    password text not null,
    email text not null,
    admin integer not null default 0,
    balance integer not null default 20,
    created_at datetime not null default current_timestamp
);

create table if not exists country (
	name	text,
	alpha-2	text,
	country-code integer
);

create table if not exists  riders (
  rider_id integer primary key,
  name varchar(255),
  team varchar(255),
  link varchar(255),
  active boolean,
  created_at datetime not null default current_timestamp
);

create table if not exists  events (
  event_id integer primary key,
  start_date date,
  name varchar(255),
  circuit varchar(255),
  country_code integer,
  link varchar(255),
  bids_open integer not null default 0,
  created_at datetime not null default current_timestamp,
  foreign key (country_code) references country(country_code)
);

create table if not exists bids (
  bid_id integer primary key,
  user_id integer,
  rider_id integer,
  event_id integer,
  bid_number integer,
  amount integer,
  created_at datetime not null default current_timestamp,
  foreign key (user_id) references users(user_id),
  foreign key (rider_id) references riders(rider_id),
  foreign key (event_id) references events(event_id)
);
