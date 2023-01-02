import useLocationsSWR from '@/api/admin/locations/useLocationsSWR'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import { debounce } from 'debounce'
import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'

const LocationsSelectFormik = () => {
    const [{ value }] = useField('locationId')
    const [query, setQuery] = useState(value as string)
    const { data, mutate, isValidating, isLoading } = useLocationsSWR({ query })
    const locations = useMemo(
        () =>
            data?.items.map(location => ({
                value: location.id.toString(),
                label: location.shortCode,
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
            label='Location Group'
            placeholder='fuk u chit'
            data={locations}
            searchable
            searchValue={query}
            onSearchChange={handleOnSearch}
            loading={isValidating || isLoading}
            nothingFound='No locations found'
            name='locationId'
        />
    )
}

export default LocationsSelectFormik
