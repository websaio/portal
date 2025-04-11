<?php
/**
 * Print receipt
 */

// Get receipt data
$receipt = db_select(
    "SELECT r.*, p.amount, p.payment_date, p.payment_type, p.payment_method, p.reference_number,
            s.first_name, s.last_name, s.student_id as student_code,
            a.name as academic_year, e.grade, e.section
     FROM receipts r
     JOIN payments p ON r.payment_id = p.id
     JOIN students s ON p.student_id = s.id
     JOIN academic_years a ON p.academic_year_id = a.id
     LEFT JOIN student_enrollments e ON p.student_id = e.student_id AND p.academic_year_id = e.academic_year_id
     WHERE r.id = ?", 
    [$receipt_id], 
    'i'
);

if (!$receipt) {
    echo '<div class="alert alert-danger">Receipt not found</div>';
    return;
}

$receipt = $receipt[0];

// If PDF path is already stored, redirect to it
if (!empty($receipt['pdf_path'])) {
    header('Location: ' . $receipt['pdf_path']);
    exit;
}

// Generate PDF and display
require_once __DIR__ . '/../../includes/receipt_pdf.php';
$pdf = new ReceiptPDF($receipt);
$pdf->outputPDF();