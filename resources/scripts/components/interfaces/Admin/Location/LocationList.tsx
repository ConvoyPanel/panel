import { Location } from '@/types/location.ts'
import { IconCheck, IconMapPin } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import useLocations from '@/api/admin/locations/use-locations.ts'

import { buttonVariants } from '@/components/ui/Button'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { Entity, EntityGroup } from '@/components/ui/Entity'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    query: string
    page: number
    value: number | null
    onSelect: (location: Location) => void
}

const LocationList = ({ query, page, value, onSelect }: Props) => {
    const { data, isLoading } = useLocations({
        page,
        filters: { '*': query },
    })

    if (isLoading) {
        return <Skeleton className={'h-24 w-full'} />
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
        <div className={'overflow-y-auto overflow-x-visible'}>
            <EntityGroup className={'truncate'}>
                {data.items.map(location => (
                    <Entity
                        key={location.id}
                        className={
                            'group flex truncate data-[selected=true]:bg-foreground data-[selected=true]:text-background'
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
                                    'truncate text-xs text-muted-foreground group-data-[selected=true]:text-muted/80'
                                }
                            >
                                {location.description}
                            </p>
                        </div>
                        {value === location.id && (
                            <IconCheck
                                className={'ml-auto mr-1 size-5 shrink-0'}
                            />
                        )}
                    </Entity>
                ))}
            </EntityGroup>
        </div>
    )
}

export default LocationList
