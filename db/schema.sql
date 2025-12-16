CREATE TABLE user (
  user_id integer PRIMARY KEY,
  username varchar(255),
  password varchar(255),
  email varchar(255),
  balance integer
);

CREATE TABLE riders (
  rider_id integer PRIMARY KEY,
  name varchar(255),
  team varchar(255),
  link varchar(255),
  active boolean
);

CREATE TABLE events (
  event_id integer PRIMARY KEY,
  start_date date,
  name varchar(255),
  circuit varchar(255),
  flag varchar(255),
  link varchar(255)
);

CREATE TABLE bids (
  bid_id integer PRIMARY KEY,
  user_id integer,
  rider_id integer,
  event_id integer,
  bid_number integer,
  amount integer
);
