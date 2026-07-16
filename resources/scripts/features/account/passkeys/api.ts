import { rawDataToPasskey } from '@/features/account/transforms.ts'
import { Passkey } from '@/features/account/types.ts'
import PasskeyController from '@/wayfinder/actions/App/Http/Controllers/Client/PasskeyController'
import type {
    PublicKeyCredentialCreationOptionsJSON,
    RegistrationResponseJSON,
} from '@simplewebauthn/browser'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

export const getPasskeys = async (): Promise<Passkey[]> => {
    const { data } = await apiFetch<DataResponse<any[]>>(
        PasskeyController.index()
    )

    return data.map(rawDataToPasskey)
}

export const getRegistrationOptions =
    (): Promise<PublicKeyCredentialCreationOptionsJSON> =>
        apiFetch<PublicKeyCredentialCreationOptionsJSON>(
            PasskeyController.create()
        )

export const verifyRegistration = async (passkey: RegistrationResponseJSON) => {
    const response = await apiFetch<
        DataResponse<unknown> & { recovery_codes: string[] | null }
    >(PasskeyController.store(), {
        body: { ...passkey },
    })

    return {
        passkey: rawDataToPasskey(response.data),
        recoveryCodes: response.recovery_codes,
    }
}

export const renamePasskey = async (id: number, name: string) =>
    rawDataToPasskey(
        (
            await apiFetch<DataResponse<unknown>>(
                PasskeyController.rename(id),
                {
                    body: { name },
                }
            )
        ).data
    )

export const deletePasskey = async (id: number): Promise<void> => {
    await apiFetch(PasskeyController.destroy(id))
}

export const passkeyQueries = {
    all: () => ['account', 'passkeys'] as const,
    list: () =>
        queryOptions({ queryKey: passkeyQueries.all(), queryFn: getPasskeys }),
}

export const usePasskeys = () => useQuery(passkeyQueries.list())
