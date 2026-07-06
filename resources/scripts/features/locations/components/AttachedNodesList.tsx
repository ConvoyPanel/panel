import { Location } from '@/features/locations/types.ts'
import { IconServer } from '@tabler/icons-react'

import { useAttachedNodes } from '@/features/locations/api.ts'

import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { Entity, EntityGroup } from '@/components/ui/Entity'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    location: Location | null
}

const AttachedNodesList = ({ location }: Props) => {
    const { data, isLoading } = useAttachedNodes(location?.id)

    if (isLoading) {
        return <Skeleton className={'h-24 w-full'} />
    }

    if (!data || data?.length === 0) {
        return (
            <SimpleEmptyState
                icon={IconServer}
                title={'No nodes'}
                description={'There are no nodes attached to this location.'}
            />
        )
    }

    return (
        <EntityGroup>
            {data.map(node => (
                <Entity className={'flex-col'} key={node.id}>
                    <p className={'text-sm'}>{node.displayName}</p>
                    <p className={'text-xs text-muted-foreground'}>
                        {node.fqdn}
                    </p>
                </Entity>
            ))}
        </EntityGroup>
    )
}

export default AttachedNodesList
