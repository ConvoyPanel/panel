import { generateAddresses } from '@/features/ipam/blocks/addresses/api.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId/blocks.$addressBlockId.tsx'
import { PaginatedAddresses } from '@/types/address.ts'
import { Mutator } from '@/types/query.ts'
import { IconPlus } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'

import { Button } from '@/components/ui/Button'
import { toast } from '@/components/ui/Toast'

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
            toast.add({
                title: `Generated ${data.createdCount.toLocaleString()} addresses`,
                type: 'success',
            })
        },
        onError: () => {
            toast.add({ title: 'Failed to generate addresses', type: 'error' })
        },
    })

    return (
        <Button
            icon={<IconPlus className={'size-4'} />}
            onClick={() => trigger()}
            loading={isPending}
        >
            Generate IPs
        </Button>
    )
}

export default GenerateAddressesButton
