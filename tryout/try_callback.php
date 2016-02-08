<html>
<head>
<title>Key-Simulation</title>
</head>
</html>
<body>
<h1>Key-Simulation</h1>

<form action="<?php echo $_GET["log_url"] ?>" target="LogResult" method="get">

<table>
<tr>
	<td>ref_id:</td>
	<td><input type="text" size="80" name="ref_id" value="<?php echo $_GET["ref_id"] ?>" /></td>
</tr>
<tr>
	<td>session_id:</td>
	<td><input type="text" size="80" name="session_id" value="<?php echo $_GET["session_id"] ?>" /></td>
</tr>
<tr>
	<td>call_time:</td>
	<td><input type="text" size="80" name="call_time" value="<?php echo $_GET["call_time"] ?>" /></td>
</tr>
<tr>
	<td>event_type:</td>
	<td><input type="text" size="80" name="event_type" value="" /></td>
</tr>
<tr>
	<td>event_subtype:</td>
	<td><input type="text" size="80" name="event_subtype" value="" /></td>
</tr>
<tr>
	<td>event_integer:</td>
	<td><input type="text" size="80" name="event_integer" value="" /></td>
</tr>
<tr>
	<td>event_text:</td>
	<td><input type="text" size="80" name="event_text" value="" /></td>
</tr>
</table>

<input type="submit" value="Senden" />
</form>

</body>
</html>
