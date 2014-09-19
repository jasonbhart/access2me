<?php

require_once __DIR__ . "/boot.php";

$db = new Database;

$filters = Filter::getFiltersByUserId('1', new Database);
echo serialize($filters);

die();


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

echo "<pre>";

echo "<br /><br />";
print_r($contact);
//print_r($data);