<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dbconnection.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/escape.php';
require_login();

// Shown in the red message line; empty means "nothing to report".
$msg = '';

//code deletion
if(isset($_POST['delid']))
{
csrf_check();
$rowid=intval($_POST['delid']);
$userid=(int) $_SESSION['detsuid'];

// The user id is part of the WHERE clause, not just the id. Without it any
// logged-in user could delete anybody's expense by sending a different number:
// the form only shows their own rows, but nothing stopped a hand-written POST.
$query=db_exec($con, "delete from tblexpense where ID = ? and UserId = ?", 'ii', [$rowid, $userid]);
if($query === false){
echo "<script>alert('Something went wrong. Please try again');</script>";
} elseif($query === 0){
echo "<script>alert('Record not found');</script>";
echo "<script>window.location.href='manage-expense.php'</script>";
} else {
echo "<script>alert('Record successfully deleted');</script>";
echo "<script>window.location.href='manage-expense.php'</script>";
}

}


?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Daily Expense Tracker || Manage Expense</title>
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/font-awesome.min.css" rel="stylesheet">
	<link href="css/datepicker3.css" rel="stylesheet">
	<link href="css/styles.css" rel="stylesheet">
	
	<!--Custom Font-->
	<link href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
	<!--[if lt IE 9]>
	<script src="js/html5shiv.js"></script>
	<script src="js/respond.min.js"></script>
	<![endif]-->
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
				<li class="active">Expense</li>
			</ol>
		</div><!--/.row-->
		
		
				
		
		<div class="row">
			<div class="col-lg-12">
			
				
				
				<div class="panel panel-default">
					<div class="panel-heading">Expense</div>
					<div class="panel-body">
						<p style="font-size:16px; color:red" align="center"> <?php if($msg){
    echo e($msg);
  }  ?> </p>
						<div class="col-md-12">
							
							<div class="table-responsive">
            <table class="table table-bordered mg-b-0">
              <thead>
                <tr>
                  <th>S.NO</th>
                  <th>Expense Item</th>
                  <th>Expense Cost</th>
                  <th>Expense Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <?php
$userid = (int) $_SESSION['detsuid'];

// Pagination: show 10 rows at a time instead of the whole table.
$perPage = 10;
$total = (int) (db_row($con, "select count(*) as total from tblexpense where UserId = ?", 'i', [$userid])['total'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));

// ?page= must be a whole number; anything else falls back to the first page.
$page = (int) ($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
} elseif ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// ORDER BY ID keeps the rows in a stable order, so a row cannot appear on two
// pages or disappear between them. LIMIT/OFFSET are bound as integers too.
$rows = db_rows($con, "select * from tblexpense where UserId = ? order by ID limit ? offset ?",
                'iii', [$userid, $perPage, $offset]);
$cnt = $offset + 1;
?>
              <tbody>
              <?php if (!$rows) { ?>
                <tr><td colspan="5">No expenses recorded yet.</td></tr>
              <?php } ?>
              <?php foreach ($rows as $row) { ?>
                <tr>
                  <td><?php echo e($cnt);?></td>
              
                  <td><?php  echo e($row['ExpenseItem']);?></td>
                  <td><?php  echo e($row['ExpenseCost']);?></td>
                  <td><?php  echo e($row['ExpenseDate']);?></td>
                  <td>
                    <form method="post" action="manage-expense.php" style="margin:0">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="delid" value="<?php echo (int) $row['ID']; ?>">
                      <button type="submit" class="btn btn-link" style="padding:0">Delete</button>
                    </form>
                  </td>
                </tr>
                <?php $cnt = $cnt + 1; ?>
              <?php } ?>
              </tbody>
            </table>

            <?php if ($totalPages > 1) { ?>
            <nav>
              <ul class="pagination">
                <li<?php echo $page <= 1 ? ' class="disabled"' : ''; ?>>
                  <a href="manage-expense.php?page=<?php echo e($page - 1); ?>" aria-label="Previous">&laquo;</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++) { ?>
                <li<?php echo $p === $page ? ' class="active"' : ''; ?>>
                  <a href="manage-expense.php?page=<?php echo e($p); ?>"><?php echo e($p); ?></a>
                </li>
                <?php } ?>
                <li<?php echo $page >= $totalPages ? ' class="disabled"' : ''; ?>>
                  <a href="manage-expense.php?page=<?php echo e($page + 1); ?>" aria-label="Next">&raquo;</a>
                </li>
              </ul>
            </nav>
            <p class="text-muted">Page <?php echo e($page); ?> of <?php echo e($totalPages); ?> &middot; <?php echo e($total); ?> expense(s)</p>
            <?php } ?>
          </div>
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
