import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'
import useAddressesSWR from '@/api/admin/nodes/addresses/useAddressesSWR'
import MultiSelectFormik from '@/components/elements/forms/MultiSelectFormik'
import { useDebouncedValue } from '@mantine/hooks'

interface Props {
    disabled?: boolean
    nodeId?: number
}

const AddressesMultiSelectFormik = ({ disabled, nodeId: propNodeId }: Props) => {
    const [{ value: addressIds }] = useField('addressIds')
    const [{ value: formNodeId }] = useField('nodeId')
    const nodeId = propNodeId ?? formNodeId

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate, isValidating, isLoading } = useAddressesSWR(nodeId ?? -1, { query: debouncedQuery, serverId: null })
    const { data: selectedAddresses } = useAddressesSWR(nodeId ?? -1, {
        query: ((addressIds as number[]).length > 0 ? (addressIds as number[]) : [-1]).join(','),
        id: 'selected-addresses',
    })

    const addresses = useMemo(() => {
        const available =
            data && selectedAddresses
                ? data.items
                      .filter(address => {
                          return !selectedAddresses.items.find(selectedAddress => selectedAddress.id === address.id)
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
        <MultiSelectFormik
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

export default AddressesMultiSelectFormik
