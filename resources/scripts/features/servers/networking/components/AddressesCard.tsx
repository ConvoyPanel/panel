import { useAddresses } from '@/features/servers/detail/api.ts'
import AddressList from '@/features/servers/networking/components/AddressList.tsx'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { CollectionErrorState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    uuid: string
}

const AddressesCard = ({ uuid }: Props) => {
    const { data: addresses, isLoading, isError, refetch } = useAddresses(uuid)

    return (
        <Card>
            <CardHeader>
                <CardTitle>IP Addresses</CardTitle>
                <CardDescription>
                    Addresses allocated to this server.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {isError && !addresses ? (
                    <CollectionErrorState onRetry={refetch} />
                ) : isLoading || !addresses ? (
                    /* Shaped like the table it becomes — a summary line over a
                       bordered body — so the card doesn't resize on load. */
                    <div className={'flex flex-col gap-3'}>
                        <Skeleton className={'h-4 w-40'} />
                        <Skeleton className={'h-32 w-full rounded-lg'} />
                    </div>
                ) : (
                    <AddressList addresses={addresses} />
                )}
            </CardContent>
        </Card>
    )
}

export default AddressesCard
