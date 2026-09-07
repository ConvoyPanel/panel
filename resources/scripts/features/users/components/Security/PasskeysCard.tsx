import type { Passkey } from '@/features/account/types.ts'
import CredentialCard from '@/features/users/components/Security/CredentialCard.tsx'
import CredentialRow from '@/features/users/components/Security/CredentialRow.tsx'
import {
    revokeUserPasskey,
    useUserPasskeys,
    userCredentialQueries,
} from '@/features/users/credentials/api.ts'
import useCredentialRevocation from '@/features/users/hooks/use-credential-revocation.ts'
import { IconFingerprint } from '@tabler/icons-react'
import { format, formatDistanceToNow } from 'date-fns'

interface Props {
    userId: number
}

const PasskeysCard = ({ userId }: Props) => {
    const { data: passkeys, isLoading, isError, refetch } = useUserPasskeys(userId)

    const revoke = useCredentialRevocation<Passkey>({
        queryKey: userCredentialQueries.passkeys(userId).queryKey,
        remove: passkey => revokeUserPasskey(userId, passkey.id),
        question: 'Revoke passkey?',
        successTitle: 'Passkey revoked',
        errorTitle: 'Failed to revoke passkey',
        nameOf: passkey => `The passkey “${passkey.name}”`,
        consequence:
            'Only the account holder can enrol a replacement, from their own device.',
    })

    return (
        <CredentialCard
            title={'Passkeys'}
            description={'Devices that can sign in without a password.'}
            icon={IconFingerprint}
            isLoading={isLoading}
            isError={isError}
            onRetry={refetch}
            emptyTitle={'No passkeys'}
            emptyDescription={'This account signs in with a password.'}
            rows={passkeys?.map(passkey => (
                <CredentialRow
                    key={passkey.id}
                    icon={IconFingerprint}
                    name={passkey.name}
                    revokeLabel={`Revoke passkey ${passkey.name}`}
                    onRevoke={() => revoke(passkey)}
                    meta={
                        <span
                            className={
                                'text-muted-foreground text-xs whitespace-nowrap'
                            }
                        >
                            Added {format(passkey.createdAt, 'MMMM do, yyyy')}
                            {passkey.lastUsedAt
                                ? ` · used ${formatDistanceToNow(passkey.lastUsedAt, { addSuffix: true })}`
                                : ' · never used'}
                        </span>
                    }
                />
            ))}
        />
    )
}

export default PasskeysCard
