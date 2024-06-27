<?php
use Propel\Generator\Manager\MigrationManager;

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version 1719313233.
 * Generated on 2024-06-25 11:00:33 by reneklatt */
class PropelMigration_1719313233{
    /**
     * @var string
     */
    public $comment = '';

    /**
     * @param \Propel\Generator\Manager\MigrationManager $manager
     *
     * @return null|false|void
     */
    public function preUp(MigrationManager $manager)
    {
        // add the pre-migration code here
    }

    /**
     * @param \Propel\Generator\Manager\MigrationManager $manager
     *
     * @return null|false|void
     */
    public function postUp(MigrationManager $manager)
    {
        // add the post-migration code here
    }

    /**
     * @param \Propel\Generator\Manager\MigrationManager $manager
     *
     * @return null|false|void
     */
    public function preDown(MigrationManager $manager)
    {
        // add the pre-migration code here
    }

    /**
     * @param \Propel\Generator\Manager\MigrationManager $manager
     *
     * @return null|false|void
     */
    public function postDown(MigrationManager $manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL(): array
    {
        $connection_zed = <<< 'EOT'

PRAGMA foreign_keys = OFF;

CREATE TEMPORARY TABLE [spy_locale__temp__667aa3517f31f] AS SELECT [id_locale],[locale_name],[is_active] FROM [spy_locale];
DROP TABLE [spy_locale];

CREATE TABLE [spy_locale]
(
    [id_locale] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [locale_name] VARCHAR(5) NOT NULL,
    [is_active] INTEGER DEFAULT 1 NOT NULL,
    UNIQUE ([locale_name]),
    UNIQUE ([id_locale])
);

CREATE INDEX [spy_locale-index-locale_name] ON [spy_locale] ([locale_name]);

INSERT INTO [spy_locale] (id_locale, locale_name, is_active) SELECT id_locale, locale_name, is_active FROM [spy_locale__temp__667aa3517f31f];
DROP TABLE [spy_locale__temp__667aa3517f31f];

CREATE TEMPORARY TABLE [spy_locale_store__temp__667aa3517f376] AS SELECT [id_locale_store],[fk_locale],[fk_store] FROM [spy_locale_store];
DROP TABLE [spy_locale_store];

CREATE TABLE [spy_locale_store]
(
    [id_locale_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER NOT NULL,
    [fk_store] INTEGER NOT NULL,
    UNIQUE ([fk_locale],[fk_store]),
    UNIQUE ([id_locale_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store])
);

CREATE INDEX [index-spy_locale_store-fk_locale] ON [spy_locale_store] ([fk_locale]);

CREATE INDEX [index-spy_locale_store-fk_store] ON [spy_locale_store] ([fk_store]);

INSERT INTO [spy_locale_store] (id_locale_store, fk_locale, fk_store) SELECT id_locale_store, fk_locale, fk_store FROM [spy_locale_store__temp__667aa3517f376];
DROP TABLE [spy_locale_store__temp__667aa3517f376];

CREATE TEMPORARY TABLE [spy_payment__temp__667aa3517f3e6] AS SELECT [id_payment],[order_reference],[transaction_id],[tenant_identifier],[quote],[status],[redirect_success_url],[redirect_cancel_url],[created_at],[updated_at] FROM [spy_payment];
DROP TABLE [spy_payment];

CREATE TABLE [spy_payment]
(
    [id_payment] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [order_reference] CHAR(36),
    [transaction_id] CHAR(36),
    [tenant_identifier] CHAR(60),
    [quote] MEDIUMTEXT,
    [status] CHAR(64),
    [redirect_success_url] MEDIUMTEXT,
    [redirect_cancel_url] MEDIUMTEXT,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([order_reference],[tenant_identifier]),
    UNIQUE ([transaction_id]),
    UNIQUE ([id_payment])
);

INSERT INTO [spy_payment] (id_payment, order_reference, transaction_id, tenant_identifier, quote, status, redirect_success_url, redirect_cancel_url, created_at, updated_at) SELECT id_payment, order_reference, transaction_id, tenant_identifier, quote, status, redirect_success_url, redirect_cancel_url, created_at, updated_at FROM [spy_payment__temp__667aa3517f3e6];
DROP TABLE [spy_payment__temp__667aa3517f3e6];

CREATE TEMPORARY TABLE [spy_payment_refund__temp__667aa3517f468] AS SELECT [id_payment_refund],[transaction_id],[refund_id],[status],[amount],[currency_code],[order_item_ids],[created_at],[updated_at] FROM [spy_payment_refund];
DROP TABLE [spy_payment_refund];

CREATE TABLE [spy_payment_refund]
(
    [id_payment_refund] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [transaction_id] CHAR(36),
    [refund_id] CHAR(36),
    [status] VARCHAR(255) NOT NULL,
    [amount] INTEGER NOT NULL,
    [currency_code] VARCHAR(10) NOT NULL,
    [order_item_ids] MEDIUMTEXT,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([id_payment_refund]),
    FOREIGN KEY ([transaction_id]) REFERENCES [spy_payment] ([transaction_id])
        ON DELETE CASCADE
);

CREATE INDEX [spy_payment_refund-search_index] ON [spy_payment_refund] ([transaction_id],[status],[order_item_ids]);

INSERT INTO [spy_payment_refund] (id_payment_refund, transaction_id, refund_id, status, amount, currency_code, order_item_ids, created_at, updated_at) SELECT id_payment_refund, transaction_id, refund_id, status, amount, currency_code, order_item_ids, created_at, updated_at FROM [spy_payment_refund__temp__667aa3517f468];
DROP TABLE [spy_payment_refund__temp__667aa3517f468];

CREATE TEMPORARY TABLE [spy_queue_process__temp__667aa3517f4ed] AS SELECT [id_queue_process],[server_id],[process_pid],[worker_pid],[queue_name],[created_at],[updated_at] FROM [spy_queue_process];
DROP TABLE [spy_queue_process];

CREATE TABLE [spy_queue_process]
(
    [id_queue_process] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [server_id] VARCHAR(255) NOT NULL,
    [process_pid] INTEGER NOT NULL,
    [worker_pid] INTEGER NOT NULL,
    [queue_name] VARCHAR(255) NOT NULL,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([server_id],[process_pid],[queue_name]),
    UNIQUE ([id_queue_process])
);

CREATE INDEX [spy_queue_process-index-key] ON [spy_queue_process] ([server_id],[queue_name]);

INSERT INTO [spy_queue_process] (id_queue_process, server_id, process_pid, worker_pid, queue_name, created_at, updated_at) SELECT id_queue_process, server_id, process_pid, worker_pid, queue_name, created_at, updated_at FROM [spy_queue_process__temp__667aa3517f4ed];
DROP TABLE [spy_queue_process__temp__667aa3517f4ed];

CREATE TEMPORARY TABLE [spy_store__temp__667aa3517f54f] AS SELECT [id_store],[fk_locale],[name] FROM [spy_store];
DROP TABLE [spy_store];

CREATE TABLE [spy_store]
(
    [id_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER,
    [name] VARCHAR(255),
    UNIQUE ([id_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
);

CREATE INDEX [index-spy_store-fk_locale] ON [spy_store] ([fk_locale]);

INSERT INTO [spy_store] (id_store, fk_locale, name) SELECT id_store, fk_locale, name FROM [spy_store__temp__667aa3517f54f];
DROP TABLE [spy_store__temp__667aa3517f54f];

PRAGMA foreign_keys = ON;
EOT;

        return [
            'zed' => $connection_zed,
        ];
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL(): array
    {
        $connection_zed = <<< 'EOT'

PRAGMA foreign_keys = OFF;

CREATE TEMPORARY TABLE [spy_locale__temp__667aa3517f5d6] AS SELECT [id_locale],[locale_name],[is_active] FROM [spy_locale];
DROP TABLE [spy_locale];

CREATE TABLE [spy_locale]
(
    [id_locale] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [locale_name] VARCHAR(5) NOT NULL,
    [is_active] INTEGER DEFAULT 1 NOT NULL,
    UNIQUE ([locale_name]),
    UNIQUE ([id_locale])
);

CREATE INDEX [spy_locale-index-locale_name] ON [spy_locale] ([locale_name]);

INSERT INTO [spy_locale] (id_locale, locale_name, is_active) SELECT id_locale, locale_name, is_active FROM [spy_locale__temp__667aa3517f5d6];
DROP TABLE [spy_locale__temp__667aa3517f5d6];

CREATE TEMPORARY TABLE [spy_locale_store__temp__667aa3517f61a] AS SELECT [id_locale_store],[fk_locale],[fk_store] FROM [spy_locale_store];
DROP TABLE [spy_locale_store];

CREATE TABLE [spy_locale_store]
(
    [id_locale_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER NOT NULL,
    [fk_store] INTEGER NOT NULL,
    UNIQUE ([fk_locale],[fk_store]),
    UNIQUE ([id_locale_store]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
);

CREATE INDEX [index-spy_locale_store-fk_store] ON [spy_locale_store] ([fk_store]);

CREATE INDEX [index-spy_locale_store-fk_locale] ON [spy_locale_store] ([fk_locale]);

INSERT INTO [spy_locale_store] (id_locale_store, fk_locale, fk_store) SELECT id_locale_store, fk_locale, fk_store FROM [spy_locale_store__temp__667aa3517f61a];
DROP TABLE [spy_locale_store__temp__667aa3517f61a];

CREATE TEMPORARY TABLE [spy_payment__temp__667aa3517f6a7] AS SELECT [id_payment],[order_reference],[transaction_id],[tenant_identifier],[quote],[status],[redirect_success_url],[redirect_cancel_url],[created_at],[updated_at] FROM [spy_payment];
DROP TABLE [spy_payment];

CREATE TABLE [spy_payment]
(
    [id_payment] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [order_reference] CHAR(36),
    [transaction_id] CHAR(36),
    [tenant_identifier] CHAR(60),
    [quote] MEDIUMTEXT,
    [status] CHAR(64),
    [redirect_success_url] MEDIUMTEXT,
    [redirect_cancel_url] MEDIUMTEXT,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([transaction_id]),
    UNIQUE ([order_reference],[tenant_identifier]),
    UNIQUE ([id_payment])
);

INSERT INTO [spy_payment] (id_payment, order_reference, transaction_id, tenant_identifier, quote, status, redirect_success_url, redirect_cancel_url, created_at, updated_at) SELECT id_payment, order_reference, transaction_id, tenant_identifier, quote, status, redirect_success_url, redirect_cancel_url, created_at, updated_at FROM [spy_payment__temp__667aa3517f6a7];
DROP TABLE [spy_payment__temp__667aa3517f6a7];

CREATE TEMPORARY TABLE [spy_payment_refund__temp__667aa3517f729] AS SELECT [id_payment_refund],[transaction_id],[refund_id],[status],[amount],[currency_code],[order_item_ids],[created_at],[updated_at] FROM [spy_payment_refund];
DROP TABLE [spy_payment_refund];

CREATE TABLE [spy_payment_refund]
(
    [id_payment_refund] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [transaction_id] CHAR(36),
    [refund_id] CHAR(36),
    [status] VARCHAR(255) NOT NULL,
    [amount] INTEGER NOT NULL,
    [currency_code] VARCHAR(10) NOT NULL,
    [order_item_ids] MEDIUMTEXT,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([id_payment_refund]),
    FOREIGN KEY ([transaction_id]) REFERENCES [spy_payment] ([transaction_id])
        ON DELETE CASCADE
);

CREATE INDEX [spy_payment_refund-search_index] ON [spy_payment_refund] ([transaction_id],[status],[order_item_ids]);

INSERT INTO [spy_payment_refund] (id_payment_refund, transaction_id, refund_id, status, amount, currency_code, order_item_ids, created_at, updated_at) SELECT id_payment_refund, transaction_id, refund_id, status, amount, currency_code, order_item_ids, created_at, updated_at FROM [spy_payment_refund__temp__667aa3517f729];
DROP TABLE [spy_payment_refund__temp__667aa3517f729];

CREATE TEMPORARY TABLE [spy_queue_process__temp__667aa3517f7ab] AS SELECT [id_queue_process],[server_id],[process_pid],[worker_pid],[queue_name],[created_at],[updated_at] FROM [spy_queue_process];
DROP TABLE [spy_queue_process];

CREATE TABLE [spy_queue_process]
(
    [id_queue_process] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [server_id] VARCHAR(255) NOT NULL,
    [process_pid] INTEGER NOT NULL,
    [worker_pid] INTEGER NOT NULL,
    [queue_name] VARCHAR(255) NOT NULL,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([server_id],[process_pid],[queue_name]),
    UNIQUE ([id_queue_process])
);

CREATE INDEX [spy_queue_process-index-key] ON [spy_queue_process] ([server_id],[queue_name]);

INSERT INTO [spy_queue_process] (id_queue_process, server_id, process_pid, worker_pid, queue_name, created_at, updated_at) SELECT id_queue_process, server_id, process_pid, worker_pid, queue_name, created_at, updated_at FROM [spy_queue_process__temp__667aa3517f7ab];
DROP TABLE [spy_queue_process__temp__667aa3517f7ab];

CREATE TEMPORARY TABLE [spy_store__temp__667aa3517f80b] AS SELECT [id_store],[fk_locale],[name] FROM [spy_store];
DROP TABLE [spy_store];

CREATE TABLE [spy_store]
(
    [id_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER,
    [name] VARCHAR(255),
    UNIQUE ([id_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
);

CREATE INDEX [index-spy_store-fk_locale] ON [spy_store] ([fk_locale]);

INSERT INTO [spy_store] (id_store, fk_locale, name) SELECT id_store, fk_locale, name FROM [spy_store__temp__667aa3517f80b];
DROP TABLE [spy_store__temp__667aa3517f80b];

PRAGMA foreign_keys = ON;
EOT;

        return [
            'zed' => $connection_zed,
        ];
    }

}
