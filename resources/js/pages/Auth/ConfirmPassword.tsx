import React, { ChangeEvent, FormEvent, useEffect } from 'react';
import Button from '@/components/Button';
import Guest from '@/components/layouts/Guest';
import Input from '@/components/Input';
import Label from '@/components/Label';
import ValidationErrors from '@/components/ValidationErrors';
import { Head, useForm } from '@inertiajs/inertia-react';

interface Form {
    password: string
}

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm<Form>({
        password: '',
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const onHandleChange = (event: ChangeEvent<HTMLInputElement>) => {
        setData('password', event.target.value);
    };

    const submit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        post(route('password.confirm'));
    };

    return (
        <Guest>
            <Head title="Confirm Password" />

            <div className="mb-4 text-sm text-gray-600">
                This is a secure area of the application. Please confirm your password before continuing.
            </div>

            <ValidationErrors errors={errors} />

            <form onSubmit={submit}>
                <div className="mt-4">
                    <Label forInput="password" value="Password" />

                    <Input
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        isFocused={true}
                        handleChange={onHandleChange}
                    />
                </div>

                <div className="flex items-center justify-end mt-4">
                    <Button className="ml-4" processing={processing}>
                        Confirm
                    </Button>
                </div>
            </form>
        </Guest>
    );
}
