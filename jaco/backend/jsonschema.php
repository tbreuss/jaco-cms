<?php

declare(strict_types=1);

namespace jaco\jsonschema;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

function validate_data(array|string|null $data, string $schema): array
{
    $validator = new Validator();
    $validator->setMaxErrors(20);
    $validator->resolver()->registerPrefix('http://jaco.tebe.ch', dirname(__DIR__) . '/schema');

    if (is_array($data)) {
        $dataAsObject = json_decode(json_encode($data));
    } elseif(is_string($data)) {
        $dataAsObject = json_decode($data);
    } else {
        $dataAsObject = $data;
    }

    $result = $validator->validate($dataAsObject, 'http://jaco.tebe.ch' . $schema);

    if ($result->isValid()) {
        return [];
    }

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

    $errors = $formatter->format($result->error());

    $return = [];

    foreach ($errors as $key => $values) {
        foreach ($values as $value) {
            $return[] = [ltrim($key, '/'), $value];
        }
    }

    return $return;
}
