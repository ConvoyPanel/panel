import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId/blocks.$addressBlockId.tsx'
import { PaginatedAddresses } from '@/types/address.ts'
import { IconPlus } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'

import generateAddresses from '@/api/admin/addressBlockGroups/addressBlocks/addresses/generateAddresses.ts'

import { Button } from '@/components/ui/Button'

interface Props {
    mutate: Mutator<PaginatedAddresses>
}

const GenerateAddressesButton = ({ mutate }: Props) => {
    const { addressBlockGroupId, addressBlockId } = Route.useParams()

    const blockGroupIdNum = Number(addressBlockGroupId)
    const blockIdNum = Number(addressBlockId)

    const { mutate: trigger, isPending } = useMutation({
        mutationFn: () => generateAddresses(blockGroupIdNum, blockIdNum),
        onSuccess: async data => {
            await mutate()
            toast.success(
                `Generated ${data.createdCount.toLocaleString()} addresses`
            )
        },
        onError: () => {
            toast.error('Failed to generate addresses')
        },
    })

    return (
        <Button
            icon={<IconPlus className={'mr-2 size-4'} />}
            size={'sm'}
            onClick={() => trigger()}
            loading={isPending}
        >
            Generate IPs
        </Button>
    )
}

export default GenerateAddressesButton
