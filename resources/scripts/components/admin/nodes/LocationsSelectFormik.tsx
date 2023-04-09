import useLocationsSWR from '@/api/admin/locations/useLocationsSWR'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import { useDebouncedValue } from '@mantine/hooks'
import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'

const LocationsSelectFormik = () => {
    const [{ value }] = useField('locationId')
    const [query, setQuery] = useState(value as string)
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate, isValidating, isLoading } = useLocationsSWR({ query: debouncedQuery })
    const locations = useMemo(
        () =>
            data?.items.map(location => ({
                value: location.id.toString(),
                label: location.shortCode,
            })) ?? [],
        [data]
    )

    useEffect(() => {
        setQuery(value as string)
    }, [value])

    return (
        <SelectFormik
            label='Location Group'
            data={locations}
            searchable
            searchValue={query}
            onSearchChange={val => setQuery(val)}
            loading={isValidating || isLoading}
            nothingFound='No locations found'
            name='locationId'
        />
    )
}

export default LocationsSelectFormik
