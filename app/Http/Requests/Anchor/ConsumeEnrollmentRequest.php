<?php

namespace App\Http\Requests\Anchor;

use App\Enums\Anchor\AnchorMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ConsumeEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],

            /*
             * Absent on the targeted path, where the mode is already recorded
             * against the Anchor and the agent is told what it is. Required to
             * self-register, because there is nothing yet to disagree with.
             */
            'mode' => ['sometimes', new Enum(AnchorMode::class)],

            /*
             * The machine's self-description. Every field is optional: an older
             * agent gathers less, and refusing enrollment over a fact nobody
             * schedules against would be a poor trade. What is *validated* is
             * the shape, so that a garbage report cannot be stored as though it
             * were evidence.
             *
             * Nothing here may grant privilege -- no location, no relay, no
             * approval. Those are decisions, and a decision cannot arrive in
             * the same envelope as the request for it.
             */
            'report' => ['sometimes', 'array'],
            'report.hostname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'report.pve_node_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'report.pve_version' => ['sometimes', 'nullable', 'string', 'max:64'],
            'report.cluster_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'report.cluster_ca_fingerprint' => ['sometimes', 'nullable', 'string', 'max:191'],
            'report.cpu' => ['sometimes', 'nullable', 'array'],
            'report.cpu.sockets' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1024'],
            'report.cpu.cores' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:8192'],
            'report.cpu.threads' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:16384'],
            'report.memory_bytes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'report.addresses' => ['sometimes', 'nullable', 'array', 'max:32'],
            'report.addresses.*' => ['string', 'ip'],
            'report.version' => ['sometimes', 'nullable', 'string', 'max:64'],
            'report.protocol' => ['sometimes', 'nullable', 'array'],
            'report.protocol.min' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'report.protocol.max' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'report.capabilities' => ['sometimes', 'nullable', 'array', 'max:32'],
            'report.capabilities.*' => ['string', 'max:191'],
        ];
    }

    /** Defaults to an agent: the mode that has to be installed on a host to be useful. */
    public function mode(): AnchorMode
    {
        return $this->enum('mode', AnchorMode::class) ?? AnchorMode::AGENT;
    }

    /**
     * The validated report, plus what only the panel can observe.
     *
     * The source address is recorded because it is the one reachability claim
     * the machine cannot overstate -- it is where the request actually came
     * from. Approval uses it as a candidate; nothing dials it unverified.
     *
     * @return array<string, mixed>
     */
    public function report(): array
    {
        /** @var array<string, mixed> $report */
        $report = $this->validated()['report'] ?? [];

        return [
            ...$report,
            'observed_source_ip' => $this->ip(),
            'observed_at' => now()->toIso8601String(),
        ];
    }
}
