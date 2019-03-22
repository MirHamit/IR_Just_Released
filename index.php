<?php
set_time_limit(5000); // time limit for php execution
date_default_timezone_set('Asia/Tehran');
$date = date('H:i:s', time());

// get a api key from http://openrank.io and replace with this line
$openRankApiKey = 'Your API Key To OpenRank.io';

$message = NULL;
$domainNames = NULL;

// check for time break in nic.ir
if ((date('H:i') >= '23:00') || (date('H:i') < '00:30'))
{
	$message = 'time';
}
if (isset($_GET['maxchar']))
{
	//check for maxChar and to be numeric
	$maxChar = $_GET['maxchar'];
	if (!is_numeric($maxChar))
	{
		$message = 'maxcharError';
	}
}
if (isset($_GET['captcha']))
{
	$captcha = $_GET['captcha'];
	if (empty($captcha))
	{
		$message = 'captcha';
	}
	//get domains from url
	$pageContent = getPage('https://www.nic.ir/Just_Released?captcha=' . $_GET['captcha']);

	// search for domains with regex
	preg_match_all('/([a-z0-9-\.]+\.ir)/', $pageContent, $domainMatch);

	// delete extra static.nic.ir and ... from array
	$domainNames = array_unique($domainMatch[1]);

	//sort by lenght
	array_multisort(array_map('strlen', $domainNames), $domainNames);

	// delete these list of domains from array
	$itemsToDelete = array('static.nic.ir', 'whois.nic.ir', 'nic.ir');
	deleteElement($itemsToDelete, $domainNames);


	if (empty($domainNames))
	{
		$message = 'wrongCaptcha';
	}
}


function getPage($url) {
	//Check if the directory already exists.
	if (!is_dir('tmp'))
	{
		//Directory does not exist, so lets create it.
		mkdir('tmp', 0755);
	}

	$options = array(
		CURLOPT_RETURNTRANSFER => TRUE, // to return web page
		CURLOPT_HEADER         => FALSE, // to return headers in addition to content
		CURLOPT_FOLLOWLOCATION => TRUE, // to follow redirects
		CURLOPT_ENCODING       => "",   // to handle all encodings
		CURLOPT_AUTOREFERER    => TRUE, // to set referer on redirect
		CURLOPT_CONNECTTIMEOUT => 120,  // set a timeout on connect
		CURLOPT_TIMEOUT        => 120,  // set a timeout on response
		CURLOPT_MAXREDIRS      => 10,   // to stop after 10 redirects
		CURLINFO_HEADER_OUT    => TRUE, // no header out
		CURLOPT_SSL_VERIFYHOST => FALSE,
		CURLOPT_SSL_VERIFYPEER => FALSE,// to disable SSL Cert checks
		CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
		CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:65.0) Gecko/20100101 Firefox/65.0',
	);

	$handle = curl_init($url);
	curl_setopt_array($handle, $options);

	// additional for storing cookie
	$tempCookieName = dirname(__FILE__) . '/tmp/cookie.txt';
	curl_setopt($handle, CURLOPT_COOKIEJAR, $tempCookieName);
	curl_setopt($handle, CURLOPT_COOKIEFILE, $tempCookieName);

	$raw_content = curl_exec($handle);
	$err = curl_errno($handle);
	$errmsg = curl_error($handle);
	$header = curl_getinfo($handle);
	curl_close($handle);

	$header_content = substr($raw_content, 0, $header['header_size']);
	$body_content = trim(str_replace($header_content, '', $raw_content));

	// let's extract cookie from raw content for the viewing purpose
	$cookiepattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
	preg_match_all($cookiepattern, $header_content, $matches);
	$cookiesOut = implode("; ", $matches['cookie']);

	$header['errno'] = $err;
	$header['errmsg'] = $errmsg;
	$header['headers'] = $header_content;
	$header['content'] = $body_content;
	$header['cookies'] = $cookiesOut;

	//return $header;
	return $raw_content;
}

