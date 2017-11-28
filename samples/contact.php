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

$start = microtime(true);

for($i=0;$i<100;$i++){
    $validator = new \Topolis\Validator\Validator("./contact.yml", false); // Cached might take longer than uncached for simple schemas
    $result = $validator->validate($contact, true);
    $status = $validator->getStatus();
    $messages = $validator->getMessages();
}

echo "Time (x100): ".round(microtime(true) - $start,3)."s\n";

echo "Status: ".$validator->getStatus()."\n";

if($messages){
    echo "\nMessages:\n";
    foreach($messages as $message)
        echo " - ".$message["message"]." (".implode(".",$message["path"]).")\n";
}


echo "\nResult:\n";
print_r($result);

