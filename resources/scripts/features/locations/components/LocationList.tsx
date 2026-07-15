import { useLocations } from '@/features/locations/api.ts'
import { Location } from '@/features/locations/types.ts'
import { IconCheck, IconMapPin } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import { buttonVariants } from '@/components/ui/Button'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { Entity, EntityGroup } from '@/components/ui/Entity'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    query: string
    page: number
    value: number | null
    onSelect: (location: Location) => void
}

const LocationList = ({ query, page, value, onSelect }: Props) => {
    const { data, isLoading, isError, refetch } = useLocations({
        page,
        filters: { '*': query },
    })

    if (isLoading) {
        return <Skeleton className={'h-24 w-full'} />
    }

    if (isError && !data) {
        return <CollectionErrorState onRetry={refetch} />
    }

    if (!data || data.pagination.count === 0) {
        return (
            <SimpleEmptyState
                icon={IconMapPin}
                title={'No locations'}
                description={'There are no locations available.'}
                action={
                    <Link className={buttonVariants()} to={'/admin/locations'}>
                        Add location
                    </Link>
                }
            />
        )
    }

    return (
        <div className={'overflow-x-visible overflow-y-auto'}>
            <EntityGroup className={'truncate'}>
                {data.items.map(location => (
                    <Entity
                        key={location.id}
                        className={
                            'group data-[selected=true]:bg-foreground data-[selected=true]:text-background flex truncate'
                        }
                        onClick={() => onSelect(location)}
                        data-selected={value === location.id}
                    >
                        <div className={'shrink truncate'}>
                            <p className={'truncate text-sm'}>
                                {location.shortCode}
                            </p>
                            <p
                                className={
                                    'text-muted-foreground group-data-[selected=true]:text-muted/80 truncate text-xs'
                                }
                            >
                                {location.description}
                            </p>
                        </div>
                        {value === location.id && (
                            <IconCheck
                                className={'mr-1 ml-auto size-5 shrink-0'}
                            />
                        )}
                    </Entity>
                ))}
            </EntityGroup>
        </div>
    )
}

export default LocationList
