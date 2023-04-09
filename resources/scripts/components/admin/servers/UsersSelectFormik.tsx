import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'
import useUsersSWR from '@/api/admin/users/useUsersSWR'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import DescriptiveItemComponent from '@/components/elements/DescriptiveItemComponent'
import { useDebouncedValue } from '@mantine/hooks'

const UsersSelectFormik = () => {
    const [{ value }] = useField('userId')
    const [query, setQuery] = useState(value as string)
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, isValidating, isLoading } = useUsersSWR({ query: debouncedQuery })
    const users = useMemo(
        () =>
            data?.items.map(user => ({
                value: user.id.toString(),
                label: user.name,
                description: user.email,
            })) ?? [],
        [data]
    )

    useEffect(() => {
        setQuery(value as string)
    }, [value])

    return (
        <SelectFormik
            label={'User'}
            data={users}
            searchable
            searchValue={query}
            onSearchChange={val => setQuery(val)}
            loading={isValidating || isLoading}
            nothingFound={'No users found'}
            name={'userId'}
            itemComponent={DescriptiveItemComponent}
        />
    )
}

export default UsersSelectFormik
