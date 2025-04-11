<?php
/**
 * Receipt PDF generation class
 */

// Include mPDF library
require_once __DIR__ . '/../vendor/autoload.php';

class ReceiptPDF {
    private $receipt;
    private $enrollment;
    
    public function __construct($receipt) {
        $this->receipt = $receipt;
        
        // Check if student_id and academic_year_id exist in the receipt data
        if (!isset($this->receipt['student_id']) || !isset($this->receipt['academic_year_id'])) {
            // Try to get them from the payment
            $payment_data = db_select(
                "SELECT student_id, academic_year_id 
                 FROM payments 
                 WHERE id = ?",
                [$this->receipt['payment_id']],
                'i'
            );
            
            if ($payment_data && count($payment_data) > 0) {
                $this->receipt['student_id'] = $payment_data[0]['student_id'];
                $this->receipt['academic_year_id'] = $payment_data[0]['academic_year_id'];
                error_log("Retrieved student_id: " . $this->receipt['student_id'] . " and academic_year_id: " . $this->receipt['academic_year_id'] . " from payment");
            } else {
                error_log("Could not find payment data for payment_id: " . $this->receipt['payment_id']);
            }
        }
        
        // Only attempt to get enrollment data if we have student_id and academic_year_id
        if (isset($this->receipt['student_id']) && isset($this->receipt['academic_year_id'])) {
            $this->enrollment = db_select(
                "SELECT e.*, 
                        (SELECT SUM(amount) FROM payments 
                         WHERE student_id = e.student_id AND academic_year_id = e.academic_year_id) as total_paid
                 FROM student_enrollments e
                 WHERE e.student_id = ? AND e.academic_year_id = ?",
                [$this->receipt['student_id'], $this->receipt['academic_year_id']],
                'ii'
            );
            
            if ($this->enrollment && count($this->enrollment) > 0) {
                $this->enrollment = $this->enrollment[0];
                error_log("Found enrollment data for student: " . $this->receipt['student_id']);
            } else {
                error_log("No enrollment data found for student_id: " . $this->receipt['student_id'] . " and academic_year_id: " . $this->receipt['academic_year_id']);
            }
        } else {
            error_log("Missing student_id or academic_year_id, cannot retrieve enrollment data");
        }
    }
    
    public function generatePDF() {
        try {
            // Create new mPDF document
            $mpdf = new \Mpdf\Mpdf([
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'format' => 'A4',
            ]);
            
            // Get school information from settings
            $school_name = get_setting_value('school_name', 'United International College');
            $school_address = get_setting_value('school_address', 'Al-Najaf, Iraq');
            $school_phone = get_setting_value('school_phone', '+964 7700 000000');
            $school_email = get_setting_value('school_email', 'info@uic-iq.com');
            $school_website = get_setting_value('school_website', 'www.uic-iq.com');
            $school_logo = get_setting_value('school_logo', '');
            
            // Get payment data
            $payment = db_select(
                "SELECT p.*, 
                        s.first_name, s.last_name, s.student_id as student_number,
                        u.first_name as cashier_first_name, u.last_name as cashier_last_name
                 FROM payments p
                 LEFT JOIN students s ON p.student_id = s.id
                 LEFT JOIN users u ON p.created_by = u.id
                 WHERE p.id = ?",
                [$this->receipt['payment_id']],
                'i'
            );
            
            if (!$payment || count($payment) === 0) {
                throw new Exception("Payment not found");
            }
            
            $payment = $payment[0];
            
            // Start building HTML content
            $html = '
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12pt;
                    line-height: 1.4;
                }
                .receipt-header {
                    text-align: center;
                    margin-bottom: 10mm;
                }
                .receipt-header h1 {
                    font-size: 20pt;
                    margin: 0;
                    padding: 0;
                    color: #3366cc;
                }
                .receipt-header p {
                    margin: 2mm 0;
                    padding: 0;
                    font-size: 10pt;
                }
                .receipt-title {
                    text-align: center;
                    margin: 5mm 0;
                    font-size: 16pt;
                    font-weight: bold;
                    color: #3366cc;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 2mm;
                }
                .receipt-info {
                    border: 1px solid #ddd;
                    padding: 5mm;
                    margin-bottom: 5mm;
                    background-color: #f9f9f9;
                }
                .receipt-info table {
                    width: 100%;
                }
                .receipt-info td {
                    padding: 2mm;
                }
                .payment-details {
                    margin-top: 5mm;
                    border-collapse: collapse;
                    width: 100%;
                }
                .payment-details th, .payment-details td {
                    border: 1px solid #ddd;
                    padding: 3mm;
                    text-align: left;
                }
                .payment-details th {
                    background-color: #f0f0f0;
                }
                .footer {
                    margin-top: 10mm;
                    border-top: 1px solid #ddd;
                    padding-top: 3mm;
                    font-size: 10pt;
                    text-align: center;
                }
                .signature {
                    margin-top: 15mm;
                    text-align: right;
                }
                .logo {
                    max-width: 30mm;
                    max-height: 30mm;
                }
                .total-amount {
                    font-weight: bold;
                    font-size: 14pt;
                    color: #3366cc;
                }
                .balance-info {
                    margin-top: 5mm;
                    padding: 3mm;
                    background-color: #f0f8ff;
                    border-left: 4px solid #3366cc;
                }
            </style>
            
            <div class="receipt-header">
            ';
            
            // Add logo if available
            if (!empty($school_logo)) {
                $html .= '<img src="' . $school_logo . '" class="logo" alt="School Logo"><br>';
            }
            
            $html .= '
                <h1>' . htmlspecialchars($school_name) . '</h1>
                <p>' . htmlspecialchars($school_address) . '</p>
                <p>Phone: ' . htmlspecialchars($school_phone) . ' | Email: ' . htmlspecialchars($school_email) . '</p>
                <p>Website: ' . htmlspecialchars($school_website) . '</p>
            </div>
            
            <div class="receipt-title">PAYMENT RECEIPT</div>
            
            <div class="receipt-info">
                <table>
                    <tr>
                        <td width="50%"><strong>Receipt Number:</strong> ' . htmlspecialchars($this->receipt['receipt_number']) . '</td>
                        <td width="50%"><strong>Date:</strong> ' . date('F j, Y', strtotime($payment['payment_date'])) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Student Name:</strong> ' . htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) . '</td>
                        <td><strong>Student ID:</strong> ' . htmlspecialchars($payment['student_number']) . '</td>
                    </tr>
            ';
            
