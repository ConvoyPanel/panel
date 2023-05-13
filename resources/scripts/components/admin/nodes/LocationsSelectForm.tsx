import useLocationsSWR from '@/api/admin/locations/useLocationsSWR'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import { useDebouncedValue } from '@mantine/hooks'
import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'
import { useFormContext } from 'react-hook-form'
import SelectForm from '@/components/elements/forms/SelectForm'

const LocationsSelectForm = () => {
    const { setValue, watch } = useFormContext()
    const locationId: number = watch('locationId')
    const [query, setQuery] = useState(locationId.toString())
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate, isValidating, isLoading } = useLocationsSWR({ query: debouncedQuery })
    const locations =
        data?.items.map(location => ({
            value: location.id.toString(),
            label: location.shortCode,
        })) ?? []

    return (
        <SelectForm
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

export default LocationsSelectForm
