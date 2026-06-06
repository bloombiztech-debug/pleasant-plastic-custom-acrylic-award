<?php
// submit.php - Handle form submission, send email, and record to CSV

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Sanitize and collect form data
$fname  = isset($_POST['fname'])  ? strip_tags(trim($_POST['fname']))  : '';
$lname  = isset($_POST['lname'])  ? strip_tags(trim($_POST['lname']))  : '';
$email  = isset($_POST['email'])  ? strip_tags(trim($_POST['email']))  : '';
$phone  = isset($_POST['phone'])  ? strip_tags(trim($_POST['phone']))  : '';
$atype  = isset($_POST['atype'])  ? strip_tags(trim($_POST['atype']))  : '';
$qty    = isset($_POST['qty'])    ? strip_tags(trim($_POST['qty']))    : '';
$msg    = isset($_POST['msg'])    ? strip_tags(trim($_POST['msg']))    : '';

// Basic validation
$errors = [];
if (empty($fname)) {
    $errors[] = 'First name is required.';
}
if (empty($email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}

// If validation fails, return error
if (!empty($errors)) {
    http_response_code(400);
    echo "Please fill out all required fields.";
    exit;
}

// ============================================
// 1. RECORD SUBMISSION IN CSV FILE (Excel)
// ============================================
$csvDir  = __DIR__ . '/submissions';      // folder to store the CSV
$csvFile = $csvDir . '/submissions.csv';

// Create directory if it doesn't exist
if (!is_dir($csvDir)) {
    mkdir($csvDir, 0755, true);
}

// Check if we need to write headers (file doesn't exist or is empty)
$writeHeaders = !file_exists($csvFile) || filesize($csvFile) === 0;

// Open file for appending (with locking to prevent concurrency issues)
$fp = fopen($csvFile, 'a');
if ($fp === false) {
    // File could not be opened; log error but continue with email sending
    error_log("Could not open CSV file for writing: $csvFile");
} else {
    // Acquire exclusive lock
    if (flock($fp, LOCK_EX)) {
        // Write header row if new file
        if ($writeHeaders) {
            $headers = ['Timestamp', 'First Name', 'Last Name', 'Email', 'Phone', 'Award Type', 'Quantity', 'Project Details'];
            fputcsv($fp, $headers);
        }
        // Write the data row
        $dataRow = [
            date('Y-m-d H:i:s'),
            $fname,
            $lname,
            $email,
            $phone,
            $atype,
            $qty,
            $msg
        ];
        fputcsv($fp, $dataRow);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// ============================================
// 2. SEND EMAIL NOTIFICATION
// ============================================
$to = "ajmalmk707@gmail.com";
$subject = "New Award Quote Request from $fname $lname";

// Build HTML email (same attractive template)
$emailBody = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Quote Request</title>
</head>
<body style="margin:0; padding:0; background-color:#f8f9fa; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8f9fa; padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:6px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#D9000F; padding:24px 30px; text-align:left;">
                            <h2 style="margin:0; color:#ffffff; font-size:22px; font-weight:bold;">New Quote Request</h2>
                            <p style="margin:5px 0 0 0; color:#ffffff; font-size:13px; opacity:0.9;">Pleasant Plastic – Custom Acrylic Awards & Trophies, Dubai</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:30px 30px 24px 30px;">
                            <h3 style="margin:0 0 6px 0; color:#0a0a0a; font-size:18px;">Customer Information</h3>
                            <p style="margin:0 0 20px 0; color:#6c757d; font-size:13px;">The following details were submitted via the website quote form.</p>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; width:140px; color:#6c757d; font-size:13px; font-weight:bold;">First Name</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#0a0a0a; font-size:14px;">' . htmlspecialchars($fname) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#6c757d; font-size:13px; font-weight:bold;">Last Name</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#0a0a0a; font-size:14px;">' . htmlspecialchars($lname) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#6c757d; font-size:13px; font-weight:bold;">Email</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#0a0a0a; font-size:14px;"><a href="mailto:' . htmlspecialchars($email) . '" style="color:#D9000F; text-decoration:none;">' . htmlspecialchars($email) . '</a></td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#6c757d; font-size:13px; font-weight:bold;">Phone / WhatsApp</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#0a0a0a; font-size:14px;">' . htmlspecialchars($phone) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#6c757d; font-size:13px; font-weight:bold;">Award Type</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#0a0a0a; font-size:14px;">' . htmlspecialchars($atype) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#6c757d; font-size:13px; font-weight:bold;">Quantity</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e9ecef; color:#0a0a0a; font-size:14px;">' . htmlspecialchars($qty) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6c757d; font-size:13px; font-weight:bold;">Project Details</td>
                                    <td style="padding:10px 0; color:#0a0a0a; font-size:14px; line-height:1.5;">' . nl2br(htmlspecialchars($msg)) . '</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f9fa; padding:20px 30px; border-top:1px solid #e9ecef;">
                            <p style="margin:0 0 5px 0; color:#6c757d; font-size:12px;">This email was automatically generated from pleasantplastic.com</p>
                            <p style="margin:0; color:#6c757d; font-size:12px;">&copy; 2025 Pleasant Plastic LLC, Dubai UAE</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
';

// Email headers
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: Pleasant Plastic Website <noreply@pleasantplastic.com>\r\n";
$headers .= "Reply-To: $email\r\n";

// Send the email
$mailSent = mail($to, $subject, $emailBody, $headers);

// Return response to the AJAX/fetch request
if ($mailSent) {
    echo "✓ Quote Request Sent - We'll be in touch within 24 hours!";
} else {
    http_response_code(500);
    echo "Server error: Unable to send email. Please try again later or WhatsApp us.";
}
?>
