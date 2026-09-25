<!DOCTYPE html>
<html>
<head>
	<title>Login Page</title>

	<!--ORIGINAL SOURCES
	<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<!------ Include the above in your HEAD tag ---------->
  	<link href="/extern/4.1.1-bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<!--<script src="/extern/4.1.1-bootstrap.min.js"></script>-->
	<script src="/extern/3.5.1.jquery.min.js"></script>
	
	<!--Custom styles-->
	<link rel="stylesheet" type="text/css" href="/css/login.css?v0.2">

<script>
function saveLogin()
{
	if($("#remember:checkbox:checked").length)
	{
		localStorage.setItem("login", JSON.stringify({username:$("#username").val(),password:$("#password").val()}));
	}
	else
	{
		localStorage.removeItem("login");
	}
}

$( document ).ready(function() {
    
	let login = localStorage.getItem("login");
	if(login != null)
	{
		login = JSON.parse(login);
		$("#username").val(login.username);
		$("#password").val(login.password);
	}
});
</script>
</head>
<body>
<div class="container">
	<div class="d-flex justify-content-center h-100">
		<div class="card">
			<div class="card-header">
				<h3>Autentificare</h3>
				<div class="d-flex justify-content-end social_icon">
					<span><img src="/login-logo/logo-S.C. HERMES ENERGY INTERNATIONAL S.R.L..png" style="width:60px;float:right;background:white;border-radius:5%"></span>
					<!--<span><img src="http://cloudromania.ro/ebs/logo-S.C. CONARG REAL ESTATE S.R.L..png" style="width:60px;float:right;background:white;border-radius:5%"></span>-->
				</div>
			</div>
			<div class="card-body">
				<form action="<?php echo base_url('Login/signin');?>" name="ajax_form" id="ajax_form" method="post" accept-charset="utf-8" enctype="multipart/form-data">
					<div class="input-group form-group">
						<div class="input-group-prepend">
							<span class="input-group-text"><i class="fas fa-user"></i></span>
						</div>
						<input type="text" class="form-control" placeholder="Utilizator" id="username" name="username">
						
					</div>
					<div class="input-group form-group">
						<div class="input-group-prepend">
							<span class="input-group-text"><i class="fas fa-key"></i></span>
						</div>
						<input type="password" class="form-control" placeholder="Parola" id="password" name="password">
					</div>
					<?php 
						if (session()->getFlashdata('error')): ?> 
						<div class="input-group form-group">
						<p style="color: white; text-shadow: 2px 2px red;animation: shake 0.25s linear 1;">
						<?php echo session()->getFlashdata('error'); ?>
						</p> 
						</div>
					<?php endif; ?>
					<div class="row align-items-center remember">
						<input type="checkbox"  id="remember" name="remember" checked>Remember Me
					</div>
					<div class="form-group">
						<input type="submit" value="Login" class="btn float-right login_btn" onclick="saveLogin()">
					</div>					
				</form>
			</div>
			<div class="card-footer">
				<div class="d-flex justify-content-center links">
					Energy Billing System
				</div>
				<div class="d-flex justify-content-center">
					<a href="mailto:office@hermes.ro">office@hermes.ro</a>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>