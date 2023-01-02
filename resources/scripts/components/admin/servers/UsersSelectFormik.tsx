import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'
import useUsersSWR from '@/api/admin/users/useUsersSWR'
import { debounce } from 'debounce'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import DescriptiveItemComponent from '@/components/elements/DescriptiveItemComponent'

const UsersSelectFormik = () => {
    const [{ value }] = useField('userId')
    const [query, setQuery] = useState(value as string)
    const { data, mutate, isValidating, isLoading } = useUsersSWR({ query })
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
        handleOnSearch(value as string)
    }, [value])

    const search = useCallback(
        debounce(() => {
            mutate()
        }, 500),
        []
    )

    const handleOnSearch = (query: string) => {
        setQuery(query)
        search()
    }

    return (
        <SelectFormik
            label={'User'}
            data={users}
            searchable
            searchValue={query}
            onSearchChange={handleOnSearch}
            loading={isValidating || isLoading}
            nothingFound={'No users found'}
            name={'userId'}
            itemComponent={DescriptiveItemComponent}
        />
    )
}

export default UsersSelectFormik
