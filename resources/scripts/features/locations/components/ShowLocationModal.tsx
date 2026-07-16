import AttachedNodesList from '@/features/locations/components/AttachedNodesList.tsx'
import { useModal } from '@/hooks/create-modal-store.ts'
import { useLocationsModalStore } from '@/routes/_app/admin/_dashboard/locations.lazy.tsx'

import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

const ShowLocationModal = () => {
    const {
        open,
        data: location,
        close,
    } = useModal(useLocationsModalStore, 'show')

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {location?.shortCode}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Nodes attached to this location
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody
                    className={
                        'h-full max-h-[50vh] overflow-x-visible overflow-y-auto'
                    }
                >
                    <AttachedNodesList location={location} />
                </ResponsiveDialogBody>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ShowLocationModal
