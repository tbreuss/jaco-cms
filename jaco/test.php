<?php

require 'vendor/autoload.php';

use Opis\JsonSchema\{Errors\ValidationError, JsonPointer, Validator, ValidationResult, Errors\ErrorFormatter};

// Create a new validator
$validator = new Validator();
$validator->setMaxErrors(5);

// Register our schema
$validator->resolver()->registerFile(
    'http://api.example.com/profile.json',
    '/path/to/schema.json'
);

// Decode $data
$data = json_decode(file_get_contents('schema/data.json'));
$schema = json_decode(file_get_contents('schema/schema.json'));

/** @var ValidationResult $result */
$result = $validator->validate($data, $schema);

if ($result->isValid()) {
    echo "Valid", PHP_EOL;
} else {
    // Get the error
    $error = $result->error();

    // Create an error formatter
    $formatter = new ErrorFormatter();

    // Print helper
    $print = function ($value) {
        echo json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ), PHP_EOL;
        echo '-----------', PHP_EOL;
    };

    // default
    #$print($formatter->format($error, false));

    // default - multiple
    #$print($formatter->format($error, true));

    $custom = function (ValidationError $error) use ($formatter) {
        $schema = $error->schema()->info();

        return [
            'source' => [
                'pointer' => join('/', $error->data()->path())
            ],
            'title' => 'Validation Error',
            'detail' => $formatter->formatErrorMessage($error),
            /*
            'schema' => [
                'id' => $schema->id(),
                'base' => $schema->base(),
                'root' => $schema->root(),
                'draft' => $schema->draft(),
                'path' => JsonPointer::pathToFragment($schema->path()),
                'contents' => $schema->data(),
                // see Opis\JsonSchema\Info\SchemaInfo for more properties
            ],
            'error' => [
                'keyword' => $error->keyword(),
                'args' => $error->args(),
                'message' => $error->message(),
                'formattedMessage' => $formatter->formatErrorMessage($error),
            ],
            'data' => [
                'type' => $error->data()->type(),
                'value' => $error->data()->value(),
                'fullPath' => $error->data()->fullPath(),
                // see Opis\JsonSchema\Info\DataInfo for more properties
            ],
            */
        ];
    };

    $custom_key = function (ValidationError $error): string {
        return implode('.', $error->data()->fullPath());
    };

    $print($formatter->formatFlat($error, $custom));

    // custom
    $print(array_values($formatter->format($error, false, $custom)));

    // custom - multiple
    $print($formatter->formatOutput($error, 'verbose'));

    exit;


    $print($formatter->formatOutput($error, "flag"));
    $print($formatter->formatOutput($error, "basic"));
    $print($formatter->formatOutput($error, "detailed"));
    $print($formatter->formatOutput($error, "verbose"));

    // default
    $print($formatter->formatNested($error));

    $custom2 = function (ValidationError $error, ?array $subErrors = null) use ($formatter) {
        $ret = [
            'keyword' => $error->keyword(),
            'message' => $formatter->formatErrorMessage($error),
        ];

        if ($subErrors) {
            $ret['subErrors'] = $subErrors;
        }

        return $ret;
    };

    // custom
    $print($formatter->format($error, $custom2));
}
