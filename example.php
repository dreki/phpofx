#!/usr/bin/env php
<?
require_once "ofx.php";

use Seventymph\OFX\OFX;

$routing_number = "000000000";
$checking_acct_number = "1111222233";

$ofx = new OFX(array(
    "uri" => "https://www.oasis.cfree.com/3001.ofxgp",
    "user_id" => "123456",
    "password" => "1234",
    "org" => "Wells Fargo",
    "fid" => "3001",
    "bank_id" => $routing_number,
    "acct_id" => $checking_acct_number,
));

$transactions = $ofx->fetch();
