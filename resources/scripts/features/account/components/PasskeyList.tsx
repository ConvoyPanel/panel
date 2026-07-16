import Passkey from '@/features/account/components/Passkey.tsx'
import { usePasskeys } from '@/features/account/passkeys/api.ts'
import { IconKey } from '@tabler/icons-react'

import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

const PasskeyList = () => {
    const { data, isPending, isError, refetch } = usePasskeys()

    // isPending, not isLoading: the query is disabled until identity is
    // confirmed, and a disabled query is pending-but-not-fetching. isLoading is
    // false there, which dropped straight through to the "No passkeys" empty
    // state — rendered behind the identity gate, since a nested dialog leaves
    // its parent visible. The skeleton is the honest thing to show while gated.
    if (isPending) {
        return <Skeleton className={'h-24 w-full'} />
    }

    if (isError && !data) {
        return <CollectionErrorState onRetry={refetch} />
    }

    if (!data || data?.length === 0) {
        return (
            <SimpleEmptyState
                icon={IconKey}
                title={'No passkeys'}
                description={'You have not added any passkeys to your account.'}
            />
        )
    }

    return (
        <ItemGroup className={'gap-3'}>
            {data.map(passkey => (
                <Passkey key={passkey.id} passkey={passkey} />
            ))}
        </ItemGroup>
    )
}

export default PasskeyList
