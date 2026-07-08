import { useAddresses } from '@/features/servers/detail/api.ts'

import { Badge } from '@/components/ui/Badge'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { IconNetwork } from '@tabler/icons-react'

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
                ) : addresses.length > 0 ? (
                    <div className={'overflow-x-auto'}>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Address</TableHead>
                                    <TableHead>Version</TableHead>
                                    <TableHead>Gateway</TableHead>
                                    <TableHead>MAC</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {addresses.map(address => (
                                    <TableRow key={address.id}>
                                        <TableCell className={'font-mono'}>
                                            {address.ip}/{address.prefixLength}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={'secondary'}>
                                                {address.version}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className={'font-mono'}>
                                            {address.gateway}
                                        </TableCell>
                                        <TableCell
                                            className={
                                                'font-mono text-muted-foreground'
                                            }
                                        >
                                            {address.macAddress ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                ) : (
                    <SimpleEmptyState
                        icon={IconNetwork}
                        title={'No addresses'}
                        description={
                            'This server has no allocated IP addresses.'
                        }
                    />
                )}
            </CardContent>
        </Card>
    )
}

export default AddressesCard
