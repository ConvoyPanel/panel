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
