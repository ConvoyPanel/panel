<?php

namespace App\Http\Requests\Admin\Nodes;

use App\Http\Requests\BaseApiRequest;
use App\Models\Node;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class UpdateNodeRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Node::getRulesForUpdate($this->parameter('node', Node::class));

        return [
            ...Arr::except($rules, ['token_id', 'secret']),
            'token_id' => 'sometimes|string|max:191',
            'secret' => 'sometimes|string|max:191',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $node = $this->parameter('node', Node::class);
            // multiply memory by memory_overallocate (which indicates how much you can go over) percentage
            $memory = intval($this->input('memory')) * ((intval(
                $this->input('memory_overallocate'),
            ) / 100) + 1);
            $disk = intval($this->input('disk')) * ((intval(
                $this->input('disk_overallocate'),
            ) / 100) + 1);

            if ($memory < $node->memory_allocated) {
                $validator->errors()->add(
                    'memory',
                    'The memory value is lower than what\'s allocated.',
                );
            }

            if ($disk < $node->disk_allocated) {
                $validator->errors()->add(
                    'disk',
                    'The disk value is lower than what\'s allocated.',
                );
            }
        });
    }
}
