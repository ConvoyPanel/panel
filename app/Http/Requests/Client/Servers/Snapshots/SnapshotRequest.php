<?php

namespace Convoy\Http\Requests\Client\Servers\Snapshots;

use Illuminate\Foundation\Http\FormRequest;

class SnapshotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|alpha_dash|max:50',
        ];
    }
}
