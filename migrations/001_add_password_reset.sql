-- Run once against pallet_store to enable the forgot-password flow.
ALTER TABLE users
    ADD COLUMN reset_token     VARCHAR(64) NULL DEFAULT NULL,
    ADD COLUMN reset_token_exp DATETIME    NULL DEFAULT NULL;
