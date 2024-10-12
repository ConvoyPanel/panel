<?php

namespace App\Extensions\Spatie\Fractal;

use League\Fractal\Serializer\ArraySerializer;

class RecursiveSerializer extends ArraySerializer
{
    /**
     * {@inheritDoc}
     */
    public function collection(?string $resourceKey, array $data): array
    {
        return ['data' => $data];
    }

    /**
     * {@inheritDoc}
     */
    public function item(?string $resourceKey, array $data): array
    {
        return ['data' => $data];
    }

    /**
     * Recursively serialize included data
     *
     * @param string $resourceKey
     * @param array $data
     * @param array $includedData
     * @return array
     */
    public function recursiveInclude($resourceKey, array $data, array $includedData): array
    {
        foreach ($includedData as $include) {
            if (isset($data[$include])) {
                $data[$include] = $this->recursiveInclude(
                    $include,
                    $data[$include],
                    $includedData[$include],
                );
            }
        }

        return $data;
    }

    /**
     * Merges included data with transformed data recursively
     *
     * @param array $transformedData
     * @param array $includedData
     * @return array
     */
    public function mergeIncludes(array $transformedData, array $includedData): array
    {
        $transformedData = parent::mergeIncludes($transformedData, $includedData);

        // Recursively include any nested data
        foreach ($includedData as $key => $value) {
            if (isset($transformedData[$key])) {
                $transformedData[$key] = $this->recursiveInclude(
                    $key,
                    $transformedData[$key],
                    $value,
                );
            }
        }

        return $transformedData;
    }
}
