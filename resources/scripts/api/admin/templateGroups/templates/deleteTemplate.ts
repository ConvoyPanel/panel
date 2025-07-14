import axios from '@/lib/axios';

const deleteTemplate = async (
    templateGroupUuid: string,
    templateUuid: string,
) => {
    await axios.delete(
        `/api/admin/template-groups/${templateGroupUuid}/templates/${templateUuid}`,
    )
};

export default deleteTemplate;