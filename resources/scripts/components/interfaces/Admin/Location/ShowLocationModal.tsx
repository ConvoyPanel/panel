import { useLocationsModalStore } from '@/routes/_app/admin/_dashboard/locations.lazy.tsx'
import { useShallow } from 'zustand/react/shallow'

import AttachedNodesList from '@/components/interfaces/Admin/Location/AttachedNodesList.tsx'

import {
    Credenza,
    CredenzaBody,
    CredenzaContent,
    CredenzaDescription,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'

const ShowLocationModal = () => {
    const [location, open, closeModal] = useLocationsModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'show',
            state.closeModal,
        ])
    )

    return (
        <Credenza
            open={open}
            onOpenChange={open => !open && closeModal('show')}
        >
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>{location?.shortCode}</CredenzaTitle>
                    <CredenzaDescription>
                        Nodes attached to this location
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaBody
                    className={
                        'h-full max-h-[50vh] overflow-y-auto overflow-x-visible'
                    }
                >
                    <AttachedNodesList location={location} />
                </CredenzaBody>
            </CredenzaContent>
        </Credenza>
    )
}

export default ShowLocationModal
