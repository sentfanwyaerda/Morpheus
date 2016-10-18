<?php
ini_set('display_errors', 'On'); error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once('../Morpheus.php');

print '<pre>';

$morph = new Morpheus();

$morph->set_template(file_get_contents('template.mustache'));

$morph->name = 'Johnny Cash';
$morph->value = 5000;
$morph->repro = json_decode('[{ "name": "resque" },{ "name": "hub" },{ "name": "rip" }]', TRUE);

// class Morpheus { function taxed_value($value){ return round( $value * (1 / 1.21) , 2); } }

print_r($morph);

print "\n<hr/>\n".$morph;

print '</pre>';
?>