import { ServerContext } from '@/state/server'
import { useMemo, useState } from 'react'
import { useFormContext } from 'react-hook-form'

import useTemplateGroupsSWR from '@/api/server/settings/useTemplateGroupsSWR'

import SelectForm from '@/components/elements/forms/SelectForm'
import Select from '@/components/elements/inputs/Select'


interface Props {
    disabled?: boolean
}

const TemplatesSelectForm = ({ disabled }: Props) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { setValue } = useFormContext()
    const { data, mutate, isValidating, isLoading } = useTemplateGroupsSWR(uuid)
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