import { Deployment, DeploymentStep } from '@/types/deployment'
import { Template } from '@/types/template'

export const rawDataToDeployment = (data: any): Deployment => {
    const stepsData = data.steps?.data ?? data.steps ?? []
    const steps: DeploymentStep[] = stepsData.map((step: any): DeploymentStep => ({
        id: step.id,
        name: step.name,
        status: step.status,
        progressCurrent: step.progressCurrent,
        progressTotal: step.progressTotal,
        startedAt: step.startedAt ? new Date(step.startedAt) : null,
        completedAt: step.completedAt ? new Date(step.completedAt) : null,
        errorCode: step.errorCode,
        errorMessage: step.errorMessage,
    }))

    const templateData = data.template?.data ?? data.template

    return {
        id: data.id,
        serverId: data.serverId,
        templateId: data.templateId,
        status: data.status,
        type: data.type,
        startOnCompletion: data.startOnCompletion,
        requestedAt: new Date(data.requestedAt),
        completedAt: data.completedAt ? new Date(data.completedAt) : null,
        template: templateData ? (templateData as Template) : undefined,
        steps,
    }
}
