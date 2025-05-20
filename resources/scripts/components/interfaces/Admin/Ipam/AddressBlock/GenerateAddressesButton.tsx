import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId/blocks.$addressBlockId.tsx'
import { PaginatedAddresses } from '@/types/address.ts'
import { IconPlus } from '@tabler/icons-react'
import { toast } from 'sonner'
import { KeyedMutator } from 'swr'
import useSWRMutation from 'swr/mutation'

import generateAddresses from '@/api/admin/addressBlockGroups/addressBlocks/addresses/generateAddresses.ts'
import { getKey } from '@/api/admin/addressBlockGroups/addressBlocks/addresses/use-addresses-swr.ts'

import { Button } from '@/components/ui/Button'

interface Props {
    mutate: KeyedMutator<PaginatedAddresses>
}

const GenerateAddressesButton = ({ mutate }: Props) => {
    const { addressBlockGroupId, addressBlockId } = Route.useParams()

    const { trigger, isMutating } = useSWRMutation(
        getKey(addressBlockId, {}),
        () => generateAddresses(addressBlockGroupId, addressBlockId),
        {
            async onSuccess(data) {
                await mutate()
                toast.success(`Generated ${data.createdCount.toLocaleString()} addresses`)
            },
            async onError() {
                toast.error('Failed to generate addresses')
            },
        }
    )

    return (
        <Button
            icon={<IconPlus className={'mr-2 size-4'} />}
            size={'sm'}
            onClick={() => trigger()}
            loading={isMutating}
        >
            Generate IPs
        </Button>
    )
}

export default GenerateAddressesButton
