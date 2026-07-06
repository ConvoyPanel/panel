import type {
    PublicKeyCredentialCreationOptionsJSON,
    RegistrationResponseJSON,
} from '@simplewebauthn/browser'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { rawDataToPasskey } from '@/lib/transformers/passkey.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { Passkey } from '@/types/passkey.ts'
import PasskeyController from '@/wayfinder/actions/App/Http/Controllers/Client/PasskeyController'

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

export const verifyRegistration = async (passkey: RegistrationResponseJSON) =>
    rawDataToPasskey(
        (
            await apiFetch<DataResponse<unknown>>(PasskeyController.store(), {
                body: { ...passkey },
            })
        ).data
    )

export const renamePasskey = async (id: number, name: string) =>
    rawDataToPasskey(
        (
            await apiFetch<DataResponse<unknown>>(PasskeyController.rename(id), {
                body: { name },
            })
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
