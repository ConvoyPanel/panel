import { Location } from '@/types/location.ts'
import { cn } from '@/utils'
import { useDebouncedValue } from '@mantine/hooks'
import { IconMapPin } from '@tabler/icons-react'
import { useState } from 'react'

import LocationList from '@/components/interfaces/Admin/Location/LocationList.tsx'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'
import {
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { Input } from '@/components/ui/Input'

const LocationPickerModal = () => {
    const [open, setOpen] = useState(false)
    const [cachedLocation, setCachedLocation] = useState<Location | null>(null)

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)

    const [page, setPage] = useState(1)

    const handleSelect = (location: Location) => {
        setCachedLocation(location)
        setOpen(false)
    }

    return (
        <FormField
            name={'locationId'}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>Location</FormLabel>
                    <Credenza open={open} onOpenChange={setOpen}>
                        <CredenzaTrigger asChild>
                            <Button
                                variant={'outline'}
                                className={cn(
                                    'w-full text-left font-normal',
                                    !field.value && 'text-muted-foreground'
                                )}
                                onClick={() => setOpen(true)}
                            >
                                {field.value === cachedLocation?.id
                                    ? cachedLocation!.shortCode
                                    : 'Select a location'}
                                <IconMapPin
                                    className={'ml-auto size-4 opacity-50'}
                                />
                            </Button>
                        </CredenzaTrigger>
                        <CredenzaContent>
                            <CredenzaHeader>
                                <CredenzaTitle>Select a Location</CredenzaTitle>
                            </CredenzaHeader>
                            <CredenzaBody className={' space-y-3'}>
                                <Input
                                    value={query}
                                    onChange={e => setQuery(e.target.value)}
                                    placeholder={'Search locations'}
                                />
                                <LocationList
                                    query={query}
                                    page={page}
                                    value={field.value}
                                    onSelect={location => {
                                        handleSelect(location)
                                        field.onChange(location.id)
                                    }}
                                />
                            </CredenzaBody>
                            <CredenzaFooter className={'mt-1'}>
                                <Button size={'sm'}>Previous</Button>
                                <Button size={'sm'}>Next</Button>
                            </CredenzaFooter>
                        </CredenzaContent>
                    </Credenza>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default LocationPickerModal
