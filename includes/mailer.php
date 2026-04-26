<?php
// ============================================================
//  ROYALE VISTA — PHPMailer Helper
//  Emails: Welcome | Login | Booking Confirmation | Cancellation
//  All include full invoice + PDF download link
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

require_once __DIR__ . '/PHPMailer/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/PHPMailer-master/src/SMTP.php';

// ── SMTP CONFIG — update before going live ──────────────────
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'ayaankhalifa4432@gmail.com');
define('MAIL_PASSWORD', 'rknm lqiw txqq mdhr');
define('MAIL_FROM_EMAIL', 'your_email@gmail.com');
define('MAIL_FROM_NAME', 'Royale Vista Hotel');
define('MAIL_ENCRYPTION', 'tls');
// ────────────────────────────────────────────────────────────

function sendRVMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = (MAIL_PORT === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</tr>'], "\n", $htmlBody));
        $mail->send();
        return true;
    } catch (MailerException $e) {
        error_log('[RV Mailer] ' . $e->getMessage());
        return false;
    } catch (\Exception $e) {
        error_log('[RV Mailer] ' . $e->getMessage());
        return false;
    }
}

// ── WELCOME EMAIL ────────────────────────────────────────────
function sendWelcomeEmail(string $toEmail, string $toName): bool
{
    $base = defined('BASE') ? BASE : '';
    $body = '
        <div style="background:linear-gradient(135deg,#0d2e1a,#0a1f12);border:1px solid rgba(92,184,92,0.3);border-radius:12px;padding:20px 24px;margin-bottom:24px;text-align:center">
            <div style="font-size:36px;margin-bottom:8px">🎉</div>
            <div style="font-size:18px;color:#5cb85c;font-weight:700;margin-bottom:4px">Account Created!</div>
            <div style="font-size:13px;color:#aaa">Welcome to the Royale Vista family.</div>
        </div>
        <table style="width:100%;border-collapse:collapse;margin-bottom:24px">
            <tr><td colspan="2" style="padding:10px 14px;background:#1a1a2e;border-radius:8px 8px 0 0;color:#d4af37;font-weight:600;font-size:12px;letter-spacing:1px;text-transform:uppercase">Your Account</td></tr>
            <tr><td style="padding:10px 14px;background:#12122a;color:#aaa;font-size:13px;width:35%">Name</td><td style="padding:10px 14px;background:#12122a;color:#e0e0e0;font-size:14px"><strong>' . htmlspecialchars($toName) . '</strong></td></tr>
            <tr><td style="padding:10px 14px;background:#111128;color:#aaa;font-size:13px;border-radius:0 0 0 8px">Email</td><td style="padding:10px 14px;background:#111128;color:#e0e0e0;font-size:14px;border-radius:0 0 8px 0">' . htmlspecialchars($toEmail) . '</td></tr>
        </table>
        <div style="text-align:center">
            <a href="' . $base . '/index.php" style="display:inline-block;background:linear-gradient(135deg,#d4af37,#b8960c);color:#0a0a0f;text-decoration:none;padding:13px 34px;border-radius:8px;font-weight:700;font-size:15px">Explore Rooms &rarr;</a>
        </div>';
    return sendRVMail($toEmail, $toName, 'Welcome to Royale Vista!', emailTemplate('Welcome Aboard', $toName, $body));
}

// ── LOGIN NOTIFICATION ───────────────────────────────────────
function sendLoginNotificationEmail(string $toEmail, string $toName): bool
{
    $base = defined('BASE') ? BASE : '';
    $time = date('D, d M Y  H:i') . ' UTC';
    $body = '
        <p style="font-size:16px;color:#e0e0e0;margin:0 0 20px">We detected a new sign-in to your account.</p>
        <table style="width:100%;border-collapse:collapse;margin-bottom:24px">
            <tr><td colspan="2" style="padding:10px 14px;background:#1a1a2e;border-radius:8px 8px 0 0;color:#d4af37;font-weight:600;font-size:12px;letter-spacing:1px;text-transform:uppercase">Sign-in Details</td></tr>
            <tr><td style="padding:10px 14px;background:#12122a;color:#aaa;font-size:13px;width:40%">Date &amp; Time</td><td style="padding:10px 14px;background:#12122a;color:#e0e0e0;font-size:14px"><strong>' . $time . '</strong></td></tr>
            <tr><td style="padding:10px 14px;background:#111128;color:#aaa;font-size:13px;border-radius:0 0 0 8px">Account</td><td style="padding:10px 14px;background:#111128;color:#e0e0e0;font-size:14px;border-radius:0 0 8px 0">' . htmlspecialchars($toEmail) . '</td></tr>
        </table>
        <p style="font-size:14px;color:#aaa;margin-bottom:20px">If this was not you, change your password immediately.</p>
        <div style="text-align:center"><a href="' . $base . '/profile.php" style="display:inline-block;background:linear-gradient(135deg,#d4af37,#b8960c);color:#0a0a0f;text-decoration:none;padding:12px 30px;border-radius:8px;font-weight:700;font-size:14px">My Account &rarr;</a></div>';
    return sendRVMail($toEmail, $toName, 'New Sign-in to Your Royale Vista Account', emailTemplate('Sign-in Detected', $toName, $body));
}

