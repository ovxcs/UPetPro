CREATE TABLE users(
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL UNIQUE,
    alias BIGINT UNSIGNED,
    ts BIGINT UNSIGNED,
    random BIGINT UNSIGNED,
    CONSTRAINT ts_rand_id UNIQUE (ts, random),
    name char(64),
    eid char(128) UNIQUE,
    oa_db_id BIGINT UNSIGNED,
    email char(64) NOT NULL UNIQUE,
    password char(64) NOT NULL,
    salt char(32) NOT NULL,
    verification INT UNSIGNED,
    tel char(32) UNIQUE,
    status INT UNSIGNED,
    flags BIGINT UNSIGNED DEFAULT 0,
    lang char(16),
    pwrc INT UNSIGNED
);

CREATE TABLE users_extra(
    id BIGINT NOT NULL UNIQUE,
    groups varchar(128) DEFAULT ',',
    langs varchar(32) DEFAULT ',',
    contacts varchar(128),
    pict varchar(256),
    info varchar(128),
    address varchar(128),
    flags INT UNSIGNED DEFAULT 0
);

CREATE TABLE groups(
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL UNIQUE,
    name char(16) UNIQUE,
    flags BIGINT UNSIGNED DEFAULT 0,
);

CREATE TABLE user_groups(
    uid BIGINT,
    gid BIGINT,
    flags BIGINT UNSIGNED DEFAULT 0, 
);

CREATE TABLE attempts(
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL UNIQUE,
    kind INT UNSIGNED,
    target char(64),
    target_id UNSIGNED BIGINT,
    address char(45),
    source_id UNSIGNED BIGINT,
    ts BIGINT UNSIGNED,
    number INT UNSIGNED,
    status INT UNSIGNED,
    flags INT UNSIGNED DEFAULT 0,
    code INT UNSIGNED,
    mwt char(64)
);

CREATE TABLE sessions(
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL UNIQUE,
    usr_id BIGINT UNSIGNED,
    ts BIGINT UNSIGNED,
    random BIGINT UNSIGNED,
    CONSTRAINT ts_rand_id UNIQUE (ts, random),
    addr char(64),
    status INT UNSIGNED
);
