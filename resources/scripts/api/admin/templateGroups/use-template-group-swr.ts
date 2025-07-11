import { useParams } from '@tanstack/react-router';
import useSWR, { preload } from 'swr';
import getTemplateGroup from '@/api/admin/templateGroups/getTemplateGroup.ts';

export const getKey = (id: number) => ['template-group', id];

export const preloadTemplateGroup = async (id: number) => {
    await preload(getKey(id), () => getTemplateGroup(id));
}

const useTemplateGroupSWR = () => {
    const params = useParams({ strict: false }) as {
        templateGroupId: number;
    };

    return useSWR(getKey(params.templateGroupId), () =>
        getTemplateGroup(params.templateGroupId)
    );
}

export default useTemplateGroupSWR;