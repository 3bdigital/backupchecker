<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

function loadSettings($file) {
	if(!file_exists($file)) return array();
	$data = file_get_contents($file);
	if(!$data) return array();
	$json = json_decode($data);
	return $json;
}

function storeSettings($settings, $file) {
	file_put_contents($file, $settings);
}

function checkGmail($username, $password, $label = '') { 
	$url = "https://mail.google.com/mail/feed/atom"; 

	if($label) $url .= '/' . $label;

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $password);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_ENCODING, "");
	$mailData = curl_exec($curl);
	curl_close($curl);

	return $mailData;
}

$settingsFile = 'backupcheckersettings.json';

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// store settings
	$settings = array();
	$settings['username'] = $_POST['username'];
	$settings['password'] = $_POST['password'];
	$settings['days'] = $_POST['days'];
	$settings['nested'] = $_POST['nested'];
	$settings['labels'] = array();

	$labels = $_POST['labels'];
	$titles = $_POST['titles'];

	foreach($labels as $key => $value) {
		$settings['labels'][] = array('label' => $value, 'title' => $titles[$key], 'success' => array());
	}

	$json = json_encode($settings);

	storeSettings($json, $settingsFile);

	$success = true;
}

$settings = loadSettings($settingsFile);

$username = (isset($settings->username)) ? $settings->username : '';
$password = (isset($settings->password)) ? $settings->password : '';
$days = (isset($settings->days)) ? $settings->days : '';
$nestedLabel = (isset($settings->nested)) ? $settings->nested : '';
$labels = (isset($settings->labels)) ? $settings->labels : array();

$check = true;
$msg = '';

if(empty($username) || empty($password) || empty($days) || empty($nestedLabel)) {
	$check = false;
	$msg = 'Please check your settings';
}

// process the clients rss
foreach($labels as $key => $value) {
	$label = $value->label;
	if($nestedLabel) $label = $nestedLabel . '-' . $label;
	$feed = checkGmail($username, $password, $label);
	$xml = new SimpleXMLElement($feed);

	// check for valid XML
	if($xml) {
		// make sure there is some emails
		if($xml->fullcount > 0) {
			// grab all the email entries
			$entries = $xml->entry;
			// today at midnight
			$today = new DateTime(date('Y-m-d') . ' 23:59:59');
			$maxTime = 86400; // 24 hours in seconds
			for($i=0; $i<$days; $i++) {
				if(isset($entries[$i])) {
					$entry = $entries[$i];
					// move the base date
					$today->modify('-' . $i . ' days');
					// get the email date
					$emailDate = new DateTime($entry->issued);
					// compare the dates
					$diff = $today->getTimestamp() - $emailDate->getTimestamp();
					// if the difference is less than 24 hours then a backup has been run for that day
					if($diff < $maxTime) $labels[$key]->success[$i] = true;
				}
			}

		}
	} else {
		$check = false;
		$msg = 'Username or password is incorrect';
		break;
	}
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Backup Checker</title>

	<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap.min.css" rel="stylesheet">
	<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css" rel="stylesheet">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/js/bootstrap.min.js"></script>

	<style>
		.glyphicon-ok {
			color: #468847;
		}
		.glyphicon-remove {
			color: #b94a48;
		}
	</style>
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				$('#toggle-settings').click(function(){
					event.preventDefault();
					$('#settings').toggleClass('hide');
				});

				$('#add-label').click(function(){
					event.preventDefault();
					var html = '<div class="row"><div class="col-lg-3"><div class="form-group"><input type="text" class="form-control" name="labels[]"></div></div><div class="col-lg-3"><div class="form-group"><input type="text" class="form-control" name="titles[]"></div></div></div>';
					$('#labels').append(html);
				});
			});
		})(jQuery);
	</script>
  </head>

  <body>


    <div class="container">

    	<?php if(!$check): ?>
    	<br />
		<div class="alert alert-danger">
			<?php echo $msg; ?>
		</div>
    	<?php endif; ?>

    	<?php if($success): ?>
    	<br />
		<div class="alert alert-success">
			Settings saved
		</div>
    	<?php endif; ?>

		<h1>Backup Checker</h1>

		<p><button type="button" id="toggle-settings" class="btn btn-primary btn-xs">Toggle Settings</button></p>

		<div id="settings" class="well hide">
			<form role="form" action="" method="post">
				<div class="row">
					<div class="col-lg-3">
						<div class="form-group">
							<label for="username">Username</label>
							<input type="text" class="form-control" name="username" id="username" placeholder="Enter username" value="<?php echo $username; ?>">
							<span class="help-block">Your Gmail username or Google Apps email</span>
						</div>
					</div>
					<div class="col-lg-3">
						<div class="form-group">
							<label for="password">Password</label>
							<input type="password" class="form-control" name="password" id="password" placeholder="Enter password"  value="<?php echo $password; ?>">
							<span class="help-block">Your Gmail or Google Apps password</span>
						</div>
					</div>
					<div class="col-lg-3">
						<div class="form-group">
							<label for="password">Days</label>
							<select class="form-control" name="days" id="days">
								<?php for($i=1; $i<15; $i++): ?>
								<option value="<?php echo $i; ?>"<?php if($i == $days) echo ' selected="selected"'; ?>><?php echo $i; ?></option>
								<?php endfor; ?>
							</select>
							<span class="help-block">The number of days you would like to check</span>
						</div>
					</div>
					<div class="col-lg-3">
						<div class="form-group">
							<label for="nested">Nested Labels</label>
							<input type="text" class="form-control" name="nested" id="nested" placeholder="Enter nested label"  value="<?php echo $nestedLabel; ?>">
							<span class="help-block">If you have nested labels enter the parent here e.g. the nested label backup/site1 would be backup. 
								If you have multiple parents then separate with "-" e.g. backup-clients</span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3"><label>Labels</label></div>
					<div class="col-lg-3"><label>Display Title</label></div>
				</div>
				<span class="help-block">Add the labels with a title</span>
				<div id="labels">
					<?php foreach($labels as $label): ?>
					<div class="row">
						<div class="col-lg-3">
							<div class="form-group">
								<input type="text" class="form-control" name="labels[]" value="<?php echo $label->label; ?>">
							</div>
						</div>
						<div class="col-lg-3">
							<div class="form-group">
								<input type="text" class="form-control" name="titles[]" value="<?php echo $label->title; ?>">
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<p><button type="submit" id="add-label" class="btn btn-xs btn-default">Add Label</button></p>
				<button type="submit" class="btn btn-default btn-success">Submit</button>
			</form>
		</div>

      	<table class="table">
	        <thead>
	          <tr>
	            <th>#</th>
	            <th>Client</th>
	            <? for ($i=0; $i<$days; $i++): ?>
	            <th><?php echo date('j M', strtotime('-' . $i .' days')); ?></th>
	            <?php endfor; ?>
	          </tr>
	        </thead>
	        <tbody>
	          <?php foreach($labels as $label): ?>
	          <tr>
	            <td width="5%">1</td>
	            <td width="25%"><?php echo $label->title; ?></td>
	            <? for ($i=0; $i<$days; $i++): ?>
				<td width="<?php echo (70 / $days)  ?>%"><span class="glyphicon glyphicon-<?php echo (isset($label->success[$i])) ? 'ok' : 'remove' ?>"></span></td>
				<?php endfor; ?>
	          </tr>
	          <?php endforeach; ?>
	        </tbody>
      	</table>

    </div><!-- /.container -->
  </body>
</html>