            // Add class/grade info if available
            if ($this->enrollment && !empty($this->enrollment['grade'])) {
                $html .= '
                    <tr>
                        <td><strong>Grade:</strong> ' . htmlspecialchars($this->enrollment['grade']) . '</td>
                        <td><strong>Section:</strong> ' . htmlspecialchars($this->enrollment['section']) . '</td>
                    </tr>
                ';
            }
            
            $html .= '
                </table>
            </div>
            
            <table class="payment-details">
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
                <tr>
                    <td>' . ucfirst(str_replace('_', ' ', $payment['payment_type'])) . ' Payment';
            
            if (!empty($payment['notes'])) {
                $html .= ' <em>(' . htmlspecialchars($payment['notes']) . ')</em>';
            }
            
            $html .= '</td>
                    <td class="total-amount">$' . number_format($payment['amount'], 2) . '</td>
                </tr>
            </table>
            
            <div style="text-align: right; margin-top: 5mm;">
                <strong>Total Paid:</strong> <span class="total-amount">$' . number_format($payment['amount'], 2) . '</span>
            </div>
            ';
            
            // Add tuition information if available
            if ($this->enrollment && isset($this->enrollment['tuition_fee'])) {
                $discount_amount = 0;
                $discount_percentage = 0;
                
                if (isset($this->enrollment['discount_percentage']) && $this->enrollment['discount_percentage'] > 0) {
                    $discount_percentage = $this->enrollment['discount_percentage'];
                    $discount_amount = ($this->enrollment['tuition_fee'] * $discount_percentage) / 100;
                }
                
                $total_due = $this->enrollment['tuition_fee'] - $discount_amount;
                $total_paid = isset($this->enrollment['total_paid']) ? $this->enrollment['total_paid'] : 0;
                $balance = $total_due - $total_paid;
                
                $html .= '
                <div class="balance-info">
                    <h3>Tuition Summary</h3>
                    <table style="width: 100%;">
                        <tr>
                            <td><strong>Tuition Fee:</strong></td>
                            <td align="right">$' . number_format($this->enrollment['tuition_fee'], 2) . '</td>
                        </tr>
                ';
                
                if ($discount_percentage > 0) {
                    $html .= '
                        <tr>
                            <td><strong>Discount (' . $discount_percentage . '%):</strong></td>
                            <td align="right">-$' . number_format($discount_amount, 2) . '</td>
                        </tr>
                    ';
                }
                
                $html .= '
                        <tr>
                            <td><strong>Total Due:</strong></td>
                            <td align="right">$' . number_format($total_due, 2) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Total Paid to Date:</strong></td>
                            <td align="right">$' . number_format($total_paid, 2) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Balance:</strong></td>
                            <td align="right" style="' . ($balance > 0 ? 'color: #cc0000;' : 'color: #009900;') . ' font-weight: bold;">$' . number_format($balance, 2) . '</td>
                        </tr>
                    </table>
                </div>
                ';
            }
            
            // Payment method info
            $html .= '
            <div style="margin-top: 5mm;">
                <strong>Payment Method:</strong> ' . ucfirst(str_replace('_', ' ', $payment['payment_method']));
            
            if (!empty($payment['reference_number'])) {
                $html .= ' (Ref: ' . htmlspecialchars($payment['reference_number']) . ')';
            }
            
            $html .= '
            </div>
            
            <div class="signature">
                <div style="margin-bottom: 15mm;">_____________________________</div>
                <div>Cashier: ' . htmlspecialchars($payment['cashier_first_name'] . ' ' . $payment['cashier_last_name']) . '</div>
            </div>
            
            <div class="footer">
                <p>Thank you for your payment. This is an official receipt.</p>
                <p>Receipt generated on: ' . date('F j, Y h:i a') . '</p>
            </div>
            ';
            
            // Write HTML to PDF
            $mpdf->WriteHTML($html);
            
            // Create uploads directory if it doesn't exist
            $upload_dir = __DIR__ . '/../../uploads/receipts';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Save PDF to file
            $filename = 'receipt_' . $this->receipt['receipt_number'] . '.pdf';
            $filepath = 'uploads/receipts/' . $filename;
            $mpdf->Output(__DIR__ . '/../../' . $filepath, \Mpdf\Output\Destination::FILE);
            
            return $filepath;
            
        } catch (Exception $e) {
            error_log('Error generating PDF: ' . $e->getMessage());
            throw $e;
        }
    }
}