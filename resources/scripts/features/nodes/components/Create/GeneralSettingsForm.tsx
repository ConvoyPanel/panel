import LocationPicker from '@/features/locations/components/LocationPicker.tsx'
import SectionRow from '@/features/nodes/components/Create/SectionRow.tsx'

import { InputForm } from '@/components/ui/Forms'

const GeneralSettingsForm = () => {
    return (
        <SectionRow
            title={'General'}
            description={'How this node is labelled and grouped in Convoy.'}
        >
            <div className={'grid gap-4 sm:grid-cols-2'}>
                <InputForm name={'displayName'} label={'Display Name'} />
                <LocationPicker />
            </div>
        </SectionRow>
    )
}

export default GeneralSettingsForm
