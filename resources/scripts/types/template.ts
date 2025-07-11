export interface TemplateGroup {
    uuid: string
    name: string
    hidden: boolean
    templates: Template[]
    orderColumn: number
}

export interface Template {
    id: number;
    uuid: string;
    templateGroupId: number;
    name: string;
    description: string | null;
    vmid: number;
    isAdminOnly: boolean;
}
