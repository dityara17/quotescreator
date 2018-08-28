<?php
include "function.php";

echo warna("
	  ___                      _          
	.'   `.                   / |_        
	/  .-.  \  __   _    .--. `| |-'.---.  
	| |   | | [  | | | / .'`\ \| | / /__\\ 
	\  `-'  \_ | \_/ |,| \__. || |,| \__., 
	`.___.\__|'.__.'_/ '.__.' \__/ '.__.' 

	","green");
echo "\n";


$folder = "./quotes/";
$overlay = "overlay.png";
$font_quote = realpath("GOGOIA-Regular.otf");
$font_copyright = realpath("Requited Script Demo.ttf");
$filename = $folder.md5(rand(000,999)).".png";
echo "Insert Quote :";
$quote = trim(fgets(STDIN));
echo "Copyright Quote :";
$copyright = trim(fgets(STDIN));
$backgrond = @$_POST['background'];

if (!filter_var($backgrond, FILTER_VALIDATE_URL) === false) {
	$bg = $backgrond;
}else {		
	$bg = get_redirect_target('https://source.unsplash.com/640x640/?'.urlencode($backgrond));
}

echo "Sedang Membuat Quote \n";

$image = new PHPImage();
$image->setQuality(10);
$image->setDimensionsFromImage($overlay);
$image->draw($bg);
$image->draw($overlay, '50%', '75%');
$image->setFont($font_quote);
$image->setTextColor(array(255, 255, 255));
$image->setAlignVertical('center');
$image->setAlignHorizontal('center');
$image->textBox($quote, array(
	'fontSize' => 100, 
	'x' => 130,
	'y' => 240,
	'width' => 380,
	'height' => 200,
	'debug' => false
	));

$image->setFont($font_copyright);
$image->setTextColor(array(230, 209, 65));	
$image->text('.:: '.$copyright." ::.", array(
	'fontSize' => 15, 
	'x' => 155,
	'y' => 535,
	'width' => 330,
	'height' => 20,
	'debug' => false
	));
$image->save($filename);

echo "Selesai Membuat Quote \n";
echo "\n";
echo "Apakah anda ingin mempostingnya ke Instagram ? (y/n) :";
$posttoig = trim(fgets(STDIN));

if ($posttoig == 'y') {

	echo "Post ke Akun Sebelumnya (jika sudah pernah masuk) atau Masuk Kembali (y/n) :";

	$pilihan = trim(fgets(STDIN));

	if (file_exists('cookies.json') AND $pilihan == 'y') {
		$cookies = file_get_contents('cookies.json');
	}elseif (file_exists('cookies.json') AND $pilihan == 'n') {
		echo "Login Instagram Untuk Mendapatkan Cookies \n\n";
		echo "Username :";
		$username = trim(fgets(STDIN));			
		echo "Password :";
		$password = trim(fgets(STDIN));

		$headers = array();
		$headers[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.75 Safari/537.36";
		$headers[] = "X-Csrftoken: ".get_csrftoken();
		$login = instagram(0, 0, 'https://www.instagram.com/accounts/login/ajax/', 0, "username={$username}&password={$password}&queryParams=%7B%7D",$headers);
		$header = $login[0];

		$login = json_decode($login[1]);
		if($login->authenticated == true){
			preg_match_all('%Set-Cookie: (.*?);%',$header,$d);
			$cookies = '';
			for($o=0;$o<count($d[0]);$o++){
				$cookies.=$d[1][$o].";";
			}
			$search  = ['csrftoken="";','target="";'];
			$cookies = str_replace($search,'', $cookies);

			file_put_contents('cookies.json', $cookies);

		}else {
			if ($login->user == true) {
				echo "Password Salah !";
				exit;
			}else {
				echo "Username atau Password Salah !";
				exit;
			}
		}
	}

	/**
	 *
	 * Deff Variable
	 *
	 */
	$generateUploadId = generateUploadId();
	$caption = $quote;
	$csrftoken = get_csrftoken_cookies($cookies);


	// Create jpg Format
	$newfile = $folder.$generateUploadId.".jpg";
	imagejpeg(imagecreatefromstring(file_get_contents($filename)), $newfile);
	unlink($filename);

	/**
	*
	* Curl to Post Image
	*
	*/		
	echo "Sedang Memposting Ke Instagram \n";

	$fields = array("upload_id"=>$generateUploadId, "media_type"=>"1");

	$filenames = array($newfile);;

	$files = array();
	foreach ($filenames as $f){
		$files[$f] = file_get_contents($f);
	}

	$boundary = uniqid();
	$delimiter = '----WebKitFormBoundary' . $boundary;

	$post_data = build_data_files($boundary, $fields, $files);

	$headers = array();
	$headers[] = "Cookie: ".$cookies;
	$headers[] = "X-Csrftoken: ".$csrftoken;
	$headers[] = "Content-Type: multipart/form-data; boundary=" . $delimiter;
	$headers[] = "Content-Length: " . strlen($post_data);

	$postimages = instagram(0, 0, "https://www.instagram.com/create/upload/photo/", 0, $post_data, $headers);


	/**
	*
	* Curl to Post Caption
	*
	*/

	$headers = array();
	$headers[] = "Cookie: ".$cookies;
	$headers[] = "X-Csrftoken: ".$csrftoken;
	$headers[] = "Content-Type: application/x-www-form-urlencoded";

	$postcaption = instagram(0, 0, "https://www.instagram.com/create/configure/", 0, "upload_id={$generateUploadId}&caption={$caption}&usertags=", $headers);

	$result =  json_decode($postcaption[1]);
	if (@$result->status == 'ok') {
		echo "\n\n";
		echo "Sukses Memposting Ke Instagram \n";
		exit;
	}else {
		echo "Gagal Memposting Ke Instagram \n";
		echo "Sepertinya Cookies Tidak Valid \n";
		exit;
	}
}else {
	echo "OK TerimaKasih \n";			
	exit;
}

?>