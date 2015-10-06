-- $Id: log.sql,v 1.1 2008/12/31 02:20:17 mark Exp $

CREATE TABLE log_table (
    id          INT NOT NULL,
    logtime     TIMESTAMP NOT NULL,
    ident       CHAR(16) NOT NULL,
    priority    INT NOT NULL,
    message     VARCHAR(200),
    PRIMARY KEY (id)
);
