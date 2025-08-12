<?php declare(strict_types=1);

namespace jaco\core\json\schema;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

use const jaco\JSON_SCHEMA_URL;

function validate_data(array|string|null $data, string $schema): array
{
    $validator = new Validator();
    $validator->setMaxErrors(20);
    $validator->resolver()->registerPrefix(JSON_SCHEMA_URL, dirname(__DIR__, 3) . '/schema/');

    if (is_array($data)) {
        $dataAsObject = json_decode(json_encode($data), false);
    } elseif (is_string($data)) {
        $dataAsObject = json_decode($data, false);
    } else {
        $dataAsObject = $data;
    }

    $result = $validator->validate($dataAsObject, JSON_SCHEMA_URL . $schema);

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
                'pointer' => join('/', $error->data()->path()),
            ],
            'title' => 'Invalid data',
            'detail' => $formatter->formatErrorMessage($error, $customErrorMessage),
        ];
    };

    return array_values($formatter->format($error, false, $custom));
}
