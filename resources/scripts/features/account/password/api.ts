import { apiFetch } from '@/lib/api'
import PasswordController from '@/wayfinder/actions/App/Http/Controllers/Client/PasswordController'

interface Payload {
    currentPassword: string
    password: string
    passwordConfirmation: string
}

export const updatePassword = async ({
    currentPassword,
    password,
    passwordConfirmation,
}: Payload): Promise<void> => {
    await apiFetch(PasswordController.update(), {
        body: {
            current_password: currentPassword,
            password,
            password_confirmation: passwordConfirmation,
        },
    })
}
