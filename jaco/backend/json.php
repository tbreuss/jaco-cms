<?php

declare(strict_types=1);

namespace jaco\json;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

function validate_data(array|string $data, string $schema): array
{
    $validator = new Validator();
    $validator->setMaxErrors(20);

    if (is_array($data)) {
        $dataAsObject = json_decode(json_encode($data));
    } else {
        $dataAsObject = json_decode($data);
    }

    if (is_file($schema)) {
        $schema = file_get_contents($schema);
    }

    $result = $validator->validate($dataAsObject, $schema);

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
            'title' => 'Validation Error',
            'detail' => $formatter->formatErrorMessage($error, $customErrorMessage),
        ];
    };

    $test = $formatter->formatFlat($error);

    return array_values($formatter->format($error, false, $custom));
}

function to_object(array $arr) {
    if (is_array($arr)) {
        // Return object
        return (object) array_map('toObject', $arr);
    }
    return false;
}
