<html>
	<head>
	</head>
	<body>
<?php
	if (isset($_POST['humidity']) and isset($_POST['temperature'])) {
		$fp = fopen('humidity.json', 'w');
		fwrite($fp, json_encode($_POST));
		fclose($fp);
	}
?>		
	</body>
</html>
