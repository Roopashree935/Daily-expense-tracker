<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dbconnection.php';
require_once __DIR__ . '/includes/escape.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validate.php';
require_login();

// Reached by POST from the report form; a POST must carry this session's token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
}

  

  ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Daily Expense Tracker || Monthwise Expense Report</title>
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/font-awesome.min.css" rel="stylesheet">
	<link href="css/datepicker3.css" rel="stylesheet">
	<link href="css/styles.css" rel="stylesheet">
	
	<!--Custom Font-->
	<link href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
	
</head>
<body>
	<?php include_once('includes/header.php');?>
	<?php include_once('includes/sidebar.php');?>
		
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#">
					<em class="fa fa-home"></em>
				</a></li>
				<li class="active">Monthwise Expense Report</li>
			</ol>
		</div><!--/.row-->
		
		
				
		
		<div class="row">
			<div class="col-lg-12">
			
				
				
				<div class="panel panel-default">
					<div class="panel-heading">Monthwise Expense Report</div>
					<div class="panel-body">

						<div class="col-md-12">
					
<?php
// The page is also reachable without a POST (someone opening the URL directly),
// so every field needs a default before it is used.
$fdate = trim($_POST['fromdate'] ?? '');
$tdate = trim($_POST['todate'] ?? '');
$rtype = $_POST['requesttype'] ?? '';
$totalsexp = 0;

// Only two real dates in the right order can produce a meaningful report.
$dateError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_date($fdate) || !is_valid_date($tdate)) {
        $dateError = 'Please choose a valid from date and to date.';
    } elseif ($fdate > $tdate) {
        $dateError = 'The from date must be on or before the to date.';
    }
}
?>
<h5 align="center" style="color:blue">Monthwise Expense Report from <?php echo e($fdate)?> to <?php echo e($tdate)?></h5>
<?php if ($dateError !== '') { ?>
<p style="color:red" align="center"><?php echo e($dateError); ?></p>
<?php } ?>
<hr />
                                    <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <tr>
              <th>S.NO</th>
              <th>Month-Year</th>
              <th>Expense Amount</th>
                </tr>
                                        </tr>
                                        </thead>
 <?php
$userid = (int) $_SESSION['detsuid'];
$rows = $dateError === '' ? db_rows($con, "SELECT month(ExpenseDate) as rptmonth, year(ExpenseDate) as rptyear, SUM(ExpenseCost) as totalmonth FROM tblexpense where ExpenseDate BETWEEN ? and ? and UserId = ? group by month(ExpenseDate), year(ExpenseDate)", 'ssi', [$fdate, $tdate, $userid]) : [];
$cnt=1;
foreach ($rows as $row) {

?>
              
                <tr>
                  <td><?php echo e($cnt);?></td>
            
                  <td><?php  echo e($row['rptmonth']) . "-" . e($row['rptyear']);?></td>
                  <td><?php $ttlsl = $row['totalmonth']; echo e($ttlsl); ?></td>
           
           
                </tr>
                <?php
                $totalsexp+=$ttlsl; 
$cnt=$cnt+1;
}?>

 <tr>
  <th colspan="2" style="text-align:center">Grand Total</th>     
  <td><?php echo e($totalsexp);?></td>
 </tr>     

                                    </table>




						</div>
					</div>
				</div><!-- /.panel-->
			</div><!-- /.col-->
			<?php include_once('includes/footer.php');?>
		</div><!-- /.row -->
	</div><!--/.main-->
	
<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/chart.min.js"></script>
	<script src="js/chart-data.js"></script>
	<script src="js/easypiechart.js"></script>
	<script src="js/easypiechart-data.js"></script>
	<script src="js/bootstrap-datepicker.js"></script>
	<script src="js/custom.js"></script>
	
</body>
</html>
