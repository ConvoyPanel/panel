import { useField } from 'formik'
import useTemplateGroupsSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'
import { useMemo, useState } from 'react'
import Select from '@/components/elements/inputs/Select'
import SelectFormik from '@/components/elements/forms/SelectFormik'

interface Props {
    disabled?: boolean
}

const TemplatesSelectFormik = ({ disabled }: Props) => {
    const [{ value }] = useField('nodeId')
    const [{}, {}, { setValue: setTemplateUuid }] = useField('templateUuid')
    const { data, mutate, isValidating, isLoading } = useTemplateGroupsSWR(value)
    const templateGroups = useMemo(
        () =>
            data?.map(group => {
                return {
                    value: group.uuid,
                    label: group.name,
                    templates: group.templates!.map(template => ({
                        value: template.uuid,
                        label: template.name,
                    })),
                }
            }) ?? [],
        [data]
    )
    const [groupUuid, setGroupUuid] = useState('')
    const templates = useMemo(
        () => templateGroups.find(group => group.value === groupUuid)?.templates ?? [],
        [templateGroups, groupUuid]
    )

    const handleGroupsOnChange = (uuid: string | null) => {
        setGroupUuid(uuid ?? '')
        setTemplateUuid('')
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
            <SelectFormik
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

export default TemplatesSelectFormik
