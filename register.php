<?php
require_once __DIR__ . '/includes/auth.php';
include('includes/dbconnection.php');
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/escape.php';
require_once __DIR__ . '/includes/validate.php';
require_once __DIR__ . '/includes/password.php';

// Shown in the red message line; empty means "nothing to report".
$msg = '';
if(isset($_POST['submit']))
  {
    csrf_check();
    $fname=trim($_POST['name'] ?? '');
    $mobno=trim($_POST['mobilenumber'] ?? '');
    $email=trim($_POST['email'] ?? '');
    $plainpassword=$_POST['password'] ?? '';
    $repeatpassword=$_POST['repeatpassword'] ?? '';

    // Validate before touching the database, and report the first problem only.
    if (!is_valid_name($fname)) {
      $msg="Please enter your full name.";
    } elseif (!is_valid_email($email)) {
      $msg="Please enter a valid email address.";
    } elseif (!is_valid_mobile($mobno)) {
      $msg="Please enter a 10-digit mobile number.";
    } elseif (strlen($plainpassword) < 8) {
      $msg="Your password must be at least 8 characters long.";
    } elseif ($plainpassword !== $repeatpassword) {
      // The form checks this in JavaScript; that check is a courtesy, not a
      // guarantee, because JavaScript can be turned off.
      $msg="Password and Repeat Password field does not match";
    } else {
    $password=hash_password($plainpassword);
    $result=db_row($con, "select Email from tbluser where Email = ?", 's', [$email]);
    if($result !== null){
$msg="This email  associated with another account";
    }
    else{
    $query=db_exec($con, "insert into tbluser(FullName, MobileNumber, Email, Password) values (?, ?, ?, ?)", 'ssss', [$fname, $mobno, $email, $password]);
    if ($query !== false) {
    $msg="You have successfully registered";
  }
  else
    {
      $msg="Something Went Wrong. Please try again";
    }
}
}
}

 ?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Daily Expense Tracker - Signup</title>
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/datepicker3.css" rel="stylesheet">
	<link href="css/styles.css" rel="stylesheet">
	<script type="text/javascript">
function checkpass()
{
if(document.signup.password.value!=document.signup.repeatpassword.value)
{
alert('Password and Repeat Password field does not match');
document.signup.repeatpassword.focus();
return false;
}
return true;
} 

</script>
<body>
	<div class="row">
			<h2 align="center">Daily Expense Tracker</h2>
	<hr />
		<div class="col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
			<div class="login-panel panel panel-default">
				<div class="panel-heading">Sign Up</div>
				<div class="panel-body">
					<form role="form" action="" method="post" id="" name="signup" onsubmit="return checkpass();">
							<?php echo csrf_field(); ?>
						<p style="font-size:16px; color:red" align="center"> <?php if($msg){
    echo e($msg);
  }  ?> </p>
						<fieldset>
							<div class="form-group">
								<input class="form-control" placeholder="Full Name" name="name" type="text" required="true">
							</div>
							<div class="form-group">
								<input class="form-control" placeholder="E-mail" name="email" type="email" required="true">
							</div>
							<div class="form-group">
								<input type="text" class="form-control" id="mobilenumber" name="mobilenumber" placeholder="Mobile Number" maxlength="10" pattern="[0-9]{10}" required="true">
							</div>
							<div class="form-group">
								<input class="form-control" placeholder="Password" name="password" type="password" value="" required="true">
							</div>
							<div class="form-group">
								<input type="password" class="form-control" id="repeatpassword" name="repeatpassword" placeholder="Repeat Password" required="true">
							</div>
							<div class="checkbox">
								<button type="submit" value="submit" name="submit" class="btn btn-primary">Register</button><span style="padding-left:250px">
								<a href="index.php" class="btn btn-primary">Login</a></span>
							</div>
							 </fieldset>
					</form>
				</div>
			</div>
		</div><!-- /.col-->
	</div><!-- /.row -->	
	

<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
</body>
</html>
