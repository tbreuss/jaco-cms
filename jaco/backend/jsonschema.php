<?php

declare(strict_types=1);

namespace jaco\jsonschema;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

function validate_data(array|string $data, string $schema): array
{
    $validator = new Validator();
    $validator->setMaxErrors(20);
    $validator->resolver()->registerPrefix('http://jaco.tebe.ch', dirname(__DIR__) . '/schema');
    //$validator->resolver()->registerFile('http://jaco.tebe.ch' . $schema, dirname(__DIR__) . '/schema' . $schema);


    if (is_array($data)) {
        $dataAsObject = json_decode(json_encode($data));
    } else {
        $dataAsObject = json_decode($data);
    }

    if (is_file($schema)) {
        //$schema = file_get_contents($schema);
    }

    $result = $validator->validate($dataAsObject, 'http://jaco.tebe.ch' . $schema);

    if ($result->isValid()) {
        return [];
    }

    $x = $result->error()->data();

    $error = $result->error();

    $formatter = new ErrorFormatter();

    $custom = function (ValidationError $error) use ($formatter) {
        $keyword = $error->keyword();
        $customError = $error->schema()->info()->data()->{'$error'} ?? null;
        $customErrorMessage = null;
        if ($customError) {
            $customErrorMessage = $customError->$keyword ?? null;
        }
        return [
            'source' => [
                'pointer' => join('/', $error->data()->path())
            ],
            'title' => 'Invalid input',
            'detail' => $formatter->formatErrorMessage($error, $customErrorMessage),
        ];
    };

    $test = $formatter->formatFlat($error);

    return array_values($formatter->format($error, false, $custom));
}

function validate_service(array $data, string $schema): array
{
    $validator = new Validator();
    $validator->setMaxErrors(20);
    $validator->resolver()->registerPrefix('http://jaco.tebe.ch', dirname(__DIR__) . '/schema');

    $dataAsObject = json_decode(json_encode($data));

    $result = $validator->validate($dataAsObject, 'http://jaco.tebe.ch' . $schema);

    if ($result->isValid()) {
        return [];
    }

    $formatter = new ErrorFormatter();

    $errors = $formatter->format($result->error(), true);

    $return = [];

    foreach ($errors as $key => $values) {
        foreach ($values as $value) {
            $return[] = [ltrim($key, '/'), $value];
        }
    }

    return $return;
}

function to_object(array $arr) {
    if (is_array($arr)) {
        // Return object
        return (object) array_map('toObject', $arr);
    }
    return false;
}
