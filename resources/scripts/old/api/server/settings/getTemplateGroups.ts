import http from '@/api/http'

export interface TemplateGroup {
    uuid: string
    name: string
    hidden: boolean
    templates: Template[]
    orderColumn: number
}

export interface Template {
    uuid: string
    name: string
    hidden: boolean
    orderColumn: number
}

export const rawDataToTemplateGroup = (data: any): TemplateGroup => ({
    uuid: data.uuid,
    name: data.name,
    hidden: data.hidden,
    templates: data?.templates?.data.map(rawDataToTemplate),
    orderColumn: data.order_column,
})

export const rawDataToTemplate = (data: any): Template => ({
    uuid: data.uuid,
    name: data.name,
    hidden: data.hidden,
    orderColumn: data.order_column,
})

const getTemplateGroups = async (serverUuid: string) => {
    const {
        data: { data },
    } = await http.get(
        `/api/client/servers/${serverUuid}/settings/template-groups`
    )

    return data.map(rawDataToTemplateGroup)
}

export default getTemplateGroups