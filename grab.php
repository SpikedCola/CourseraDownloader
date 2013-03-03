<?php
	/**
	 * Super quick script to download Coursera videos.
	 * 
	 * You don't need to be logged in or anything silly like that,
	 * just drop the /lecture/preview link in $page.
	 * 
	 * @author Jordan Skoblenick
	 */
	
	$page = 'https://class.coursera.org/neuralnets-2012-001/lecture/preview';
	$outputFolder = __DIR__.'/files/';
	
	libxml_use_internal_errors(true);
	$dom = new DOMDocument();
	$dom->loadHTMLFile($page);
	$xpath = new DOMXPath($dom);
	$itemListNodes = $xpath->query('//div[contains(@class,"course-item-list")]');
	if ($itemListNodes->length > 0) {
		$itemList = $itemListNodes->item(0);
		$headerNodes = $xpath->query('div[contains(@class,"course-item-list-header")]/h3', $itemList);
		$listNodes = $xpath->query('ul[contains(@class,"course-item-list-section-list")]', $itemList);
		if ($headerNodes->length == $listNodes->length) {
			for ($i = 0; $i < $headerNodes->length; $i++) {
				// remove unprintable shit, convert Lecture1 into Lecture 1
				$header = preg_replace('/Lecture(\d)/', 'Lecture $1', trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headerNodes->item($i)->nodeValue))) ;
				$listItems = $xpath->query('li/a', $listNodes->item($i));
				if ($listItems->length > 0) {
					foreach ($listItems as $idx => $item) {
						$videoTitle = trim(preg_replace('/([\[].*)$/', '', str_replace(array(':', '?'), '', $item->nodeValue)));
						$videoLink = $item->getAttribute('data-modal-iframe');
						$dom2 = new DOMDocument();
						$dom2->loadHTMLFile($videoLink);
						$xpath2 = new DOMXPath($dom2);
						$videoNodes = $xpath2->query('//source[@type="video/mp4"]');
						if ($videoNodes->length > 0) {
							if (!is_dir($outputFolder)) {
								mkdir($outputFolder);
							}
							$filename = $header.' - E'.($idx+1).' - '.$videoTitle;
							$fp = fopen($outputFolder.$filename.'.mp4', 'w');
							$ch = curl_init();
							curl_setopt_array($ch, array(
							    CURLOPT_NOPROGRESS => false,
							    CURLOPT_PROGRESSFUNCTION => 'progress',
							    CURLOPT_BUFFERSIZE => 64000,
							    CURLOPT_FILE => $fp,
							    CURLOPT_URL => $videoNodes->item(0)->getAttribute('src'),
							    CURLOPT_SSL_VERIFYPEER => false
							));
							echo 'Downloading '.$filename.'...'.PHP_EOL;
							curl_exec($ch);
							echo PHP_EOL;
						}
					}
				}
			}
		}
	}
	
	function progress($download_size, $downloaded) {
		$percent = 0;
		if ($download_size > 0) {
			$percent = ($downloaded/$download_size)*100;
		}
		
		$downloaded /= 1024*1024;
		$download_size /= 1024*1024;
		
		echo "\r".number_format($downloaded, 2).'MB / '.number_format($download_size, 2).'MB ('.number_format($percent, 3).'%)';
	}
?>