import FormSection from '@/components/elements/FormSection'
import UserInformationContainer from '@/components/admin/users/settings/UserInformationContainer'

const GeneralContainer = () => {
    return (
        <FormSection title='General Settings'>
            <UserInformationContainer />
        </FormSection>
    )
}

export default GeneralContainer
