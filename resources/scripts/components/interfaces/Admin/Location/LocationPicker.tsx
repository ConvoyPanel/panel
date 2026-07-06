import { Location } from '@/types/location.ts'
import { cn } from '@/utils'
import { IconCheck, IconMapPin } from '@tabler/icons-react'
import { useController } from 'react-hook-form'

import { getLocations, useLocation } from '@/features/locations/api.ts'

import { ResourceComboboxForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'

const LocationPicker = () => {
    const { field } = useController<{
        locationId: string
    }>({
        name: 'locationId',
    })
    const { data: selectedLocation, isLoading: isLoadingSelection } =
        useLocation(field.value ? Number(field.value) : null)

    return (
        <ResourceComboboxForm<Location>
            swrKey={'locations'}
            accessorKey={'id'}
            name={'locationId'}
            fetcher={(query, page) =>
                getLocations({
                    page: page,
                    filters: {
                        '*': query,
                    },
                })
            }
            renderItem={(location, isSelected) => (
                <>
                    <dl className={'flex grow flex-col overflow-hidden'}>
                        <dt className={'truncate'}>{location.shortCode}</dt>
                        <dd
                            className={'truncate text-xs text-muted-foreground'}
                        >
                            {location.description}
                        </dd>
                    </dl>

                    <IconCheck
                        className={cn(
                            'shrink-0',
                            isSelected ? 'opacity-100' : 'opacity-0'
                        )}
                    />
                </>
            )}
            renderTrigger={() => (
                <>
                    {isLoadingSelection ? (
                        <Skeleton className={'w-24 h-3'} />
                    ) : selectedLocation ? (
                        selectedLocation.shortCode
                    ) : (
                        'Select a location'
                    )}

                    <IconMapPin className={'ml-auto size-4 opacity-50'} />
                </>
            )}
            label={'Location'}
            searchPlaceholder={'Search locations...'}
        />
    )
}

export default LocationPicker
