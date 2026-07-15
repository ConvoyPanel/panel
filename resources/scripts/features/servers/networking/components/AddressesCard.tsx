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

interface Props {
    uuid: string
}

const AddressesCard = ({ uuid }: Props) => {
    const { data: addresses, isLoading } = useAddresses(uuid)

    return (
        <Card className={'@md:col-span-2'}>
            <CardHeader>
                <CardTitle>IP Addresses</CardTitle>
                <CardDescription>
                    Addresses allocated to this server.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {isLoading || !addresses ? (
                    <Skeleton className={'h-32 w-full'} />
                ) : (
                    <AddressList addresses={addresses} />
                )}
            </CardContent>
        </Card>
    )
}

export default AddressesCard
