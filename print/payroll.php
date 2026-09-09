<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pdf.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    exit('Payroll record not found.');
}

$stmt = $conn->prepare('SELECT p.*, e.employee_no, e.first_name, e.middle_name, e.last_name, e.department, e.position FROM payroll_records p JOIN employees e ON e.employee_id=p.employee_id WHERE p.payroll_id=? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$payroll = $stmt->get_result()->fetch_assoc();

if (!$payroll) {
    exit('Payroll record not found.');
}

class PayslipPDF extends FPDF
{
    public function Footer()
    {
        pdf_footer($this, 'Payroll Statement - Private and Confidential');
    }
}

$pdf = new PayslipPDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 12, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

pdf_header($pdf, 'PAYROLL STATEMENT', 'Employee payroll record');

$pdf->SetFillColor(6, 59, 70);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(180, 9, pdf_text('PAYSLIP'), 0, 1, 'C', true);
$pdf->SetTextColor(23, 50, 59);

$pdf->Ln(5);
pdf_field_row($pdf, 'Employee ID', $payroll['employee_no'], 'Status', $payroll['status']);
pdf_field_row($pdf, 'Employee Name', employee_name($payroll), 'Date Prepared', pdf_date($payroll['created_at']));
pdf_field_row($pdf, 'Department', $payroll['department'] ?: 'Not assigned', 'Position', $payroll['position'] ?: 'Not assigned');
pdf_field_row($pdf, 'Pay Period', pdf_date($payroll['period_start']) . ' - ' . pdf_date($payroll['period_end']), 'Record Date', pdf_date($payroll['created_at']));

$pdf->Ln(7);

$pdf->SetFillColor(236, 247, 246);
$pdf->SetTextColor(8, 127, 123);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(90, 8, pdf_text('EARNINGS'), 1, 0, 'L', true);
$pdf->Cell(90, 8, pdf_text('DEDUCTIONS'), 1, 1, 'L', true);
$pdf->SetTextColor(23, 50, 59);

$startY = $pdf->GetY();
$leftRows = [
    ['Basic Salary', $payroll['basic_salary']],
    ['Allowances', $payroll['allowances']],
    ['Other Earnings', $payroll['other_earnings']],
];
$rightRows = [
    ['Deductions', $payroll['deductions']],
];

$maxRows = max(count($leftRows), count($rightRows));
for ($i = 0; $i < $maxRows; $i++) {
    $left = $leftRows[$i] ?? ['', null];
    $right = $rightRows[$i] ?? ['', null];

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetDrawColor(215, 225, 227);
    $pdf->Cell(62, 8, pdf_text($left[0]), 1, 0, 'L');
    $pdf->Cell(28, 8, $left[1] === null ? '' : pdf_money($left[1]), 1, 0, 'R');
    $pdf->Cell(62, 8, pdf_text($right[0]), 1, 0, 'L');
    $pdf->Cell(28, 8, $right[1] === null ? '' : pdf_money($right[1]), 1, 1, 'R');
}

$pdf->SetFillColor(247, 249, 249);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(62, 8, pdf_text('TOTAL EARNINGS'), 1, 0, 'L', true);
$pdf->Cell(28, 8, pdf_money($payroll['gross_pay']), 1, 0, 'R', true);
$pdf->Cell(62, 8, pdf_text('TOTAL DEDUCTIONS'), 1, 0, 'L', true);
$pdf->Cell(28, 8, pdf_money($payroll['deductions']), 1, 1, 'R', true);

$pdf->Ln(9);
$pdf->SetFillColor(6, 59, 70);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(120, 14, pdf_text('NET PAY'), 1, 0, 'L', true);
$pdf->Cell(60, 14, pdf_money($payroll['net_pay']), 1, 1, 'R', true);
$pdf->SetTextColor(23, 50, 59);

if (!empty($payroll['notes'])) {
    $pdf->Ln(8);
    pdf_compact_section($pdf, 'Notes');
    pdf_compact_multiline($pdf, 'Payroll Notes', $payroll['notes']);
}

$pdf->Ln(22);
$pdf->SetFont('Arial', '', 8);
$pdf->SetDrawColor(160, 170, 173);
$pdf->Cell(82, 6, pdf_text('Prepared by: __________________________'), 0, 0, 'L');
$pdf->Cell(16, 6, '', 0, 0);
$pdf->Cell(82, 6, pdf_text('Received by: __________________________'), 0, 1, 'L');
$pdf->Ln(7);
$pdf->SetFont('Arial', '', 7.5);
$pdf->SetTextColor(110, 123, 128);
$pdf->Cell(0, 5, pdf_text('This document is an internal payroll record. Please keep it confidential.'), 0, 1, 'C');

$pdf->Output('I', 'payslip-' . $payroll['employee_no'] . '.pdf');
