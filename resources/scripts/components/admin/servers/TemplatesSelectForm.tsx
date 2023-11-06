import { useMemo, useState } from 'react'
import { useFormContext } from 'react-hook-form'

import useTemplateGroupsSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'

import SelectForm from '@/components/elements/forms/SelectForm'
import Select from '@/components/elements/inputs/Select'


interface Props {
    disabled?: boolean
}

const TemplatesSelectForm = ({ disabled }: Props) => {
    const { setValue, watch } = useFormContext()
    const nodeId: number | null = watch('nodeId', null)
    const { data, mutate, isValidating, isLoading } = useTemplateGroupsSWR(
        nodeId ?? -1
    )
    const templateGroups =
        data?.map(group => {
            return {
                value: group.uuid,
                label: group.name,
                templates: group.templates!.map(template => ({
                    value: template.uuid,
                    label: template.name,
                })),
            }
        }) ?? []

    const [groupUuid, setGroupUuid] = useState('')
    const templates = useMemo(
        () =>
            templateGroups.find(group => group.value === groupUuid)
                ?.templates ?? [],
        [templateGroups, groupUuid]
    )

    const handleGroupsOnChange = (uuid: string | null) => {
        setGroupUuid(uuid ?? '')
        setValue('templateUuid', '')
    }

    return (
        <>
            <Select
                label={'Template Group'}
                data={templateGroups}
                searchable
                value={groupUuid}
                onChange={handleGroupsOnChange}
                loading={isValidating || isLoading}
                nothingFound={'No groups found'}
                disabled={disabled}
            />
            <SelectForm
                data={templates}
                name={'templateUuid'}
                label={'Template'}
                searchable
                nothingFound={'No templates found'}
                disabled={groupUuid === '' || disabled}
            />
        </>
    )
}

export default TemplatesSelectForm