// ── BOOKING CONFIRMATION EMAIL ───────────────────────────────
function sendBookingConfirmationEmail(string $toEmail, string $toName, array $booking, array $rooms): bool
{
    $base = defined('BASE') ? BASE : '';
    $ref = htmlspecialchars($booking['booking_ref']);
    $invNo = htmlspecialchars($booking['invoice_no'] ?? $booking['booking_ref']);
    $invUrl = $base . '/invoice.php?ref=' . urlencode($booking['booking_ref']);
    $subject = 'Booking Confirmed - ' . $booking['booking_ref'] . ' | Royale Vista';

    // Room rows
    $roomRows = '';
    foreach ($rooms as $r) {
        $total = (float) ($r['total'] ?? ($r['price_usd'] * $r['qty'] * ($r['nights'] ?? $booking['nights'])));
        $roomRows .= '
        <tr>
          <td style="padding:13px 16px;border-bottom:1px solid rgba(212,175,55,0.08);color:#e0e0e0;font-size:14px;font-weight:600">' . htmlspecialchars($r['name']) . '</td>
          <td style="padding:13px 16px;border-bottom:1px solid rgba(212,175,55,0.08);color:#aaa;font-size:13px;text-align:center">' . (int) $r['qty'] . '</td>
          <td style="padding:13px 16px;border-bottom:1px solid rgba(212,175,55,0.08);color:#aaa;font-size:13px;text-align:center">' . (int) ($r['nights'] ?? $booking['nights']) . '</td>
          <td style="padding:13px 16px;border-bottom:1px solid rgba(212,175,55,0.08);color:#aaa;font-size:13px;text-align:right">$' . number_format((float) $r['price_usd'], 2) . '</td>
          <td style="padding:13px 16px;border-bottom:1px solid rgba(212,175,55,0.08);color:#d4af37;font-size:14px;font-weight:700;text-align:right">$' . number_format($total, 2) . '</td>
        </tr>';
    }

    // Discount row
    $discRow = '';
    if (!empty($booking['discount_usd']) && (float) $booking['discount_usd'] > 0) {
        $offerLabel = !empty($booking['offer_code']) ? ' (Code: ' . htmlspecialchars($booking['offer_code']) . ')' : '';
        $discRow = '<tr><td colspan="4" style="padding:11px 16px;color:#5cb85c;font-size:13px;text-align:right;border-bottom:1px solid rgba(212,175,55,0.08)">Discount' . $offerLabel . '</td><td style="padding:11px 16px;color:#5cb85c;font-size:14px;font-weight:600;text-align:right;border-bottom:1px solid rgba(212,175,55,0.08)">- $' . number_format((float) $booking['discount_usd'], 2) . '</td></tr>';
    }

    $payNames = ['card' => '&nbsp;Credit / Debit Card', 'upi' => '&nbsp;UPI Transfer', 'hotel' => '&nbsp;Pay at Hotel'];
    $payEmoji = ['card' => '&#x1F4B3;', 'upi' => '&#x1F4F1;', 'hotel' => '&#x1F3E8;'];
    $payLabel = ($payEmoji[$booking['pay_method']] ?? '') . ($payNames[$booking['pay_method']] ?? ucfirst($booking['pay_method']));
    $paidAt = !empty($booking['paid_at']) ? 'Paid on ' . date('d M Y, H:i', strtotime($booking['paid_at'])) : 'Payment due at hotel check-in';
    $psColor = $booking['pay_status'] === 'paid' ? '#5cb85c' : '#f0ad4e';
    $psBg = $booking['pay_status'] === 'paid' ? 'rgba(92,184,92,0.15)' : 'rgba(240,173,78,0.15)';

    $nights = (int) $booking['nights'];
    $adults = (int) ($booking['adults'] ?? 1);
    $children = (int) ($booking['children'] ?? 0);
    $guests = $adults . ' Adult' . ($adults > 1 ? 's' : '') . ($children > 0 ? ' &amp; ' . $children . ' Child' . ($children > 1 ? 'ren' : '') : '');

    $specialReq = '';
    if (!empty($booking['special_req'])) {
        $specialReq = '<div style="margin-bottom:24px"><div style="font-size:12px;color:#d4af37;font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px">Special Requests</div><div style="background:#111128;border:1px solid rgba(212,175,55,0.15);border-radius:8px;padding:13px 16px;color:#c0c0c0;font-size:13px;line-height:1.8">' . nl2br(htmlspecialchars($booking['special_req'])) . '</div></div>';
    }

    $content = '
    <div style="background:linear-gradient(135deg,#0d2e1a,#0a1f12);border:1px solid rgba(92,184,92,0.3);border-radius:12px;padding:20px 24px;margin-bottom:24px;text-align:center">
        <div style="font-size:38px;margin-bottom:8px">&#x2705;</div>
        <div style="font-size:20px;color:#5cb85c;font-weight:700;margin-bottom:4px">Booking Confirmed!</div>
        <div style="font-size:13px;color:#aaa">We look forward to welcoming you at Royale Vista.</div>
    </div>

    <div style="background:#1a1a2e;border:1px solid rgba(212,175,55,0.2);border-radius:10px;padding:16px 20px;margin-bottom:22px">
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td><div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">Booking Reference</div><div style="font-size:22px;color:#d4af37;font-weight:700;letter-spacing:2px">' . $ref . '</div></td>
                <td style="text-align:right"><div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">Invoice No.</div><div style="font-size:16px;color:#c0c0c0;font-weight:600">' . $invNo . '</div></td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom:22px">
        <div style="font-size:11px;color:#d4af37;font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:11px">Stay Details</div>
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="padding:12px 16px;background:#12122a;border-radius:8px 0 0 0;width:50%">
                    <div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">CHECK-IN</div>
                    <div style="font-size:15px;color:#e0e0e0;font-weight:600">' . date('D, d M Y', strtotime($booking['check_in'])) . '</div>
                    <div style="font-size:11px;color:#888;margin-top:2px">From 2:00 PM</div>
                </td>
                <td style="padding:12px 16px;background:#111128;border-radius:0 8px 0 0;text-align:right">
                    <div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">CHECK-OUT</div>
                    <div style="font-size:15px;color:#e0e0e0;font-weight:600">' . date('D, d M Y', strtotime($booking['check_out'])) . '</div>
                    <div style="font-size:11px;color:#888;margin-top:2px">By 12:00 PM</div>
                </td>
            </tr>
            <tr>
                <td style="padding:12px 16px;background:#111128;border-radius:0 0 0 8px">
                    <div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">DURATION</div>
                    <div style="font-size:15px;color:#d4af37;font-weight:600">' . $nights . ' Night' . ($nights != 1 ? 's' : '') . '</div>
                </td>
                <td style="padding:12px 16px;background:#12122a;border-radius:0 0 8px 0;text-align:right">
                    <div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">GUESTS</div>
                    <div style="font-size:14px;color:#e0e0e0;font-weight:600">' . $guests . '</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom:22px">
        <div style="font-size:11px;color:#d4af37;font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:11px">Room Details</div>
        <table style="width:100%;border-collapse:collapse;background:#12122a;border-radius:10px;overflow:hidden">
            <thead>
                <tr style="background:#1a1a2e">
                    <th style="padding:10px 16px;font-size:10px;color:#d4af37;letter-spacing:1px;text-transform:uppercase;text-align:left;font-weight:600">Room Type</th>
                    <th style="padding:10px 16px;font-size:10px;color:#d4af37;letter-spacing:1px;text-align:center;font-weight:600">Qty</th>
                    <th style="padding:10px 16px;font-size:10px;color:#d4af37;letter-spacing:1px;text-align:center;font-weight:600">Nights</th>
                    <th style="padding:10px 16px;font-size:10px;color:#d4af37;letter-spacing:1px;text-align:right;font-weight:600">Rate</th>
                    <th style="padding:10px 16px;font-size:10px;color:#d4af37;letter-spacing:1px;text-align:right;font-weight:600">Amount</th>
                </tr>
            </thead>
            <tbody>' . $roomRows . '</tbody>
        </table>
    </div>

    <div style="margin-bottom:22px">
        <div style="font-size:11px;color:#d4af37;font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:11px">Price Summary</div>
        <table style="width:100%;border-collapse:collapse;background:#12122a;border-radius:10px;overflow:hidden">
            <tbody>
                <tr><td colspan="4" style="padding:11px 16px;color:#aaa;font-size:13px;text-align:right;border-bottom:1px solid rgba(212,175,55,0.08)">Room Subtotal</td><td style="padding:11px 16px;color:#e0e0e0;font-size:14px;font-weight:600;text-align:right;border-bottom:1px solid rgba(212,175,55,0.08)">$' . number_format((float) $booking['total_usd'], 2) . '</td></tr>
                ' . $discRow . '
                <tr><td colspan="4" style="padding:11px 16px;color:#aaa;font-size:13px;text-align:right;border-bottom:1px solid rgba(212,175,55,0.08)">Taxes &amp; Fees (18% GST)</td><td style="padding:11px 16px;color:#e0e0e0;font-size:13px;text-align:right;border-bottom:1px solid rgba(212,175,55,0.08)">$' . number_format((float) $booking['taxes_usd'], 2) . '</td></tr>
                <tr style="background:#1a1a2e"><td colspan="4" style="padding:14px 16px;color:#d4af37;font-size:15px;font-weight:700;text-align:right">Total ' . ($booking['pay_status'] === 'paid' ? 'Paid' : 'Due') . '</td><td style="padding:14px 16px;color:#d4af37;font-size:20px;font-weight:700;text-align:right">$' . number_format((float) $booking['final_usd'], 2) . '</td></tr>
            </tbody>
        </table>
    </div>

    <div style="background:#12122a;border:1px solid rgba(212,175,55,0.15);border-radius:10px;padding:14px 18px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between">
        <div><div style="font-size:14px;color:#e0e0e0;font-weight:600;margin-bottom:3px">' . $payLabel . '</div><div style="font-size:12px;color:#888">' . $paidAt . '</div></div>
        <div style="background:' . $psBg . ';color:' . $psColor . ';padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px">' . ucfirst($booking['pay_status']) . '</div>
    </div>

    ' . $specialReq . '

    <div style="background:linear-gradient(135deg,#1a1a2e,#111128);border:1px solid rgba(212,175,55,0.25);border-radius:12px;padding:22px 24px;margin-bottom:18px;text-align:center">
        <div style="font-size:32px;margin-bottom:10px">&#x1F4C4;</div>
        <div style="font-size:15px;color:#e0e0e0;font-weight:600;margin-bottom:6px">Your Invoice is Ready</div>
        <div style="font-size:13px;color:#888;margin-bottom:18px">View the full invoice online and download it as a PDF for your records.</div>
        <a href="' . $invUrl . '" style="display:inline-block;background:linear-gradient(135deg,#d4af37,#b8960c);color:#0a0a0f;text-decoration:none;padding:13px 34px;border-radius:8px;font-weight:700;font-size:15px;letter-spacing:0.5px">View &amp; Download Invoice &#x2192;</a>
    </div>

    <div style="background:#111128;border-radius:10px;padding:13px 18px">
        <div style="font-size:12px;color:#777;line-height:2">
            <strong style="color:#d4af37">Important:</strong> Present this email or your invoice at check-in. &nbsp;|&nbsp;
            <a href="mailto:stay@royalevista.com" style="color:#d4af37">stay@royalevista.com</a> &nbsp;|&nbsp;
            <a href="tel:+12125550100" style="color:#d4af37">+1 212 555 0100</a>
        </div>
    </div>';

    return sendRVMail($toEmail, $toName, $subject, emailTemplate('Booking Confirmed', $toName, $content));
}

