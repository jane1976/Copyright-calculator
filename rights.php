<?php
/*
 * @author Jane Makke <jane.makke@ebs.ee>
 * @copyright 2019 Jane Makke (Business Applications of Technologies (IntMBA-2))
 * @version 0.1
 * @example https://your.server.com/rights.php?URL=https://www.ester.ee:444/record=b1355887~S1&lang=en&debu
 * @todo Sierra query by ISBN number 
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require('Sierra.php'); //Sierra API rest client

//Sierra REST API URL, API Key and Password
$api = new Sierra(array(
    'endpoint' => 'https://ester.ester.ee:443/iii/sierra-api/v5/',
    'key' => 'SecureKeyFromSierra',
    'secret' => 'PasswordString'
));

$max_year = 0;
$message ='';

//all requests are logged into log file with date, IP and request parameters
$fp = file_put_contents('access.log', date("Y-m-d H:i")." - IP:".$_SERVER['REMOTE_ADDR']." - URL: ".$hosting_server.$_SERVER['REQUEST_URI'], FILE_APPEND);

//check URL parameter exist in browser 
if (isset($_GET['URL'])){
	$url = $_SERVER['REQUEST_URI']; // from server created array containing URL information
	$url = substr($url,strpos($url,'URL=')+4); //extracting beginning part of URL=
	$array = preg_split('/=/',$url); //spliting URL to array
	$server = $array[0]; // fist element of array are server name
	$recno = substr($array[1],1,7); // second part is record number

	$res = $api->query('bibs/'.$recno.'/marc', array(), true); //API Call to Sierra with record number
	//API call for ISBN -> https://ester.ester.ee:443/iii/sierra-api/v5/bibs/search?limit=1&text=123456789012

	$leader = substr($res['leader'],7,1);
	
	//must be book, note, map, video, sound marc leader position 07 must be 'm'
	if ($leader == 'm') {
	$autors = $res['fields'];
	foreach ($autors as $key => $value) {
//    		print_r($value);
		
		//get title, author and year from marc21
		$author = $author.$value['100']['subfields'][0]['a'];
		$title = $title.ltrim($value['245']['subfields'][0]['a'].' '.$value['245']['subfields'][1]['c'].$value['245']['subfields'][2]['c']);
		$year = str_replace(']','',str_replace('[','',str_replace('c','',$year.$value['260']['subfields'][0]['c'].$value['260']['subfields'][1]['c'].$value['260']['subfields'][2]['c'])));

		//get marc field 100 (author name) etc.
		$marc100 = $value['100']['subfields'][0]['a'];
		$marc700 = $value['700']['subfields'][0]['a'];
		$organization = $organization.$value['110']['subfields'][0]['a'];
                $organization = $organization.$value['710']['subfields'][0]['a'];
		$free_of_use = $free_of_use.$value['542']['subfields'][0]['l'];
		
		$author100_dead_date = $value['100']['subfields'][0]['d'].$value['100']['subfields'][1]['d'].$value['100']['subfields'][2]['d'].$value['100']['subfields'][3]['d'];
		$author100_dead_date = str_replace(',','',str_replace('.','',preg_replace('~.*?-~', '', $author100_dead_date))); //remove first date and all commas and dots
		
		$author700_dead_date = $value['700']['subfields'][0]['d'].$value['700']['subfields'][1]['d'].$value['700']['subfields'][2]['d'].$value['700']['subfields'][3]['d'];
		$author700_dead_date = str_replace(',','',str_replace('.','',preg_replace('~.*?-~', '', $author700_dead_date))); //remove first date and all commas and dots
		
		// author must be dead at least 71 years and all authors must be dead
		if (($author100_dead_date == '' and $marc100 != '') || ($author700_dead_date == '' and $marc700 != '')){
			 $not_dead = 'Live';
		}else{
			if ($max_year < $author100_dead_date +71) {$max_year = $author100_dead_date +71;}
                        if ($max_year < $author700_dead_date +71) {$max_year = $author700_dead_date +71;}
		}
		//pseudo or organization then take  publishing date+71 years - not done
		//if () { }

		if ($marc100 != '') {
        	        $a100 = $a100.'</br>'.$marc100." - ".$author100_dead_date ;}
		if ($marc700 != '') {
                        $a700 = $a700.'</br>'.$marc700." - ".$author700_dead_date ;}
		if ($organization != '') {
			 if ($max_year < $year+71) {$max_year = $year +71;}
		}
	}//end foreach	
	
	//messages to user
	if ($free_of_use == 'Vabakasutus'){
		$message = 'The work is in public domain <a href="https://creativecommons.org/publicdomain/mark/1.0/">https://creativecommons.org/publicdomain/mark/1.0/</a>';
	}else{
 		if ($free_of_use == 'Orbteos') {
			$message = 'This is an Orphan Work <a href="http://rightsstatements.org/vocab/InC-OW-EU/1.0/">http://rightsstatements.org/vocab/InC-OW-EU/1.0/</a>';
		}else{
			if ($free_of_use == 'Osaline orbteos') {
                        	$message = 'This is a Partial Orphan Work <a href="http://rightsstatements.org/vocab/InC-OW-EU/1.0/">http://rightsstatements.org/vocab/InC-OW-EU/1.0/</a>';
                	}else{
			if ($not_dead == 'Live'){
				$message = "Due to lack of data it is not possible to calculate the estimated copyright expiration date."; 		
			}else{
			if ($max_year > 2019) {	
				$message =  "Estimated copyright expiration date: 01.01.".$max_year;
			}else{
				$message = 'The work is in public domain, <a href="https://creativecommons.org/publicdomain/mark/1.0/">https://creativecommons.org/publicdomain/mark/1.0/</a>';
			}//endif
			}//endif Osaline Orbteos
		}//endif Orbteos
		}//endif Live
	}//endif free_of_use
	}//endif leader

//default output is html but if XML tag is presented in ULR the response will be XML
// in this server can't implement real XML service because of server parser send html tags and scrpts
if (isset($_GET['xml'])){
	$response = '<?xml version="1.0" encoding="utf-8">'."\n";
        $response .= '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">'."\n";
	$response .= '<dc:creator>'.$author.'</dc:creator>'."\n";
	$response .= '<dc:title>'.$title.'</dc:tilte>'."\n";
	$response .= '<dc:date>'.$year.'</dc:date>'."\n";
	$response .= '<dc:rights>'.$message.'</dc:rights>'."\n";
	$response .= '</metadata>'."\n";

//	next 2 lines must be commented in when using real XML service and removed third line
//	header('Content-Type: application/xml; charset=utf-8');
//	echo ($response);
	echo '<pre>' . htmlspecialchars($response) . '</pre>';
}else{
// beginning of html output	
	$html = '<html><head><title>Calculate rights</title>
		<link rel="stylesheet" href="rights.css" type="text/css">
		</head><body>';
	echo $html.'<div class="container"><span>';
	echo "<center><h2>COPYRIGHT EXPIRATION DATE*</h2></center>";
	        //debug mode on
                if (isset($_GET['debug'])){
                        echo '<ul><div class ="debug"><b>Debug info:</b></br>';
                        echo 'Full parameters from URL: '.$url.'</br>';
                        //echo 'Query from server: '.$server.'</br>';
                        //echo 'Record number from system: '.$recno.'</br>';
                        echo 'AUTHORs from MARC 100 record: '.$a100.'</br>';
                        echo 'AUTHORs from MARC 700 record: '.$a700.'</br>';
                        //echo 'some of authors is:'.$not_dead.'</br>';
                      	//echo 'Get organization as an author through the API: '.$organization.'</br>';
                        echo 'Orphan work value:'.$free_of_use.'</br>';
                        echo 'Max Year:'.$max_year.'</div></ul>';
                }


		if ($leader != 'm'){ 
			$message = 'For this type of work it is not possible to calculate the copyright expiration date.';
		}else{
			echo "<ul><h3>";
			if ($author != ''){ echo "<p><i>Author: </i> ".$author."</p>";}
			echo "<i>Title: </i>".$title."</br>";
			if ($year != '') { echo "<p><i>Publishing year: </i>".$year."</p>";}
			echo "</h3></ul>";  
		}
	echo '<center><h1><i>'.$message.'</i></h1></center>';
	echo '<center><a href="https://www.elnet.ee/estermeil/rights.php?URL='.$url.'&debug">View calcualtion</a></center>';
	echo "</span></div>";
	echo "<center><i>*Disclaimer: The copyright expiration date is an estimation, calculated by the artificial intelligence using the data that is available in the bibliographic records.</br> The National Library of Estonia nor the ELNET Consortium do not take any responsibility for the consequences that may arise from the use of this calculator.</i></center>";
	//echo "</span></div>";
	echo "</body></html>";
}	
//save result also to log file
$fp = file_put_contents('access.log',' - '.$message."\n" , FILE_APPEND);
}//endif URL
?>
