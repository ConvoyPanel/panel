import { useLocationsModalStore } from '@/routes/_app/admin/_dashboard/locations.lazy.tsx'
import { useShallow } from 'zustand/react/shallow'

import AttachedNodesList from '@/features/locations/components/AttachedNodesList.tsx'

import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

const ShowLocationModal = () => {
    const [location, open, closeModal] = useLocationsModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'show',
            state.closeModal,
        ])
    )

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={open => !open && closeModal('show')}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>{location?.shortCode}</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Nodes attached to this location
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody
                    className={
                        'h-full max-h-[50vh] overflow-y-auto overflow-x-visible'
                    }
                >
                    <AttachedNodesList location={location} />
                </ResponsiveDialogBody>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ShowLocationModal
