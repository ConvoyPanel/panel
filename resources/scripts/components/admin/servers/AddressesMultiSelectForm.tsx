import { useDebouncedValue } from '@mantine/hooks'
import { useMemo, useState } from 'react'
import { useFormContext } from 'react-hook-form'

import useAddressesSWR from '@/api/admin/nodes/addresses/useAddressesSWR'

import MultiSelectForm from '@/components/elements/forms/MultiSelectForm'


interface Props {
    disabled?: boolean
    nodeId?: number
}

const AddressesMultiSelectForm = ({ disabled, nodeId: propNodeId }: Props) => {
    const { watch } = useFormContext()
    const addressIds: string[] = watch('addressIds')
    const formNodeId: string = watch('nodeId')
    const nodeId = propNodeId ?? parseInt(formNodeId)

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, isValidating, isLoading } = useAddressesSWR(nodeId ?? -1, {
        query: debouncedQuery,
        serverId: null,
    })
    const { data: selectedAddresses } = useAddressesSWR(nodeId ?? -1, {
        query: (addressIds.length > 0 ? addressIds : [-1]).join(','),
        id: 'selected-addresses',
    })

    const addresses = useMemo(() => {
        const available =
            data && selectedAddresses
                ? data.items
                      .filter(address => {
                          return !selectedAddresses.items.find(
                              selectedAddress =>
                                  selectedAddress.id === address.id
                          )
                      })
                      .map(address => ({
                          value: address.id.toString(),
                          label: address.address,
                      }))
                : []

        const selected = selectedAddresses
            ? selectedAddresses.items.map(address => ({
                  value: address.id.toString(),
                  label: address.address,
              }))
            : []

        return [...selected, ...available]
    }, [data, selectedAddresses])

    return (
        <MultiSelectForm
            data={addresses}
            searchable
            searchValue={query}
            onSearchChange={val => setQuery(val)}
            loading={isValidating || isLoading}
            label={'Addresses'}
            nothingFound='No addresses found'
            name={'addressIds'}
            disabled={disabled}
        />
    )
}

export default AddressesMultiSelectForm