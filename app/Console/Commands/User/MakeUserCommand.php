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

namespace App\Console\Commands\User;

use App\Exceptions\Model\DataValidationException;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeUserCommand extends Command
{
    protected $description = 'Creates a user on the system via the CLI.';

    protected $signature = 'users:create
        {--email= : Email address}
        {--name= : Name}
        {--password= : Password}
        {--admin= : Whether the user is an administrator (true/false)}';

    /**
     * Handle command request to create a new user.
     */
    public function handle(): int
    {
        $rootAdmin = $this->rootAdmin();

        if ($rootAdmin === null) {
            $this->components->error('The --admin option must be a boolean value: true, false, 1, or 0.');

            return self::FAILURE;
        }

        $data = [
            'email' => $this->option('email') ?? text(
                label: 'Email Address',
                required: true,
                validate: fn (string $value) => $this->validationError('email', $value),
            ),
            'name' => $this->option('name') ?? text(
                label: 'Name',
                required: true,
                validate: fn (string $value) => $this->validationError('name', $value),
            ),
            'password' => $this->option('password') ?? password(
                label: 'Password',
                required: true,
            ),
            'root_admin' => $rootAdmin,
        ];

        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'root_admin' => $data['root_admin'],
                'password' => $data['password'],
            ]);
        } catch (DataValidationException $exception) {
            foreach ($exception->getMessageBag()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->table(['Field', 'Value'], [
            ['Internal ID', $user->id],
            ['Email', $user->email],
            ['Name', $user->name],
            ['Admin', $user->root_admin ? 'Yes' : 'No'],
        ]);

        return self::SUCCESS;
    }

    private function rootAdmin(): ?bool
    {
        $admin = $this->option('admin');

        if ($admin === null) {
            return confirm('Is this user an administrator?');
        }

        if ($admin === '') {
            return null;
        }

        return filter_var($admin, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $rules = User::getRules();

        return [
            'email' => $rules['email'],
            'name' => $rules['name'],
            'password' => ['required', 'string'],
            'root_admin' => $rules['root_admin'],
        ];
    }

    private function validationError(string $field, string $value): ?string
    {
        $validator = Validator::make([$field => $value], [$field => $this->rules()[$field]]);

        return $validator->errors()->first($field) ?: null;
    }
}
