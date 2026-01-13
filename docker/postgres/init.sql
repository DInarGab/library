CREATE TABLE users
(
    id                    INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    telegram_id           BIGINT      NOT NULL UNIQUE,
    username              VARCHAR(255),
    first_name            VARCHAR(255),
    last_name             VARCHAR(255),
    role                  VARCHAR(20) NOT NULL     DEFAULT 'user',
    created_at            TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE books
(
    id          INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    title       VARCHAR(500) NOT NULL,
    author      VARCHAR(255) NOT NULL,
    isbn        VARCHAR(20),
    description TEXT,
    cover_url   VARCHAR(1000),
    created_at  TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE book_copies
(
    id               INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    book_id          INTEGER            NOT NULL REFERENCES books (id) ON DELETE CASCADE,
    inventory_number VARCHAR(50) UNIQUE NOT NULL,
    status           VARCHAR(20)        NOT NULL DEFAULT 'available',
    condition        VARCHAR(20)        NOT NULL DEFAULT 'good',
    created_at       TIMESTAMP WITH TIME ZONE    DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE lendings
(
    id            INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    book_copy_id  INTEGER                  NOT NULL REFERENCES book_copies (id),
    user_id       INTEGER                  NOT NULL REFERENCES users (id),
    issued_at     TIMESTAMP WITH TIME ZONE          DEFAULT CURRENT_TIMESTAMP,
    due_date      TIMESTAMP WITH TIME ZONE NOT NULL,
    returned_at   TIMESTAMP WITH TIME ZONE,
    status        VARCHAR(20)              NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP WITH TIME ZONE          DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE book_suggestions
(
    id            INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    user_id       INTEGER     NOT NULL REFERENCES users (id),
    title         VARCHAR(500),
    author        VARCHAR(255),
    source_url    VARCHAR(1000),
    status        VARCHAR(20) NOT NULL     DEFAULT 'pending',
    admin_comment TEXT,
    created_at    TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
