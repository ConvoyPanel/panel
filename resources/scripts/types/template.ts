export interface Template {
    uuid: string;
    templateGroupId: number;
    name: string;
    description: string | null;
    vmid: number;
    isAdminOnly: boolean;
}
