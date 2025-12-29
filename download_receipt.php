<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_config.php';

// Get payment ID from query string
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if ($payment_id <= 0) {
    die('Invalid payment ID');
}

try {
    // Get payment details with student and fee type information
    $stmt = $pdo->prepare("SELECT fp.*, s.full_name as student_name, s.email as student_email, 
                           s.class_id, c.class_name, c.class_code, ft.name as fee_type_name, 
                           ft.amount as fee_type_amount
                           FROM fee_payments fp 
                           JOIN students s ON fp.student_id = s.id 
                           LEFT JOIN classes c ON s.class_id = c.class_id
                           LEFT JOIN fee_types ft ON fp.fee_type_id = ft.id 
                           WHERE fp.id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        die('Payment record not found');
    }

    // Get school information (you can customize this)
    $school_name = "FISC School";
    $school_address = "Your School Address";
    $school_phone = "Your School Phone";
    $school_email = "info@school.com";

} catch (PDOException $e) {
    die('Error fetching payment details: ' . $e->getMessage());
}

// Generate receipt HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payment Receipt - <?php echo htmlspecialchars($payment['student_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .receipt-header h1 {
            color: #4f46e5;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .receipt-header p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .receipt-body {
            margin: 30px 0;
        }

        .receipt-section {
            margin-bottom: 25px;
        }

        .receipt-section h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            width: 40%;
        }

        .info-value {
            color: #333;
            width: 60%;
            text-align: right;
        }

        .amount-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            padding: 10px 0;
        }

        .amount-row.total {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            border-top: 2px solid #4f46e5;
            margin-top: 10px;
            padding-top: 15px;
        }

        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        .receipt-number {
            background: #4f46e5;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: bold;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                padding: 20px;
            }
            .no-print {
                display: none;
            }
        }

        .print-button {
            text-align: center;
            margin: 20px 0;
        }

        .btn-print {
            background: #4f46e5;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-print:hover {
            background: #4338ca;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1><?php echo htmlspecialchars($school_name); ?></h1>
            <p><?php echo htmlspecialchars($school_address); ?></p>
            <p>Phone: <?php echo htmlspecialchars($school_phone); ?> | Email: <?php echo htmlspecialchars($school_email); ?></p>
        </div>

        <div class="receipt-body">
            <div class="receipt-section">
                <h2>Payment Information</h2>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Time:</span>
                    <span class="info-value"><?php echo date('g:i A', strtotime($payment['created_at'])); ?></span>
                </div>
            </div>

            <div class="receipt-section">
                <h2>Student Information</h2>
                <div class="info-row">
                    <span class="info-label">Student Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['student_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Class:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['class_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($payment['class_code'] ?? 'N/A'); ?>)</span>
                </div>
            </div>

            <div class="receipt-section">
                <h2>Fee Details</h2>
                <div class="info-row">
                    <span class="info-label">Fee Type:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['fee_type_name'] ?? 'General'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Term:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['term']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Academic Year:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['academic_year']); ?></span>
                </div>
            </div>

            <div class="amount-section">
                <div class="amount-row">
                    <span>Amount Paid:</span>
                    <span>GHC <?php echo number_format($payment['amount'], 2); ?></span>
                </div>
                <div class="amount-row total">
                    <span>Total Amount:</span>
                    <span>GHC <?php echo number_format($payment['amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="receipt-footer">
            <p><strong>Thank you for your payment!</strong></p>
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
        </div>
    </div>

    <div class="print-button no-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <script>
        // Auto-print option (optional - uncomment if you want auto-print)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>

