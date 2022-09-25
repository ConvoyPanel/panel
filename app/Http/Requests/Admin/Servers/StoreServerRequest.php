<?php

namespace Convoy\Http\Requests\Admin\Servers;

use Convoy\Http\Requests\Admin\AdminFormRequest;
use Convoy\Models\Server;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $type
 */
class StoreServerRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = Server::getRules();
        $rules['template_id'] = 'required_if:type,new|exists:templates,id';
        $rules['vmid'] = $this->convertRule('required', $rules['vmid'], 'required_if:type,existing');
        $rules['cpu'] = $this->convertRule('required', $rules['cpu'], 'required_if:type,new');
        $rules['memory'] = $this->convertRule('required', $rules['memory'], 'required_if:type,new');
        $rules['disk'] = $this->convertRule('required', $rules['disk'], 'required_if:type,new');
        $rules['template'] = $this->convertRule('required', $rules['template'], 'required_if:type,existing');
        $rules['visible'] = $this->convertRule('required', $rules['visible'], 'required_with:template');

        return $rules;
    }
}
