<?php
/**
 * Email receipt to student/parent
 */

// Get receipt data
$receipt = db_select(
    "SELECT r.*, p.amount, p.payment_date, p.payment_type, p.payment_method,
            s.first_name, s.last_name, s.student_id as student_code, s.email as student_email,
            s.parent_email, a.name as academic_year
     FROM receipts r
     JOIN payments p ON r.payment_id = p.id
     JOIN students s ON p.student_id = s.id
     JOIN academic_years a ON p.academic_year_id = a.id
     WHERE r.id = ?", 
    [$receipt_id], 
    'i'
);

if (!$receipt) {
    echo '<div class="alert alert-danger">Receipt not found</div>';
    return;
}

$receipt = $receipt[0];

// Check if already emailed
if ($receipt['is_emailed']) {
    echo '<div class="alert alert-warning">This receipt has already been emailed on ' . date('F d, Y', strtotime($receipt['emailed_at'])) . '.</div>';
    echo '<div class="mt-3">';
    echo '<a href="index.php?page=receipts&action=view&id=' . $receipt_id . '" class="btn btn-primary">Back to Receipt</a>';
    echo '</div>';
    return;
}

// Check if student or parent email is available
if (empty($receipt['student_email']) && empty($receipt['parent_email'])) {
    echo '<div class="alert alert-danger">No email address is available for this student or parent.</div>';
    echo '<div class="mt-3">';
    echo '<a href="index.php?page=receipts&action=view&id=' . $receipt_id . '" class="btn btn-primary">Back to Receipt</a>';
    echo '</div>';
    return;
}

// Process form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if email settings file exists
    if (!file_exists(__DIR__ . '/../../config/mail.php')) {
        $error = 'Email configuration is not set up. Please contact the administrator.';
    } else {
        require_once __DIR__ . '/../../config/mail.php';
        
        try {
            // Check if PDF exists or generate it
            if (empty($receipt['pdf_path'])) {
                require_once __DIR__ . '/../../includes/receipt_pdf.php';
                $pdf = new ReceiptPDF($receipt);
                $pdf_path = $pdf->generatePDF();
                
                // Update receipt with PDF path
                db_execute(
                    "UPDATE receipts SET pdf_path = ? WHERE id = ?",
                    [$pdf_path, $receipt_id],
                    'si'
                );
                
                $receipt['pdf_path'] = $pdf_path;
            }
            
            // Initialize PHPMailer
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $mail_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $mail_username;
            $mail->Password   = $mail_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $mail_port;
            
            // Recipients
            $mail->setFrom($mail_from_email, $mail_from_name);
            
            if (!empty($_POST['send_to_student']) && !empty($receipt['student_email'])) {
                $mail->addAddress($receipt['student_email'], $receipt['first_name'] . ' ' . $receipt['last_name']);
            }
            
            if (!empty($_POST['send_to_parent']) && !empty($receipt['parent_email'])) {
                $mail->addAddress($receipt['parent_email'], 'Parent of ' . $receipt['first_name'] . ' ' . $receipt['last_name']);
            }
            
            if (!empty($_POST['custom_email'])) {
                $mail->addAddress($_POST['custom_email']);
            }
            
            $mail->addReplyTo($mail_reply_to);
            
            // Attach PDF
            $mail->addAttachment($_SERVER['DOCUMENT_ROOT'] . '/' . $receipt['pdf_path']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Receipt #' . $receipt['receipt_number'] . ' for ' . $receipt['first_name'] . ' ' . $receipt['last_name'];
            
            // Build email body
            $body = '<p>Dear Parent/Guardian,</p>';
            $body .= '<p>Please find attached the receipt for your recent payment:</p>';
            $body .= '<ul>';
            $body .= '<li><strong>Student:</strong> ' . $receipt['first_name'] . ' ' . $receipt['last_name'] . ' (' . $receipt['student_code'] . ')</li>';
            $body .= '<li><strong>Receipt Number:</strong> ' . $receipt['receipt_number'] . '</li>';
            $body .= '<li><strong>Payment Date:</strong> ' . date('F d, Y', strtotime($receipt['payment_date'])) . '</li>';
            $body .= '<li><strong>Amount:</strong> $' . number_format($receipt['amount'], 2) . '</li>';
            $body .= '<li><strong>Payment Type:</strong> ' . ucfirst(str_replace('_', ' ', $receipt['payment_type'])) . '</li>';
            $body .= '</ul>';
            $body .= '<p>Thank you for your payment.</p>';
            $body .= '<p>Best regards,<br>';
            
            // Get school name from settings
            $school_name = 'School Administration';
            $setting = db_select("SELECT value FROM settings WHERE name = 'school_name'");
            if ($setting) {
                $school_name = $setting[0]['value'];
            }
            
            $body .= $school_name . '</p>';
            
            $mail->Body = $body;
            
            // Send email
            $mail->send();
            
            // Update receipt as emailed
            db_execute(
                "UPDATE receipts SET is_emailed = 1, emailed_at = NOW() WHERE id = ?",
                [$receipt_id],
                'i'
            );
            
            $success = true;
            
        } catch (Exception $e) {
            $error = 'Error sending email: ' . $e->getMessage();
        }
    }
}

// Display form or success message
if ($success) {
    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle"></i> Receipt has been emailed successfully.';
    echo '</div>';
    echo '<div class="mt-3">';
    echo '<a href="index.php?page=receipts&action=view&id=' . $receipt_id . '" class="btn btn-primary">Back to Receipt</a>';
    echo '</div>';
} else {
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Email Receipt</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=receipts&action=view&id=<?php echo $receipt_id; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Receipt
        </a>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-envelope"></i> Email Receipt #<?php echo htmlspecialchars($receipt['receipt_number']); ?>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <h5>Receipt Details</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Student:</strong> <?php echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']); ?></p>
                    <p><strong>Receipt Number:</strong> <?php echo htmlspecialchars($receipt['receipt_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($receipt['receipt_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Amount:</strong> $<?php echo number_format($receipt['amount'], 2); ?></p>
                    <p><strong>Payment Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $receipt['payment_type'])); ?></p>
                    <p><strong>Academic Year:</strong> <?php echo htmlspecialchars($receipt['academic_year']); ?></p>
                </div>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="mb-4">
                <h5>Email Recipients</h5>
                
                <?php if (!empty($receipt['student_email'])): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="send_to_student" name="send_to_student" value="1" checked>
                    <label class="form-check-label" for="send_to_student">
                        Student Email: <?php echo htmlspecialchars($receipt['student_email']); ?>
                    </label>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($receipt['parent_email'])): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="send_to_parent" name="send_to_parent" value="1" checked>
                    <label class="form-check-label" for="send_to_parent">
                        Parent Email: <?php echo htmlspecialchars($receipt['parent_email']); ?>
                    </label>
                </div>
                <?php endif; ?>
                
                <div class="mb-3 mt-3">
                    <label for="custom_email" class="form-label">Additional Email Address (optional)</label>
                    <input type="email" class="form-control" id="custom_email" name="custom_email" placeholder="Enter email address">
                </div>
            </div>
            
            <div class="d-flex justify-content-end">
                <a href="index.php?page=receipts&action=view&id=<?php echo $receipt_id; ?>" class="btn btn-secondary me-2">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Send Email
                </button>
            </div>
        </form>
    </div>
</div>

<?php
}
?>