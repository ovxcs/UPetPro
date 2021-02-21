CREATE TABLE oauth(
    id INT AUTO_INCREMENT NOT NULL UNIQUE,
    mwt char(64) NOT NULL UNIQUE,
    prov char(16) NOT NULL,
    name char(64),
    eid char(64) NOT NULL,
    CONSTRAINT Prov_eid UNIQUE (prov, eid),
    email char(64),
    tel char(96),
    pict char(255)
);

CREATE TABLE oa_exchanges(
    id INT AUTO_INCREMENT NOT NULL UNIQUE,
    mwt varchar(64) UNIQUE NOT NULL,
    prov varchar(16) NOT NULL,
    code varchar(1024),
    access_token varchar(2048),
    verifier varchar(256),
    token_expires_at int
);
