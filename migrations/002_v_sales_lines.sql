-- View used by the Reports page (admin/reports.php) and CSV export.
-- Run once against pallet_store.
CREATE OR REPLACE VIEW v_sales_lines AS
SELECT
    o.created_at      AS ordered_at,
    oi.order_id,
    oi.product_id,
    oi.name           AS product_name,
    oi.qty,
    oi.price          AS unit_price,
    oi.qty * oi.price AS line_total
FROM order_items oi
JOIN orders o ON o.id = oi.order_id;
