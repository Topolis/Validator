<?php

require "../vendor/autoload.php";

$contact = [
    "firstname" => "John",
    "lastname" => "Doe",
    "emails" => [
        "private" => "john.doe@condenast.de",
        "business" => "john.doe@condenast.de"
    ]
];

$validator = new \Topolis\Validator\Validator("./contact.yml", "./cache");

$result = $validator->validate($contact);