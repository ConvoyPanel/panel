import axios from '@/lib/axios'

const deleteTemplateGroup = async (uuid: string) => {
    await axios.delete(`/api/admin/template-groups/${uuid}`)
}

export default deleteTemplateGroup
