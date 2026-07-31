import { deleteLocation } from '@/features/locations/api.ts'
import { Location, PaginatedLocations } from '@/features/locations/types.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { useLocationsModalStore } from '@/routes/_app/admin/_dashboard/locations.lazy.tsx'
import { Mutator } from '@/types/query.ts'
import { IconExclamationCircle } from '@tabler/icons-react'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

interface Props {
    mutate: Mutator<PaginatedLocations>
}

const DeleteLocationModal = ({ mutate }: Props) => {
    const {
        open,
        data: location,
        close,
    } = useModal(useLocationsModalStore, 'delete')

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

                toast.add({ title: 'Location deleted', type: 'success' })

                close()
            } catch (e) {
                toast.add({ title: 'Deletion failed', type: 'error' })
                throw e
            }
        }
    )

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Delete {location?.shortCode}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this location?
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
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
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => location && submit(location)}
                        disabled={Boolean(
                            location?.nodesCount && location.nodesCount > 0
                        )}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteLocationModal
