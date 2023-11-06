import http from '@/api/http'

export interface TemplateGroup {
    id: number
    nodeId: number
    uuid: string
    name: string
    hidden: boolean
    templates?: Template[]
    orderColumn: number
}

export interface Template {
    id: number
    templateGroupId: number
    uuid: string
    name: string
    vmid: number
    hidden: boolean
    orderColumn: number
}

export const rawDataToTemplateGroup = (data: any): TemplateGroup => ({
    id: data.id,
    nodeId: data.node_id,
    uuid: data.uuid,
    name: data.name,
    hidden: Boolean(data.hidden),
    templates: data?.templates?.data.map(rawDataToTemplate),
    orderColumn: data.order_column,
})

export const rawDataToTemplate = (data: any): Template => ({
    id: data.id,
    templateGroupId: data.template_group_id,
    uuid: data.uuid,
    name: data.name,
    vmid: data.vmid,
    hidden: Boolean(data.hidden),
    orderColumn: data.order_column,
})

const getTemplateGroups = async (nodeId: number): Promise<TemplateGroup[]> => {
    const {
        data: { data },
    } = await http.get(`/api/admin/nodes/${nodeId}/template-groups`)

    return data.map(rawDataToTemplateGroup)
}

export default getTemplateGroups