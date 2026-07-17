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
                    <Skeleton className={'h-8 w-full'} />
                )}
            </CardContent>
        </Card>
    )
}

export default IpamCard
