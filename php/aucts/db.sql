CREATE TABLE goods(
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL UNIQUE,
    name char(128),
    model char(128),
    location char(128),
    sn char(128),
    qty int UNSIGNED,
    cts int UNSIGNED,
    fabrication_h int UNSIGNED,
    valabilty_h int UNSIGNED,
    flags int UNSIGNED,
    owner_id BIGINT UNSIGNED,
    status char(32),
    price_s char(32),
    price_i int UNSIGNED,
    valut char(8),
    my_cents int UNSIGNED,
    lang char(8)
);

CREATE TABLE goods_pics(
    goodId BIGINT UNSIGNED NOT NULL UNIQUE,
    default_pic_ix int UNSIGNED,
    default_pic varchar(256),
    pictures TEXT
);

CREATE TABLE goods_translations(
    goodId BIGINT UNSIGNED NOT NULL,
    lang char(16),
    name varchar(128),
    description TEXT
);

CREATE TABLE reviews_and_comments(
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL UNIQUE,
    usrId BIGINT UNSIGNED NOT NULL,
    gKind SMALLINT UNSIGNED NOT NULL,
    gId BIGINT UNSIGNED NOT NULL,
    note UNSIGNED TINYINT,
    edTs INT UNSIGNED,
    flags INT UNSIGNED,
    content TEXT,
    likes TEXT
);

CREATE TABLE aucts(
    id INT UNSIGNED AUTO_INCREMENT NOT NULL UNIQUE,
    goodId BIGINT UNSIGNED NOT NULL,
    status char(32),
    flags int UNSIGNED,
    placed_ts int UNSIGNED,
    start_ts int UNSIGNED,
    end_ts int UNSIGNED,
    crt_bid int UNSIGNED,
    start_val int UNSIGNED,
    bid_step int UNSIGNED,
    valut char(8),
    bids int UNSIGNED
);


CREATE TABLE aucts_watchlists(
    user_id BIGINT UNSIGNED,
    list varchar(1028)
);