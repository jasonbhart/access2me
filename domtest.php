<?php

require_once __DIR__ . "/boot.php";

$db = new Database;

$key = 'AQVt-gDM8Qsgi_0h-Aj3tlECRVokoGQ6EXJrqqO7V_VHH-EmIStHO9lndwJdTYp8tlJbbYySWOBI-7mIpw_7hNs39RFHX4-FIf_7dMPx7iUNDcO9sALQKkWX2OWgGfyuRWVnLrPk2oHYQHQwuxpT3J27K6A_xX_HTdw-A6j3BLrSY45laa0';

$url  = "https://api.linkedin.com/v1/people/~:(";
$url .= "first-name,";
$url .= "last-name,";
$url .= "email-address,";
$url .= "headline,";
$url .= "industry,";
$url .= "picture-url,";
$url .= "site-standard-profile-request,";
$url .= "num-connections,";
$url .= "summary,";
$url .= "specialties,";
$url .= "associations,";
$url .= "interests,";
$url .= "num-recommenders,";
$url .= "recommendations-received,";
$url .= "phone-numbers,";
$url .= "im-accounts,";
$url .= "main-address,";
$url .= "twitter-accounts,";
$url .= "primary-twitter-account,";
$url .= "group-memberships,";
$url .= "positions,";
$url .= "location:(name))";
$url .= "?oauth2_access_token=" . $key;

$cURL = curl_init();

curl_setopt($cURL, CURLOPT_VERBOSE, true);
curl_setopt($cURL, CURLOPT_URL, $url);
curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($cURL, CURLOPT_HTTPGET, true);
curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($cURL);
curl_close($cURL);

$xml = new SimpleXMLElement($result);
$data = $xml->xpath('/person');

$contact['first_name'] = (isset($data[0]->{'first-name'})) ? (string) $data[0]->{'first-name'} : null;
$contact['last_name'] = (isset($data[0]->{'last-name'})) ? (string) $data[0]->{'last-name'} : null;
$contact['email'] = (isset($data[0]->{'email-address'})) ? (string) $data[0]->{'email-address'} : null;
$contact['headline'] = (isset($data[0]->{'headline'})) ? (string) $data[0]->{'headline'} : null;
$contact['picture_url'] = (isset($data[0]->{'picture-url'})) ? (string) $data[0]->{'picture-url'} : null;
$contact['profile_url'] = (isset($data[0]->{'site-standard-profile-request'}->{'url'})) ? (string) $data[0]->{'site-standard-profile-request'}->{'url'} : null;
$contact['location'] = (isset($data[0]->{'location'}->{'name'})) ? (string) $data[0]->{'location'}->{'name'} : null;
$contact['industry'] = (isset($data[0]->{'industry'})) ? (string) $data[0]->{'industry'} : null;
$contact['self_summary'] = (isset($data[0]->{'summary'})) ? (string) $data[0]->{'summary'} : null;
$contact['specialties'] = (isset($data[0]->{'specialties'})) ? (string) $data[0]->{'specialties'} : null;
$contact['associations'] = (isset($data[0]->{'associations'})) ? (string) $data[0]->{'associations'} : null;
$contact['interests'] = (isset($data[0]->{'interests'})) ? (string) $data[0]->{'interests'} : null;
$contact['total_connections'] = (isset($data[0]->{'num-connections'})) ? (string) $data[0]->{'num-connections'} : null;
$contact['total_positions'] = (isset($data[0]->{'positions'}->attributes()['total'][0])) ? (string) $data[0]->{'positions'}->attributes()['total'][0] : null;

for ($x = 0; $x < $contact['total_positions']; $x++) {
    $contact['positions'][$x]['company'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'company'}->{'name'};
    $contact['positions'][$x]['title'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'title'};
    $contact['positions'][$x]['summary'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'summary'};
    $contact['positions'][$x]['is_current'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'is-current'};
}

$append  = 'Access2.me has verified this contact. Their details are below:';
$append .= "<br /><br />";
$append .= '<img src="' . $contact['picture_url'] . '">';
$append .= "<br />";
$append .= "<strong>" . $contact['first_name'] . " " . $contact['last_name'] . "</strong>";
$append .= "<br />";
$append .= $contact['headline'] . " (" . $contact['industry'] . ")";
$append .= "<br />";
$append .= $contact['location'];
$append .= "<br /><br />";

?>


<html> <head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head> <body>
<table cellspacing="0" cellpadding="0" border="0" width="380"><tr><td><img src="<?php echo $contact['picture_url']; ?>" nosend="1" border="0" width="80" height="80" alt="photo"></td><td><table cellspacing="0" cellpadding="0" border="0" width="380"><tr><td><tr><td width="7"></td><td width="370" align="left"><a href="<?php echo $contact['profile_url']; ?>"><img src="http://access2.me/images/linkedin-22x17-bw.gif" nosend="1" border="0" width="22" height="17" alt="LinkedIn"></a><img src="http://www.stationerycentral.com/images/clear.gif" nosend="1" border="0" width="3" height="1"><span style="text-align: right; margin-top: 0px; color: #808080; font-size: 8pt; font-weight: bold; font-family: 'Calibri', sans-serif;"><font size="1" color="#808080">|</font>&nbsp; <a style="text-decoration: none; color: #808080" href="mailto:<?php echo $contact['email']; ?>"><font color="#808080">email</font></a></span></td><td width="10"><br></td><span style="line-height: 2px; font-size: 2px;">&nbsp;<br></span></tr></table><table cellspacing="0" cellpadding="0" border="0"><tr><td width="13"></td><td><span style="text-align: left; margin-top: 0px; color: #000000; font-size: 8pt; font-weight: normal; font-family: 'Arial', sans-serif;"><span style="text-align: left; color: #000000; font-family: Arial; font-size: 12pt; font-style: bold; font-weight: normal;"><?php echo $contact['first_name'] . " " . $contact['last_name']; ?><br /></span><span style="text-align: left; color: #000000; font-family: Arial; font-size: 8pt; font-style: normal; font-weight: normal;"><?php echo $contact['headline']; ?><br> <?php echo $contact['location']; ?></span></span></td></tr></td></tr></table></td></tr></table><table cellspacing="0" cellpadding="0" border="0"><tr><td style="padding-left: 0px;"><span style="color: #8A8A8A; font-family: 'Calibri', sans-serif; font-size: 8pt; font-weight: regular;"></span></td></tr></table><table cellspacing="0" cellpadding="0" border="0"><tr><td style="padding-left: 0px;"></td></tr></table></body><style> a {color: #808080;} </style></html>
<span style="background-color: #0000ff; font-weight: bold; color: #ffffff;">&nbsp;&nbsp;&nbsp;This contact has been verified by Access2.me&nbsp;&nbsp;&nbsp;</span>
<?php

echo "<pre>";
//echo $append;

echo "<br /><br />";
print_r($contact);
//print_r($data);
