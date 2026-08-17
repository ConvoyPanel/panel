import { useAddresses } from '@/features/servers/detail/api.ts'
import AddressList from '@/features/servers/networking/components/AddressList.tsx'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'

const IpamCard = () => {
    const { data: addresses } = useAddresses()

    return (
        <Card className={'col-span-2 min-h-[15rem] @5xl:col-span-4'}>
            <CardHeader>
                <CardTitle>IPAM</CardTitle>
                <CardDescription>
                    Addresses allocated to this server.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {addresses ? (
                    <AddressList addresses={addresses} />
                ) : (
                    /* Same shape as the table that replaces it — a summary line
                       over a bordered body — so the card holds still on load. */
                    <div className={'flex flex-col gap-3'}>
                        <Skeleton className={'h-4 w-40'} />
                        <Skeleton className={'h-32 w-full rounded-lg'} />
                    </div>
                )}
            </CardContent>
        </Card>
    )
}

export default IpamCard
