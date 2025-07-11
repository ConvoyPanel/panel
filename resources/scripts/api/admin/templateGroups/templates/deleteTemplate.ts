import axios from '@/lib/axios';

const deleteTemplate = async (
    templateGroupId: number,
    templateId: number
) => {
    await axios.delete(
        `/api/admin/template-groups/${templateGroupId}/templates/${templateId}`
    );
};

export default deleteTemplate;