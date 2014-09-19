<?php

require_once __DIR__ . "/boot.php";

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title></title>

	<!-- Meta Tags -->
	<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
	<meta name="robots" content="index, follow" />

	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<meta name="author" content="" />

	<!-- CSS -->
	<link rel="stylesheet" href="css/style.css" media="screen,projection" type="text/css" />

</head>

<body>

	<div id="wrapper">

		<div id="container">

			<div id="content">

				<div class="top"></div>

				<div class="middle">

					<div class="post">

						<h2>Access to this Person is Denied!</h2>

						<div class="clear"></div>
					</div><!--post-->

					<div class="post">

						<h6>This person has chosen to restrict access to their e-mail address. In order to get on the
                            short list of qualified individuals who can make contact, please verify your identity
                            below.</h6>

						<ul>
							<li><a href="<?php echo $localUrl; ?>/linkedin.php?message_id=<?php echo $_GET['message_id']; ?>"><img src="images/linkedin.png"></a></li>
							<li><a href="<?php echo $localUrl; ?>/facebook.php?message_id=<?php echo $_GET['message_id']; ?>"><img src="images/facebook.png"></a></li>
						</ul>

						<div class="clear"></div>
					</div><!--post-->

					<div class="post2">

						<p>After verifying your identity, your profile information will be compared to the selections
                           this user has made about who can and cannot make contact. If your profile aligns with their
                           selected criteria, your email will be promptly delivered.</p>

						<div class="blue">

							<p>To protect yourself from unwanted emails, like this user has ... </p>
							<a href="#"><img src="images/button.png"/></a>

						</div><!--blue-->

					</div><!--post2-->

				</div><!--middle-->

				<div class="bottom"></div>

				<div class="clear"></div>
			</div><!-- content -->

		</div><!-- container -->

	</div><!-- wrapper -->

</body>
</html>