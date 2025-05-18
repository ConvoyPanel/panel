import { KeyedMutator } from 'swr'
import { PaginatedAddresses } from '@/types/address.ts'
import { Button } from '@/components/ui/Button'
import { IconPlus } from '@tabler/icons-react'

interface Props {
    mutate: KeyedMutator<PaginatedAddresses>
}

const GenerateAddressesButton = ({ mutate }: Props) => {
    return <Button size={'sm'}>
        <IconPlus className={'mr-2 size-4'} /> Generate IPs
    </Button>
}

export default GenerateAddressesButton