// ── CANCELLATION EMAIL ───────────────────────────────────────
function sendCancellationEmail(string $toEmail, string $toName, array $booking): bool
{
    $base = defined('BASE') ? BASE : '';
    $ref = htmlspecialchars($booking['booking_ref']);
    $invUrl = $base . '/invoice.php?ref=' . urlencode($booking['booking_ref']);
    $subject = 'Booking Cancelled - ' . $booking['booking_ref'] . ' | Royale Vista';

    $payNames = ['card' => 'Credit / Debit Card', 'upi' => 'UPI Transfer', 'hotel' => 'Pay at Hotel'];
    $payLabel = $payNames[$booking['pay_method']] ?? ucfirst($booking['pay_method']);

    $content = '
    <div style="background:linear-gradient(135deg,#2e0d0d,#1f0a0a);border:1px solid rgba(224,85,85,0.3);border-radius:12px;padding:20px 24px;margin-bottom:24px;text-align:center">
        <div style="font-size:38px;margin-bottom:8px">&#x274C;</div>
        <div style="font-size:20px;color:#e05555;font-weight:700;margin-bottom:4px">Booking Cancelled</div>
        <div style="font-size:13px;color:#aaa">Your reservation has been cancelled as requested.</div>
    </div>

    <div style="background:#1a1a2e;border:1px solid rgba(224,85,85,0.2);border-radius:10px;padding:16px 20px;margin-bottom:22px">
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td><div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">Booking Reference</div><div style="font-size:22px;color:#e05555;font-weight:700;letter-spacing:2px">' . $ref . '</div></td>
                <td style="text-align:right"><div style="background:rgba(224,85,85,0.15);color:#e05555;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:1px;display:inline-block">CANCELLED</div></td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom:22px">
        <div style="font-size:11px;color:#d4af37;font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:11px">Cancelled Reservation</div>
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="padding:12px 16px;background:#12122a;border-radius:8px 0 0 0;width:50%">
                    <div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">GUEST</div>
                    <div style="font-size:15px;color:#e0e0e0;font-weight:600">' . htmlspecialchars($booking['guest_name']) . '</div>
                </td>
                <td style="padding:12px 16px;background:#111128;border-radius:0 8px 0 0;text-align:right">
                    <div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">DURATION</div>
                    <div style="font-size:15px;color:#e0e0e0;font-weight:600">' . ($booking['nights'] ?? '–') . ' Night' . (($booking['nights'] ?? 1) != 1 ? 's' : '') . '</div>
                </td>
            </tr>
            <tr>
                <td style="padding:12px 16px;background:#111128;border-radius:0 0 0 8px">
                    <div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">WAS CHECK-IN</div>
                    <div style="font-size:14px;color:#c0c0c0">' . date('D, d M Y', strtotime($booking['check_in'])) . '</div>
                </td>
                <td style="padding:12px 16px;background:#12122a;border-radius:0 0 8px 0;text-align:right">
                    <div style="font-size:10px;color:#888;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">WAS CHECK-OUT</div>
                    <div style="font-size:14px;color:#c0c0c0">' . date('D, d M Y', strtotime($booking['check_out'])) . '</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="background:#12122a;border:1px solid rgba(212,175,55,0.15);border-radius:10px;margin-bottom:22px;overflow:hidden">
        <div style="display:flex;justify-content:space-between;padding:11px 18px;border-bottom:1px solid rgba(212,175,55,0.08)">
            <span style="color:#aaa;font-size:13px">Original Amount</span>
            <span style="color:#e0e0e0;font-size:14px;font-weight:600">$' . number_format((float) $booking['final_usd'], 2) . '</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:11px 18px;background:#1a1a2e">
            <span style="color:#aaa;font-size:13px;font-weight:600">Payment Method</span>
            <span style="color:#e0e0e0;font-size:13px">' . $payLabel . '</span>
        </div>
    </div>

    <div style="background:linear-gradient(135deg,#1a1a2e,#111128);border:1px solid rgba(212,175,55,0.25);border-radius:12px;padding:22px 24px;margin-bottom:18px;text-align:center">
        <div style="font-size:32px;margin-bottom:10px">&#x1F4C4;</div>
        <div style="font-size:15px;color:#e0e0e0;font-weight:600;margin-bottom:6px">Cancellation Invoice Available</div>
        <div style="font-size:13px;color:#888;margin-bottom:18px">Your invoice has been updated with cancellation status. Download it for your records.</div>
        <a href="' . $invUrl . '" style="display:inline-block;background:linear-gradient(135deg,#d4af37,#b8960c);color:#0a0a0f;text-decoration:none;padding:13px 34px;border-radius:8px;font-weight:700;font-size:15px;letter-spacing:0.5px">View &amp; Download Invoice &#x2192;</a>
    </div>

    <div style="background:#111128;border-radius:10px;padding:14px 18px;margin-bottom:18px">
        <div style="font-size:12px;color:#777;line-height:2">
            <strong style="color:#d4af37">Refund Policy:</strong> Refunds are processed within 5-7 business days to your original payment method, subject to our cancellation policy.
        </div>
    </div>

    <div style="text-align:center">
        <a href="' . $base . '/rooms.php" style="display:inline-block;background:rgba(212,175,55,0.1);color:#d4af37;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:600;border:1px solid rgba(212,175,55,0.3)">Browse Rooms Again &#x2192;</a>
    </div>';

    return sendRVMail($toEmail, $toName, $subject, emailTemplate('Booking Cancelled', $toName, $content));
}

