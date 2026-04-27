
create table if not exists countries (
	name	text,
	alpha_2	text,
	country_code integer primary key
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

create table if not exists riders (
  rider_id integer primary key,
  name varchar(255),
  team_id integer,
  active boolean,
  created_at datetime not null default current_timestamp,
  foreign key (team_id) references teams(team_id)
);

create table if not exists teams (
	team_id	integer primary key,
	team_name	text not null,
	short_team_name	text,
	manufacturer	text
);

create table if not exists  events (
event_id integer primary key,
start_date date,
name varchar(255),
circuit varchar(255),
country_code integer,
bids_open integer not null default 0,
created_at datetime not null default current_timestamp,
foreign key (country_code) references countries(country_code)
);

create table if not exists  results (
    event_id integer,
    rider_id integer,
    position integer,
    created_at datetime not null default current_timestamp,
    foreign key (event_id) references events(event_id),
    foreign key (rider_id) references riders(rider_id)
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
