import FormSection from '@/components/elements/FormSection'
import UserInformationContainer from '@/components/admin/users/settings/UserInformationContainer'
import DeleteUserContainer from '@/components/admin/users/settings/DeleteUserContainer'

const GeneralContainer = () => {
    return (
        <>
            <UserInformationContainer />
            <DeleteUserContainer />
        </>
    )
}

export default GeneralContainer