function deleteElement(array $element, &$array) {
	foreach ($element as $itemToDelete)
	{
		$index = array_search($itemToDelete, $array);
		if ($index !== FALSE)
		{
			unset($array[ $index ]);
		}
	}
}

function getDomainOr($domainName) {
	//$d = array('message' => $message , 'progress' => $progress);

	$openrankContent = getPage("https://api.openrank.io/?key=3+CugVeRlIvdiU/5RtVOttpIo5yN11EqA+5u7h1a4k0&d=$domainName&format=csv");
	$openRankDataArray = explode(',', $openrankContent);
	if ( ! empty($openRankDataArray[2])) {
		$domainOr = $openRankDataArray[2];
	} else {
		$domainOr = '-';
	}
	echo "<td style='direction: ltr;'>$domainOr</td>";

	ob_flush();
	flush();
}

$pageContent = getPage('http://www.nic.ir/Show_CAPTCHA');


$fh = fopen(dirname(__FILE__) . '/tmp/temp.jpg', 'w');
fwrite($fh, $pageContent);
fclose($fh);
?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
	<title>دامنه های آزاد شده</title>

	<base href="./">
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
	<meta name="description" content="دامنه های تازه آزاد شده IR">
	<meta name="author" content="Hamid Musavi">
	<meta name="keyword" content="دامنه های تازه آزاد شده IR">

	<!-- Main styles for this application-->
	<link href="assets/css/font-awesome.min.css" rel="stylesheet">
	<link href="assets/css/custom.css" rel="stylesheet">
	<link href="assets/css/style.css" rel="stylesheet">

	<!-- Necessery JS file for all pages-->
	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/js/bootstrap.min.js"></script>

</head>
<body class="app flex-row align-items-center">
<a href="javascript:" id="return-to-top"><i class="fa fa-chevron-up"></i></a>
<script>
	// ===== Scroll to Top ====
	$(window).scroll(function () {
		if ($(this).scrollTop() >= 50) {        // If page is scrolled more than 50px
			$('#return-to-top').fadeIn(200);    // Fade in the arrow
		} else {
			$('#return-to-top').fadeOut(200);   // Else fade out the arrow
		}
	});
	$('#return-to-top').click(function () {      // When arrow is clicked
		$('body,html').animate({
			scrollTop: 0                       // Scroll to top of body
		}, 500);
	});
