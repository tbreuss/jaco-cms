<?php

require 'vendor/autoload.php';

use jaco\json;

// Decode $data
$data = file_get_contents('schema/data.json');
$schema = 'schema/schema.json';

$result = json\validate_data($data, $schema);

echo"<pre>";print_r($result);echo"</pre>";
