<?php
if ( !function_exists('fsockopen') ) {
	wp_die(__('Function fsockopen() is not supported, please contact your webmaster.'));
}
else {
	function ats_request_by_socket($remote_server,$remote_path,$post_string,$port = 80,$timeout = 30){
		$socket = @fsockopen($remote_server,$port,$errno,$errmsg,$timeout);
		if (!$socket) wp_die(__('Something wrong when access to the Bing server.'));
		fwrite($socket,"POST $remote_path HTTP/1.0\r\n");
		fwrite($socket,"User-Agent: WordPress Auto Tag Slug Plugin\r\n");
		fwrite($socket,"HOST: $remote_server\r\n");
		fwrite($socket,"Content-Type: text/xml\r\n");
		fwrite($socket,"Content-Length: ".strlen($post_string)."\r\n");
		fwrite($socket,"Accept:*/*\r\n");
		fwrite($socket,"\r\n");
		fwrite($socket,"$post_string\r\n");
		fwrite($socket,"\r\n");
		$data = "";
		while (!feof($socket)) {
			$data .= fgets($socket,1024);
		}
		return $data;
	}
}

function ats_text_array_xml($array) {
	$str = '';
	foreach ($array as $text) {
		$str .= '<string xmlns="http://schemas.microsoft.com/2003/10/Serialization/Arrays">'. $text .'</string>';
	}
	return $str;
}

function ats_bing_translate($app_id, $array) {
	$array_slice = array_chunk($array, 200);
	$result_array = array();
	foreach ($array_slice as $array_200_items) {
		$xml = "<TranslateArrayRequest>" .
			"<AppId>$app_id</AppId>" .
			'<Texts>' .ats_text_array_xml($array_200_items) .'</Texts>'.
			'<To>en</To></TranslateArrayRequest>';
		$response = ats_request_by_socket('api.microsofttranslator.com', '/V2/Http.svc/TranslateArray', $xml);
		preg_match_all('/<TranslatedText>(.*?)<\/TranslatedText>/', $response, $matches);
		foreach($matches[1] as $result){
			$str = preg_replace('/[^a-z0-9- ]/i', '', $result);
			$str = str_replace(' ', '-', strtolower(trim($str)));
			$result_array[] = $str;
		}
	}
	return $result_array;
}
?>
