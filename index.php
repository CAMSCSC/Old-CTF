<?php

session_start();

?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>CAMS CTF</title>
		<link rel="stylesheet" href="styles/scoreboard.css" type="text/css" />
		<link rel="shortcut icon" href="http://www.camscsc.org/favicon.ico" />
		<link href='http://fonts.googleapis.com/css?family=Ubuntu+Mono' rel='stylesheet' type='text/css'>
		<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
		<script src="scripts/scoreboard.js"></script>
		<script src="scripts/typed.js"></script>
		<script>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create', 'UA-48668461-2', 'auto');ga('send', 'pageview');</script>
	</head>
	<body>
		<ul id="errors">
			<h1>Error</h1>
			<noscript>
				<li>You have to enable JavaScript!</li>
			</noscript>
		</ul>
		<div id="content">
			<div id="world">
				<div id="innercontent"></div>
				<canvas id="canvas"></canvas>
				<div id="menu">
					<img src="images/icons/challenges.png" id="challenges" alt="browser" title="Challenges" />
					<img src="images/icons/submit.png" id="submit" alt="submit" title="Attack" />
					<img src="images/icons/authenticate.png" id="authenticate" alt="authenticate" title="Authenticate" />
					<img src="images/icons/separator.png" class="separator" alt="separator" />
					<img src="images/icons/settings.png" class="right" id="terminal" alt="terminal" title="Terminal" />
					<img src="images/icons/close.png" class="right" id="close" alt="close" />
				</div>
			</div>
			<div id="sidebar">
				<div id="rankingframe">
					<div id="scrollable">
						<table id="ranking"></table>
					</div>
				</div>
				<div id="announcements">
					<div id="tickerframe"></div>
				</div>
			</div>
		</div>
	</body>
</html>
