import type { OAuthConnection } from '@/features/account/oauth/api.ts'
import CredentialCard from '@/features/users/components/Security/CredentialCard.tsx'
import CredentialRow from '@/features/users/components/Security/CredentialRow.tsx'
import {
    revokeUserOAuthConnection,
    useUserOAuthConnections,
    userCredentialQueries,
} from '@/features/users/credentials/api.ts'
import useCredentialRevocation from '@/features/users/hooks/use-credential-revocation.ts'
import { IconPlugConnected } from '@tabler/icons-react'
import { formatDistanceToNow } from 'date-fns'

interface Props {
    userId: number
}

const OAuthConnectionsCard = ({ userId }: Props) => {
    const {
        data: connections,
        isLoading,
        isError,
        refetch,
    } = useUserOAuthConnections(userId)

    const revoke = useCredentialRevocation<OAuthConnection>({
        queryKey: userCredentialQueries.oauthConnections(userId).queryKey,
        remove: connection => revokeUserOAuthConnection(userId, connection.id),
        question: 'Unlink this login?',
        successTitle: 'Login unlinked',
        errorTitle: 'Failed to unlink login',
        nameOf: connection => `The ${connection.label} login`,
        consequence:
            'The account can still sign in with its password, and can link the provider again.',
        confirmText: 'Unlink',
    })

    return (
        <CredentialCard
            title={'Linked logins'}
            description={'Identity providers this account signs in through.'}
            icon={IconPlugConnected}
            isLoading={isLoading}
            isError={isError}
            onRetry={refetch}
            emptyTitle={'No linked logins'}
            emptyDescription={'This account signs in with the panel directly.'}
            rows={connections?.map(connection => (
                <CredentialRow
                    key={connection.id}
                    icon={IconPlugConnected}
                    name={connection.label}
                    revokeLabel={`Unlink ${connection.label}`}
                    onRevoke={() => revoke(connection)}
                    meta={
                        <span
                            className={
                                'text-muted-foreground truncate text-xs'
                            }
                        >
                            {connection.email ?? connection.name ?? 'Linked'}
                            {connection.lastUsedAt
                                ? ` · used ${formatDistanceToNow(connection.lastUsedAt, { addSuffix: true })}`
                                : ''}
                        </span>
                    }
                />
            ))}
        />
    )
}

export default OAuthConnectionsCard
