import { useDebouncedValue } from '@mantine/hooks'
import { useMemo, useState } from 'react'
import { useFormContext } from 'react-hook-form'
import { useTranslation } from 'react-i18next'

import useNodesSWR from '@/api/admin/nodes/useNodesSWR'

import DescriptiveItemComponent from '@/components/elements/DescriptiveItemComponent'
import MultiSelectForm from '@/components/elements/forms/MultiSelectForm'


interface Props {
    disabled?: boolean
    loading?: boolean
}

const CotermNodesMultiSelectForm = ({ disabled, loading }: Props) => {
    const { t } = useTranslation('admin.addressPools.index')
    const { t: tStrings } = useTranslation('strings')
    const { watch } = useFormContext()
    const nodeIds: string[] = watch('nodeIds')

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)

    const { data, isValidating, isLoading } = useNodesSWR({
        query: debouncedQuery,
        cotermId: null,
        perPage: 20,
    })
    const { data: selectedNodes } = useNodesSWR({
        id: nodeIds.length > 0 ? nodeIds : [-1],
    })

    const nodes = useMemo(() => {
        const available =
            data && selectedNodes
                ? data.items
                      .filter(
                          node =>
                              !selectedNodes.items.find(
                                  selectedNode => selectedNode.id === node.id
                              )
                      )
                      .map(node => ({
                          value: node.id.toString(),
                          label: node.name,
                          description: node.fqdn,
                      }))
                : []

        const selected = selectedNodes
            ? selectedNodes.items.map(node => ({
                  value: node.id.toString(),
                  label: node.name,
                  description: node.fqdn,
              }))
            : []

        return [...selected, ...available]
    }, [data, selectedNodes])

    return (
        <MultiSelectForm
            data={nodes}
            itemComponent={DescriptiveItemComponent}
            searchable
            searchValue={query}
            onSearchChange={val => setQuery(val)}
            loading={isValidating || isLoading || loading}
            label={'Attached Nodes'}
            nothingFound={t('nodes_nothing_found')}
            name={'nodeIds'}
            disabled={disabled}
        />
    )
}

export default CotermNodesMultiSelectForm