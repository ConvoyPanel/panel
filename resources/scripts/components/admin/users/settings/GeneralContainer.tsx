import DeleteUserContainer from '@/components/admin/users/settings/DeleteUserContainer'
import UserInformationContainer from '@/components/admin/users/settings/UserInformationContainer'

const GeneralContainer = () => {
    return (
        <>
            <UserInformationContainer />
            <DeleteUserContainer />
        </>
    )
}

export default GeneralContainer