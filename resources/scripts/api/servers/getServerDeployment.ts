import axios from '@/lib/axios.ts'
import type { Deployment, DeploymentStep } from '@/types/deployment'
import type { Template } from '@/types/template'

const getServerDeployment = async (uuid: string): Promise<Deployment | null> => {
    const response = await axios.get(`/api/client/servers/${uuid}/deployment`)

    if (response.status === 204) {
        return null
    }

    const data = response.data.data

    const steps: DeploymentStep[] = (data.steps?.data ?? data.steps ?? []).map((step: any) => ({
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
        template: templateData
            ? ({
                  uuid: templateData.uuid,
                  templateGroupId: templateData.templateGroupId,
                  name: templateData.name,
                  description: templateData.description,
                  vmid: templateData.vmid,
                  isAdminOnly: templateData.isAdminOnly,
              } as Template)
            : undefined,
        steps,
    }
}

export default getServerDeployment
