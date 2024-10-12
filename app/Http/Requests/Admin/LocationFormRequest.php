<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;

class LocationFormRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->method() === 'PUT') {
            /** @var Location $location */
            $location = $this->route()->parameter('location');

            return Location::getRulesForUpdate($location->id);
        }

        return Location::getRules();
    }
}