</script>
<div class="container">
	<div class="row justify-content-center">
		<div class="col-md-8">
			<div class="card-group">
				<?php
				if ($message == 'time')
				{
					?>
					<div class="card p-4">
						<div class="card-body">
							<h1 class="text-muted text-center">کارگزار در دسترس نمی‌باشد</h1>
							<div class="text-center p-4">
								<p>سامانه ایرنیک به جهت عملیات روزانه هر شب از ساعت 23:00 لغایت 30 دقیقه بامداد در دسترس
									نمی‌باشد.</p>
							</div>
						</div>
					</div>
					<?php
				} else
				{
					?>
					<div class="card p-4">
						<form action="" method="<?php htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

							<div class="card-body">
								<p class="text-muted">کد زیر را وارد کرده و دکمه یافتن را فشار دهید</p>
								<?php
								if ($message == 'captcha')
								{
									echo '<p class="text-danger">کد نباید خالی باشد</p>';
								}
								if ($message == 'maxcharError')
								{
									echo '<p class="text-danger">حداکثر تعداد حروف باید عدد باشد !</p>';
								}
								if ($message == 'wrongCaptcha')
								{
									echo '<p class="text-danger">کد به درستی وارد نشده است</p>';
								}
								?>
								<div class="input-group mb-3">
									<div class="input-group-prepend">
										<img src="tmp/temp.jpg" alt="کد کپجا">
									</div>
								</div>
								<div class="form-group row">
									<label class="col-md-7 col-form-label" for="select1">حداکثر تعداد حروف</label>
									<div class="col-md-5">
										<label>
											<select class="form-control" name="maxchar">
												<?php
												for ($x = 3; $x <= 25; $x ++)
												{
													if ($x == 10)
													{
														echo '<option value="' . $x . '" selected> ' . $x . ' حرفی</option>';
													}
													echo '<option value="' . $x . '"> ' . $x . ' حرفی</option>';
												}
												?>
											</select>
										</label>
									</div>
								</div>
								<div class="input-group mb-4">
									<input class="form-control" autocomplete="off" name="captcha" type="text"
										   placeholder="کد تصویر">
								</div>
								<div class="row">
									<div class="col-6">
										<input class="btn btn-primary px-4" type="submit" value="یافتن">
									</div>
								</div>
							</div>
						</form>
					</div>
					<div class="card text-white bg-primary p-4">
						<div class="card-body text-right">
							<div>
								<h2 class="text-center">دامنه های تازه آزاد شده</h2>
								<p>دامنه‌های مرتبه دومی که برای تمدید آنها طی دو دورهٔ ۳۰ روزه پس از انقضاء اقدام نشود
									در این فهرست قرار می‌گیرند.</p>
								<p>دامنه‌های درخواستی تایید شده که در مهلت مقرر نسبت به پرداخت حق ثبت آنها اقدام نشود
									بلافاصله در این فهرست قرار می‌گیرند.</p>
								<p>دامنه‌های درخواستی که قبل از تایید نسبت به پرداخت حق ثبت آنها اقدام شود، در صورت
									تایید نشدن در این فهرست قرار نمی‌گیرند.</p>
							</div>
						</div>
					</div>
					<?php
				}
				?>

			</div>

			<div class="text-center m-auto">
				<?php

				if (!empty($domainNames))
				{
					echo "<div class=\"card-footer\">";
					echo "<table class=\"table table-responsive-md text-center table-bordered\">
					<thead>
					<tr>
						<th>#</th>
						<th>نام دامنه</th>
						<th><a href='http://openrank.io' target='_blank'>Open Rank</a></th>
					</tr>
					</thead>
					<tbody>";

					$domainNumber = 0;

					for ($i = 3; $i <= $maxChar; $i ++)
					{
						echo "<td colspan=\"3\" align=\"center\"> دامنه های $i حرفی</td>";
						echo '<tr">';
						echo '</tr>';
						foreach ($domainNames as $domains)
						{

							$domainLenght = strlen($domains) - 3;
							if ($domainLenght == $i)
							{

								$domainNumber ++;
								//echo "<td colspan=\"$domainLenght\" align=\"center\"> دامنه های $domainLenght حرفی</td>";

								echo '<tr>';
								echo "<td>$domainNumber</td>";
								echo "<td style='direction: ltr;'>$domains</td>";
								//$openrankContent = getPage("https://api.openrank.io/?key=$openRankApiKey&d=$domains&format=csv");
								//$openRankDataArray = explode(',', $openrankContent);
								echo getDomainOr($domains);
								//echo "<td>soon</td>";
								echo '</tr>';

							}
						}

					}
					echo "</div>";
				}

				?>

			</div>

		</div>
	</div>
	<footer class="footer fixed-bottom text-right mr-5">
		<div class="p-4">
			<a class="m-1" href="https://www.instagram.com/mirhamit/" target="_blank"><i
						class="fa fa-instagram fa-lg mt-4"></i></a>
			<a class="m-1" href="https://github.com/tabrizli" target="_blank"><i
						class="fa fa-github fa-lg mt-4"></i></a>
			<a class="m-1" href="https://t.me/murio" target="_blank"><i class="fa fa-telegram fa-lg mt-4"></i></a>
		</div>
	</footer>
</div>

</body>
</html>
<?php
// clear all vars and arrays
$vars = array_keys(get_defined_vars());
foreach ($vars as $var)
{
	unset(${"$var"});
}

