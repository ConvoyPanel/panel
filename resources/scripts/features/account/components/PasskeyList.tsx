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
    const { data, isLoading, isError, refetch } = usePasskeys()

    if (isLoading) {
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
