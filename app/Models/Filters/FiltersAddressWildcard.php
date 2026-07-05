<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersAddressWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $fields = [
            'id' => false, // false = don't convert to lowercase
            'address' => true, // true = convert to lowercase
            'mac_address' => true,
        ];
        
        $query->where(function (Builder $subQuery) use ($fields, $value) {
            $first = true;
            
            foreach ($fields as $field => $convertCase) {
                $method = $first ? 'where' : 'orWhere';
                $first = false;
                
                if (is_array($value)) {
                    $values = $convertCase 
                        ? Arr::map($value, fn($v) => strtolower($v)) 
                        : $value;
                    $whereInMethod = "{$method}In";
                    $subQuery->{$whereInMethod}($field, $values);
                } else {
                    $fieldValue = $convertCase ? strtolower($value) : $value;
                    $subQuery->$method($field, $fieldValue);
                }
            }
        });
    }
}
