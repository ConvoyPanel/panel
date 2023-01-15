import DeleteServerContainer from '@/components/admin/servers/settings/DeleteServerContainer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'

const DangerZoneContainer = () => {
    return <FormSection title='Danger Zone' description='ayo. be careful'>
        <DeleteServerContainer />
    </FormSection>
}

export default DangerZoneContainer
