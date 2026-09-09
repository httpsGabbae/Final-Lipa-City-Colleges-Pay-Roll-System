<?php
require_once __DIR__ . '/../includes/auth.php';

$id=(int)($_GET['id'] ?? 0);
$stmt=$conn->prepare('SELECT p.*,e.employee_no,e.first_name,e.middle_name,e.last_name,e.department,e.position,e.employment_status FROM payroll_records p JOIN employees e ON e.employee_id=p.employee_id WHERE p.payroll_id=? LIMIT 1');
$stmt->bind_param('i',$id);
$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
if(!$row){ http_response_code(404); exit('<div class="print-error">Payroll record not found.</div>'); }
function pp_money($n){ return '₱ '.number_format((float)$n,2); }
$name=trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']);
?>
<div class="native-payroll-statement">
  <div class="native-print-brand">LCC PAYROLL SYSTEM</div>
  <div class="native-print-kicker">PAYROLL STATEMENT</div>
  <div class="native-payroll-title">Employee Payroll Record</div>
  <div class="native-payroll-meta"><span>Employee ID: <strong><?=htmlspecialchars($row['employee_no'])?></strong></span><span>Status: <strong><?=htmlspecialchars($row['status'])?></strong></span></div>
  <div class="native-payroll-grid">
    <div><span>Employee Name</span><strong><?=htmlspecialchars($name)?></strong></div>
    <div><span>Department</span><strong><?=htmlspecialchars($row['department']?:'Not assigned')?></strong></div>
    <div><span>Position</span><strong><?=htmlspecialchars($row['position']?:'Not assigned')?></strong></div>
    <div><span>Pay Period</span><strong><?=htmlspecialchars(date('M d, Y',strtotime($row['period_start'])).' – '.date('M d, Y',strtotime($row['period_end'])))?></strong></div>
  </div>
  <div class="native-payroll-columns">
    <section><h3>Earnings</h3><div class="native-pay-row"><span>Basic Salary</span><strong><?=pp_money($row['basic_salary'])?></strong></div><div class="native-pay-row"><span>Allowances</span><strong><?=pp_money($row['allowances'])?></strong></div><div class="native-pay-row"><span>Other Earnings</span><strong><?=pp_money($row['other_earnings'])?></strong></div><div class="native-pay-total"><span>Gross Pay</span><strong><?=pp_money($row['gross_pay'])?></strong></div></section>
    <section><h3>Deductions</h3><div class="native-pay-row"><span>Total Deductions</span><strong><?=pp_money($row['deductions'])?></strong></div><div class="native-pay-total net"><span>Net Pay</span><strong><?=pp_money($row['net_pay'])?></strong></div></section>
  </div>
  <?php if(!empty($row['notes'])): ?><div class="native-pay-notes"><span>Notes</span><p><?=nl2br(htmlspecialchars($row['notes']))?></p></div><?php endif; ?>
  <div class="native-pay-sign"><span>Prepared by: __________________________</span><span>Date: <?=date('M d, Y')?></span></div>
  <div class="native-print-footer">Private and Confidential · LCC Payroll System</div>
</div>
