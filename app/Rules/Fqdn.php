<?php

/*
Pterodactyl®
Copyright © Dane Everitt <dane@daneeveritt.com> and contributors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 */

namespace App\Rules;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class Fqdn implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    protected ?string $schemeField = null;

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Validates that the value provided resolves to an IP address. If a scheme is
     * specified when this rule is created additional checks will be applied.
     *
     * @param  mixed  $value
     */
    public function validate(string $attribute, $value, Closure $fail): void
    {
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            // Check if the scheme is set to HTTPS.
            //
            // Unless someone owns their IP blocks and decides to pay who knows how much for a
            // custom SSL cert, IPs will not be able to use HTTPS.  This should prevent most
            // home users from making this mistake and wondering why their node is not working.
            if ($this->schemeField && Arr::get($this->data, $this->schemeField) === 'https') {
                $fail(__('validation.fqdn.https_and_ip'));
            }
        }

        // Lookup A and AAAA DNS records for the FQDN. Note, this function will also resolve CNAMEs
        // for us automatically, there is no need to manually resolve them here.
        //
        // The error suppression is intentional, see https://bugs.php.net/bug.php?id=73149
        $records = @dns_get_record($value, DNS_A + DNS_AAAA);
        // If no records were returned fall back to trying to resolve the value using the hosts DNS
        // resolution. This will not work for IPv6 which is why we prefer to use `dns_get_record`
        // first.
        if (! empty($records) || filter_var(gethostbyname($value), FILTER_VALIDATE_IP)) {
            return;
        }

        $fail(__('validation.fqdn.unresolvable'));
    }

    /**
     * Returns a new instance of the rule with a defined scheme set.
     */
    public static function make(string $schemeField = null): self
    {
        return tap(new static(), function ($fqdn) use ($schemeField) {
            $fqdn->schemeField = $schemeField;
        });
    }
}
