import { Deployment, DeploymentStep } from '@/types/deployment'
import { Template } from '@/types/template'

export const rawDataToDeployment = (data: any): Deployment => {
    // Unwrap Fractal response structure for nested resources
    const stepsData = data.steps?.data ?? data.steps ?? []
    const steps: DeploymentStep[] = stepsData.map((step: any): DeploymentStep => ({
        id: step.id,
        name: step.name,
        status: step.status,
        progressCurrent: step.progress_current,
        progressTotal: step.progress_total,
        startedAt: step.started_at ? new Date(step.started_at) : null,
        completedAt: step.completed_at ? new Date(step.completed_at) : null,
        errorCode: step.error_code,
        errorMessage: step.error_message,
    }))

    // Unwrap Fractal response structure for template
    const templateData = data.template?.data ?? data.template

    return {
        id: data.id,
        serverId: data.server_id,
        templateId: data.template_id,
        status: data.status,
        type: data.type,
        startOnCompletion: data.start_on_completion,
        requestedAt: new Date(data.requested_at),
        completedAt: data.completed_at ? new Date(data.completed_at) : null,
        template: templateData ? (templateData as Template) : undefined,
        steps,
    }
}
