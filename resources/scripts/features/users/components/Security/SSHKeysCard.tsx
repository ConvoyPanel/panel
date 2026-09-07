import { sshKeyAlgorithm } from '@/features/account/ssh-keys/api.ts'
import type { SSHKey } from '@/features/account/types.ts'
import CredentialCard from '@/features/users/components/Security/CredentialCard.tsx'
import CredentialRow from '@/features/users/components/Security/CredentialRow.tsx'
import {
    revokeUserSSHKey,
    useUserSSHKeys,
    userCredentialQueries,
} from '@/features/users/credentials/api.ts'
import useCredentialRevocation from '@/features/users/hooks/use-credential-revocation.ts'
import { IconKeyFilled } from '@tabler/icons-react'
import { format } from 'date-fns'

import { Badge } from '@/components/ui/Badge'

interface Props {
    userId: number
}

const SSHKeysCard = ({ userId }: Props) => {
    const { data: keys, isLoading, isError, refetch } = useUserSSHKeys(userId)

    const revoke = useCredentialRevocation<SSHKey>({
        queryKey: userCredentialQueries.sshKeys(userId).queryKey,
        remove: key => revokeUserSSHKey(userId, key.id),
        question: 'Revoke SSH key?',
        successTitle: 'SSH key revoked',
        errorTitle: 'Failed to revoke SSH key',
        nameOf: key => `The key “${key.name}”`,
        consequence:
            'Any server it was injected into keeps it until the server is rebuilt.',
    })

    return (
        <CredentialCard
            title={'SSH keychain'}
            description={'Public keys this account injects into its servers.'}
            icon={IconKeyFilled}
            isLoading={isLoading}
            isError={isError}
            onRetry={refetch}
            emptyTitle={'No SSH keys'}
            emptyDescription={'This account has not added a public key.'}
            rows={keys?.map(key => (
                <CredentialRow
                    key={key.id}
                    icon={IconKeyFilled}
                    name={key.name}
                    revokeLabel={`Revoke SSH key ${key.name}`}
                    onRevoke={() => revoke(key)}
                    meta={
                        <>
                            <Badge
                                variant={'secondary'}
                                className={'shrink-0 font-mono whitespace-nowrap'}
                            >
                                {sshKeyAlgorithm(key.publicKey)}
                            </Badge>
                            <span
                                className={
                                    'text-muted-foreground text-xs whitespace-nowrap'
                                }
                            >
                                Added {format(key.createdAt, 'MMMM do, yyyy')}
                            </span>
                        </>
                    }
                />
            ))}
        />
    )
}

export default SSHKeysCard