// ── FORGOT PASSWORD (OTP) EMAIL ──────────────────────────────
function sendOTPEmail(string $toEmail, string $toName, string $otp): bool
{
    $body = '
        <div style="background:linear-gradient(135deg,#1a1a2e,#0a0a1f);border:1px solid rgba(212,175,55,0.2);border-radius:12px;padding:24px;margin-bottom:24px;text-align:center">
            <p style="font-size:16px;color:#e0e0e0;margin:0 0 16px">Use the following One-Time Password (OTP) to reset your password. This code is valid for 15 minutes.</p>
            <div style="background:rgba(212,175,55,0.1);border:2px dashed #d4af37;border-radius:12px;padding:20px;display:inline-block;margin:10px 0">
                <span style="font-size:36px;font-weight:800;color:#d4af37;letter-spacing:10px;font-family:monospace">' . $otp . '</span>
            </div>
            <p style="font-size:13px;color:#888;margin-top:16px">If you did not request a password reset, please ignore this email or contact support.</p>
        </div>';
    return sendRVMail($toEmail, $toName, 'Password Reset OTP — Royale Vista', emailTemplate('Reset Your Password', $toName, $body));
}

// ── SHARED TEMPLATE ──────────────────────────────────────────
function emailTemplate(string $title, string $guestName, string $bodyHtml): string
{
    $firstName = htmlspecialchars(explode(' ', trim($guestName))[0]);
    $base = defined('BASE') ? BASE : '';
    $year = date('Y');
    return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($title) . '</title></head>
<body style="margin:0;padding:0;background:#06060f;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#06060f;padding:36px 0">
  <tr><td align="center">
    <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#0d0d1f;border:1px solid rgba(212,175,55,0.2);border-radius:16px;overflow:hidden">
      <tr>
        <td style="background:linear-gradient(135deg,#1a1a3e,#0a0a1f);padding:28px 40px;text-align:center;border-bottom:2px solid #d4af37">
          <div style="font-size:10px;letter-spacing:4px;color:#d4af37;text-transform:uppercase;margin-bottom:6px">Luxury Collection</div>
          <h1 style="margin:0;font-size:24px;color:#d4af37;letter-spacing:3px;font-weight:300">ROYALE VISTA</h1>
        </td>
      </tr>
      <tr><td style="background:#0a0a1a;padding:16px 40px;border-bottom:1px solid rgba(212,175,55,0.08)"><p style="margin:0;font-size:19px;color:#fff;font-weight:600">' . htmlspecialchars($title) . '</p></td></tr>
      <tr>
        <td style="padding:28px 40px">
          <p style="font-size:15px;color:#999;margin:0 0 22px">Dear <strong style="color:#e0e0e0">' . $firstName . '</strong>,</p>
          ' . $bodyHtml . '
        </td>
      </tr>
      <tr><td style="padding:0 40px"><div style="height:1px;background:rgba(212,175,55,0.1)"></div></td></tr>
      <tr>
        <td style="padding:22px 40px;text-align:center">
          <p style="margin:0 0 8px;font-size:12px;color:#555">&copy; ' . $year . ' Royale Vista Hotel &mdash; All Rights Reserved</p>
          <p style="margin:0;font-size:12px">
            <a href="' . $base . '/index.php" style="color:#d4af37;text-decoration:none">Website</a> &nbsp;&middot;&nbsp;
            <a href="' . $base . '/bookings.php" style="color:#d4af37;text-decoration:none">My Bookings</a> &nbsp;&middot;&nbsp;
            <a href="' . $base . '/contact.php" style="color:#d4af37;text-decoration:none">Contact</a>
          </p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body></html>';
}