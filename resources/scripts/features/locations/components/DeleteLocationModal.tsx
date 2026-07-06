import useAsyncFunction from '@/hooks/use-async-function.ts'
import { useLocationsModalStore } from '@/routes/_app/admin/_dashboard/locations.lazy.tsx'
import { Location, PaginatedLocations } from '@/features/locations/types.ts'
import { IconExclamationCircle } from '@tabler/icons-react'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { useShallow } from 'zustand/react/shallow'

import { deleteLocation } from '@/features/locations/api.ts'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'

interface Props {
    mutate: Mutator<PaginatedLocations>
}

const DeleteLocationModal = ({ mutate }: Props) => {
    const [location, open, closeModal] = useLocationsModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'delete',
            state.closeModal,
        ])
    )

    const [state, submit] = useAsyncFunction(
        async (currentLocation: Location) => {
            try {
                await deleteLocation(currentLocation.id)

                await mutate(data => {
                    if (!data) return data

                    return {
                        ...data,
                        items: data.items.filter(
                            location => location.id !== currentLocation.id
                        ),
                    }
                })

                toast.success('Location deleted')

                closeModal('delete')
            } catch (e) {
                toast.error('Deletion failed')
                throw e
            }
        }
    )

    return (
        <Credenza
            open={open}
            onOpenChange={open => !open && closeModal('delete')}
        >
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Delete {location?.shortCode}</CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to delete this location?
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaBody>
                    {Boolean(
                        location?.nodesCount && location.nodesCount > 0
                    ) && (
                        <Alert variant={'destructive'}>
                            <IconExclamationCircle className={'size-4'} />
                            <AlertDescription>
                                This location cannot be deleted because there
                                are nodes associated. Please remove all nodes
                                before attempting to delete the location.
                            </AlertDescription>
                        </Alert>
                    )}
                </CredenzaBody>
                <CredenzaFooter className={'mt-4'}>
                    <CredenzaClose asChild>
                        <Button variant={'outline'}>Cancel</Button>
                    </CredenzaClose>
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => submit(location!)}
                        disabled={Boolean(
                            location?.nodesCount && location.nodesCount > 0
                        )}
                    >
                        Delete
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default DeleteLocationModal
