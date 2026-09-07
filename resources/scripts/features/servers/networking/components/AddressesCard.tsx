import { useAddresses } from '@/features/servers/detail/api.ts'
import {
    VersionFilter,
    countVersions,
    summarizeAddresses,
} from '@/features/servers/networking/address-labels.ts'
import AddressList from '@/features/servers/networking/components/AddressList.tsx'
import { AddressVersion } from '@/types/address.ts'
import { useState } from 'react'

import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { CollectionErrorState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/ToggleGroup'

interface Props {
    uuid: string
}

const AddressesCard = ({ uuid }: Props) => {
    const { data: addresses, isLoading, isError, refetch } = useAddresses(uuid)
    const [filter, setFilter] = useState<VersionFilter>('all')

    const counts = addresses ? countVersions(addresses) : null
    const isMixed = Boolean(counts && counts.ipv4 > 0 && counts.ipv6 > 0)

    return (
        <Card>
            <CardHeader>
                <CardTitle>IP Addresses</CardTitle>
                {/* The count line the list used to draw above itself. A card
                    header states what the card holds; it does not repeat the
                    title in a sentence. */}
                <CardDescription>
                    {addresses?.length
                        ? summarizeAddresses(addresses)
                        : 'Addresses allocated to this server.'}
                </CardDescription>
                {isMixed && (
                    <CardAction>
                        <ToggleGroup
                            variant={'outline'}
                            size={'sm'}
                            spacing={0}
                            multiple={false}
                            value={[filter]}
                            onValueChange={value => {
                                // Single-select: ignore the empty array a
                                // second click on the pressed item produces,
                                // so one segment is always active.
                                if (value[0])
                                    setFilter(value[0] as VersionFilter)
                            }}
                            aria-label={'Filter addresses by IP version'}
                        >
                            <ToggleGroupItem value={'all'}>All</ToggleGroupItem>
                            <ToggleGroupItem value={AddressVersion.IPv4}>
                                IPv4
                            </ToggleGroupItem>
                            <ToggleGroupItem value={AddressVersion.IPv6}>
                                IPv6
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </CardAction>
                )}
            </CardHeader>
            {/* Unpadded: the rows are the card's own, so their dividers reach
                its edges. Deliberate exception to the Card's `p-4`. */}
            <CardContent className={'p-0'}>
                {isError && !addresses ? (
                    <div className={'px-4 pb-4'}>
                        <CollectionErrorState onRetry={refetch} />
                    </div>
                ) : isLoading || !addresses ? (
                    /* The same rows, so the card holds still on load. */
                    <ul className={'divide-y border-t'}>
                        {Array.from({ length: 3 }).map((_, index) => (
                            <li key={index} className={'px-4 py-3'}>
                                <Skeleton className={'h-4 w-40'} />
                            </li>
                        ))}
                    </ul>
                ) : (
                    <AddressList addresses={addresses} filter={filter} />
                )}
            </CardContent>
        </Card>
    )
}

export default AddressesCard
