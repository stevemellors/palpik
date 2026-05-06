<?php
declare(strict_types=1);

/**
 * HTML template used for both customer + owner emails
 */
function build_order_html(array $order, array $items, string $headline, ?string $adminLink=null): string {
    $rows = '';
    foreach ($items as $it) {
        $rows .= '<tr>'.
                 '<td style="padding:8px;border:1px solid #e5e7eb;">'.htmlspecialchars((string)$it['name']).'</td>'.
                 '<td style="padding:8px;border:1px solid #e5e7eb;text-align:center;">'.(int)$it['qty'].'</td>'.
                 '<td style="padding:8px;border:1px solid #e5e7eb;text-align:right;">$'.number_format((float)$it['price'],2).'</td>'.
                 '</tr>';
    }

    $addr = htmlspecialchars((string)$order['address']).'<br>'.
            htmlspecialchars((string)$order['city']).', '.
            htmlspecialchars((string)$order['state']).' '.
            htmlspecialchars((string)$order['zip']);

    $adminRow = $adminLink
        ? '<p style="margin-top:10px;"><a href="'.htmlspecialchars($adminLink).'" '.
          'style="display:inline-block;padding:10px 12px;border-radius:10px;border:1px solid #e5e7eb;' .
          'text-decoration:none;color:#0a0a0a;">Open in Admin</a></p>'
        : '';

    return '<!doctype html><html><body style="font-family:Arial,Helvetica,sans-serif;background:#0a0f1e;color:#e5e7eb;padding:16px;">'.
           '<div style="max-width:640px;margin:0 auto;background:#0f162d;border:1px solid #18213d;border-radius:14px;overflow:hidden;">'.
             '<div style="padding:16px 18px;border-bottom:1px solid #18213d;">'.
               '<h2 style="margin:0 0 6px;">'.htmlspecialchars($headline).'</h2>'.
               '<div style="color:#9aa4b2;">Order #'.(int)$order['id'].' · '.htmlspecialchars((string)$order['created_at']).'</div>'.
             '</div>'.

             '<div style="padding:16px 18px;">'.
               '<h3 style="margin:0 0 8px;">Customer</h3>'.
               '<p style="margin:0 0 10px;">'.
                 '<strong>'.htmlspecialchars((string)$order['name']).'</strong><br>'.
                 htmlspecialchars((string)$order['email']).'<br>'.
                 $addr.
               '</p>'.

               '<h3 style="margin:14px 0 8px;">Items</h3>'.
               '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;background:#0b1325;border:1px solid #18213d;">'.
                 '<thead><tr>'.
                   '<th style="text-align:left;padding:8px;border:1px solid #18213d;">Item</th>'.
                   '<th style="text-align:center;padding:8px;border:1px solid #18213d;">Qty</th>'.
                   '<th style="text-align:right;padding:8px;border:1px solid #18213d;">Price</th>'.
                 '</tr></thead>'.
                 '<tbody>'.$rows.'</tbody>'.
               '</table>'.

               '<div style="margin-top:12px;line-height:1.6;">'.
                 '<div><strong>Subtotal:</strong> $'.number_format((float)$order['subtotal'],2).'</div>'.
                 '<div><strong>Shipping:</strong> $'.number_format((float)$order['shipping'],2).'</div>'.
                 '<div><strong>Tax:</strong> $'.number_format((float)$order['tax'],2).'</div>'.
                 '<div><strong>Total:</strong> $'.number_format((float)$order['total'],2).'</div>'.
                 '<div style="color:#9aa4b2;">Payment: '.htmlspecialchars((string)$order['payment_method']).'</div>'.
               '</div>'.
               $adminRow.
             '</div>'.
           '</div>'.
           '<p style="color:#9aa4b2;text-align:center;margin:12px 0 0;">— Pallet Picks</p>'.
           '</body></html>';
}

/**
 * Customer receipt (HTML) — uses server mail() relay
 */
function send_order_email(string $to, array $order, array $items, string $from='noreply@palletpiks.com'): bool {
    $subject = 'Your order #'.$order['id'].' receipt';
    $body    = build_order_html($order, $items, 'Thanks for your order!');
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: '.$from,
        'Reply-To: '.$from,
    ];
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Owner copy/slip (HTML) — goes to palletpiks@gmail.com
 */
function send_order_admin(string $ownerEmail, array $order, array $items, string $from='noreply@palletpiks.com', ?string $adminLink=null): bool {
    $subject = 'New order #'.$order['id'].' — $'.number_format((float)$order['total'],2);
    $body    = build_order_html($order, $items, 'New order received', $adminLink);
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: '.$from,
        'Reply-To: '.$from,
    ];
    return @mail($ownerEmail, $subject, $body, implode("\r\n", $headers));
}
