import { Deployment } from '@/types/deployment'
import { Template } from '@/types/template'

export const rawDataToDeployment = (data: any): Deployment => ({
    id: data.id,
    serverId: data.server_id,
    templateId: data.template_id,
    status: data.status,
    type: data.type,
    startOnCompletion: data.start_on_completion,
    requestedAt: new Date(data.requested_at),
    completedAt: data.completed_at ? new Date(data.completed_at) : null,
    template: data.template ? (data.template as Template) : undefined,
    steps: data.steps ? data.steps.map((step: any) => ({
        id: step.id,
        name: step.name,
        status: step.status,
        progressCurrent: step.progress_current,
        progressTotal: step.progress_total,
        startedAt: step.started_at ? new Date(step.started_at) : null,
        completedAt: step.completed_at ? new Date(step.completed_at) : null,
        errorCode: step.error_code,
        errorMessage: step.error_message,
    })) : [],
})
