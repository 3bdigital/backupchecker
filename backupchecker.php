<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

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

$username = ''; // google username
$password = ''; // google password
$days = 7; // the amount of days to check
$nestedLabel = ''; // if the label is nested add the parent labels ending with "-" e.g. the nested label backup/example would be "backup-"

$clients = array(
	'bnms' 				=> array('name' => 'BNMS', 'success' => array()),
	'3b' 				=> array('name' => '3B', 'success' => array()),
	'siliconjunction' 	=> array('name' => 'Silicon Junction', 'success' => array()),
	'oldbrightonians' 	=> array('name' => 'Old Brightonians', 'success' => array()),
	'rapidaddition' 	=> array('name' => 'Rapid Addition', 'success' => array()),
	'crosfields' 		=> array('name' => 'Crosfields', 'success' => array()),
	'fepsa' 			=> array('name' => 'FESPA', 'success' => array()),
	'marshallarts' 		=> array('name' => 'Marshall Arts', 'success' => array()),
);

// process the clients rss
// foreach($clients as $key => $value) {
// 	$label = $key;
// 	if($nestedLabel) $label = $nestedLabel . $label;
// 	$feed = checkGmail($username, $password, $label);
// 	$xml = new SimpleXMLElement($feed);

// 	// check for valid XML
// 	if($xml) {
// 		// make sure there is some emails
// 		if($xml->fullcount > 0) {
// 			// grab all the email entries
// 			$entries = $xml->entry;
// 			// today at midnight
// 			$today = new DateTime(date('Y-m-d') . ' 23:59:59');
// 			$maxTime = 86400; // 24 hours in seconds
// 			for($i=0; $i<$days; $i++) {
// 				if(isset($entries[$i])) {
// 					$entry = $entries[$i];
// 					// move the base date
// 					$today->modify('-' . $i . ' days');
// 					// get the email date
// 					$emailDate = new DateTime($entry->issued);
// 					// compare the dates
// 					$diff = $today->getTimestamp() - $emailDate->getTimestamp();
// 					// if the difference is less than 24 hours then a backup has been run for that day
// 					if($diff < $maxTime) $clients[$key]['success'][$i] = true;
// 				}
// 			}

// 		}
// 	}
// }

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
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/js/bootstrap.min.js"></script>
	<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css" rel="stylesheet">

	<style>
		.glyphicon-ok {
			color: #468847;
		}
		.glyphicon-remove {
			color: #b94a48;
		}
	</style>
  </head>

  <body>


    <div class="container">

		<h1>Backup Checker</h1>

		<div class="well">
			<form role="form" action="" method="post">
				<div class="row">
					<div class="col-lg-3">
						<div class="form-group">
							<label for="username">Username</label>
							<input type="text" class="form-control" name="username" id="username" placeholder="Enter username">
						</div>
					</div>
					<div class="col-lg-3">
						<div class="form-group">
							<label for="password">Password</label>
							<input type="text" class="form-control" name="password" id="password" placeholder="Enter password">
						</div>
					</div>
					<div class="col-lg-3">
						<div class="form-group">
							<label for="password">Days</label>
							<select class="form-control" name="days" id="days">
								<?php for($i=1; $i<15; $i++): ?>
								<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
								<?php endfor; ?>
							</select>
						</div>
					</div>
					<div class="col-lg-3">
						<div class="form-group">
							<label for="nested">Nested Labels</label>
							<input type="text" class="form-control" name="nested" id="nested" placeholder="Enter nested label">
						</div>
					</div>
				</div>
				<label>Labels</label>
				<div id="labels">
					<div class="row">
						<div class="col-lg-3">
							<div class="form-group">
								<input type="text" class="form-control" name="labels[]">
							</div>
						</div>
						<div class="col-lg-3">
							<div class="form-group">
								<input type="text" class="form-control" name="titles[]">
							</div>
						</div>
					</div>
				</div>
				<p><button type="submit" class="btn btn-xs btn-default">Add Label</button></p>
				<button type="submit" class="btn btn-default btn-success">Submit</button>
			</form>
		</div>

      	<table class="table">
	        <thead>
	          <tr>
	            <th>#</th>
	            <th>Client</th>
	            <? for ($i=0; $i<$days; $i++): ?>
	            <th><?php echo date('d-m-Y', strtotime('-' . $i .' days')); ?></th>
	            <?php endfor; ?>
	          </tr>
	        </thead>
	        <tbody>
	          <?php foreach($clients as $client): ?>
	          <tr>
	            <td width="5%">1</td>
	            <td width="25%"><?php echo $client['name']; ?></td>
	            <? for ($i=0; $i<$days; $i++): ?>
				<td width="10%"><span class="glyphicon glyphicon-<?php echo (isset($client['success'][$i])) ? 'ok' : 'remove' ?>"></span></td>
				<?php endfor; ?>
	          </tr>
	          <?php endforeach; ?>
	        </tbody>
      	</table>

    </div><!-- /.container -->
  </body>
</html>