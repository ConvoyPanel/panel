import { Heading } from '@/components/ui/Typography'
import { InputForm } from '@/components/ui/Forms'
import LocationPicker from '@/components/interfaces/Admin/Location/LocationPicker.tsx'

const GeneralSettingsForm = () => {
    return <div className={'flex flex-col space-y-4'}>
        <Heading as={'h3'}>General</Heading>
        <InputForm
            name={'displayName'}
            label={'Display Name'}
        />
        <LocationPicker />
    </div>
}

export default GeneralSettingsForm