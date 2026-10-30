<?php declare(strict_types=1);

namespace jaco\core\json;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

const JSON_SCHEMA_URL = 'https://jaco.tebe.ch';

/**
 * Validate data against a schema file below schema/.
 *
 * A missing schema is a broken installation, not an invalid request: it is logged
 * and reported through `$schemaError` so the caller answers 500, never 400. Silently
 * skipping the validation would let any request through unvalidated.
 *
 * @param string|null $schemaError receives the reason when the schema is unusable
 */
function validate_data(array|string|null $data, string $schema, ?string &$schemaError = null): array
{
    $schemaDir = dirname(__DIR__, 2) . '/schema/';
    $schemaFile = $schemaDir . $schema;

    if (!is_file($schemaFile)) {
        $schemaError = 'schema not found: ' . $schema;
        error_log('validate_data: ' . $schemaError);

        return [[
            'source' => ['pointer' => ''],
            'title' => 'Internal Server Error',
            'detail' => 'The request could not be validated on this installation.',
        ]];
    }

    $validator = new Validator();
    $validator->setMaxErrors(20);
    $validator->resolver()?->registerPrefix(JSON_SCHEMA_URL, $schemaDir);

    if (is_array($data)) {
        $encoded = json_encode($data);
        $dataAsObject = $encoded === false ? null : json_decode($encoded, false);
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
    if ($error === null) {
        return [];
    }

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
