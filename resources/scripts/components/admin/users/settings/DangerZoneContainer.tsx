import DeleteServerContainer from '@/components/admin/servers/settings/DeleteServerContainer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import DeleteUserContainer from '@/components/admin/users/settings/DeleteUserContainer'

const DangerZoneContainer = () => {
    return (
        <FormSection title='Danger Zone' description='ayo. be careful'>
            <DeleteUserContainer />
        </FormSection>
    )
}

export default DangerZoneContainer
