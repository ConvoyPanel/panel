import UserContentBlock from '@/components/admin/users/UserContentBlock'
import GeneralContainer from '@/components/admin/users/settings/GeneralContainer'
import FormSection from '@/components/elements/FormSection'
import DangerZoneContainer from '@/components/admin/users/settings/DangerZoneContainer'

const UserSettingsContainer = () => {
    return (
        <UserContentBlock title={'Settings'}>
            <GeneralContainer />
            <FormSection.Divider />
            <DangerZoneContainer />
        </UserContentBlock>
    )
}

export default UserSettingsContainer
