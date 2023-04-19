<?php

require 'vendor/autoload.php';

use jaco\jsonschema;

// Decode $data
$data = file_get_contents('schema/data.json');
$schema = 'schema/schema.json';

$result = jsonschema\validate_data($data, $schema);

echo"<pre>";print_r($result);echo"</pre>";
