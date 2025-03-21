CREATE TABLE members (
  member_id integer PRIMARY KEY,
  username varchar(255),
  password varchar(255),
  email varchar(255),
  balance integer
);

CREATE TABLE riders (
  rider_id integer PRIMARY KEY,
  name varchar(255),
  active boolean
);

CREATE TABLE events (
  event_id integer PRIMARY KEY,
  name varchar(255),
  start_date date
);

CREATE TABLE bids (
  bid_id integer PRIMARY KEY,
  member_id integer,
  rider_id integer,
  event_id integer,
  amount integer
);
