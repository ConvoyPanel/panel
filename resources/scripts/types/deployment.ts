import { Template } from '@/types/template'

export enum DeploymentStatus {
    Pending = 'pending',
    Running = 'running',
    Completed = 'completed',
    Failed = 'failed',
}

export enum DeploymentType {
    Install = 'install',
    Reinstall = 'reinstall',
    Delete = 'delete',
    Import = 'import',
}

export interface DeploymentStep {
    id: number
    name: string
    status: DeploymentStatus
    progressCurrent: number
    progressTotal: number
    startedAt: Date | null
    completedAt: Date | null
    errorCode: string | null
    errorMessage: string | null
}

export interface Deployment {
    id: number
    serverId: number
    templateId: number | null
    status: DeploymentStatus
    type: DeploymentType
    startOnCompletion: boolean
    requestedAt: Date
    completedAt: Date | null
    template?: Template
    steps: DeploymentStep[]
}
