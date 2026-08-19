import { useNode } from '@/features/nodes/api.ts'
import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'
import { useStorages } from '@/features/nodes/storages/api.ts'
import { useTemplates } from '@/features/template-groups/templates/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import { useServerPresetsModalStore } from '@/routes/_app/admin/_dashboard/server-presets.lazy.tsx'
import type { ServerPresetSettings } from '@/types/server-preset'
import byteSize from 'byte-size'
import { ReactNode } from 'react'

import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

const MEBIBYTE = 1024 * 1024
const GIBIBYTE = 1024 * MEBIBYTE

const iec = { units: 'iec' as const, precision: 1 }

/** A preset stores MiB; the rest of the app formats bytes through byte-size. */
const mib = (value: number) => byteSize(value * MEBIBYTE, iec).toString()

interface RowProps {
    label: string
    children: ReactNode
}

const Row = ({ label, children }: RowProps) => (
    <div className={'flex items-baseline justify-between gap-4 py-1.5'}>
        <dt className={'text-muted-foreground text-xs'}>{label}</dt>
        <dd className={'text-right text-sm'}>{children}</dd>
    </div>
)

const unlimited = (value: number) => (value === -1 ? 'Unlimited' : null)

/**
 * What a preset will fill in, in the operator's terms rather than the database's
 * — resolved names for the node, storage and bridge it points at, and byte
 * sizes formatted the way the rest of the panel formats them.
 */
const PresetSettings = ({ settings }: { settings: ServerPresetSettings }) => {
    const { data: node } = useNode(settings.nodeId ?? undefined)
    const { data: storages } = useStorages(settings.nodeId ?? undefined)
    const { data: interfaces } = useNetworkInterfaces(settings.nodeId ?? null)
    const { data: templates } = useTemplates(settings.templateGroupUuid, {})

    // A storage's display name is optional, so fall back to its PVE name the
    // way the create form's picker labels it; the id is the last resort, for a
    // storage that has since been detached from the node.
    const storageName = (id: number) => {
        const storage = storages?.find(item => item.id === id)

        if (!storage) return `Storage #${id}`

        return storage.displayName || storage.name
    }

    const interfaceName =
        interfaces?.find(item => item.id === settings.networkInterfaceId)
            ?.name ?? `Interface #${settings.networkInterfaceId}`

    const templateName =
        templates?.find(template => template.uuid === settings.templateUuid)
            ?.name ?? settings.templateUuid

    return (
        <dl className={'divide-y'}>
            {settings.nodeId != null && (
                <Row label={'Node'}>
                    {node?.displayName ?? `Node #${settings.nodeId}`}
                </Row>
            )}
            {settings.storageId != null && (
                <Row label={'Storage'}>{storageName(settings.storageId)}</Row>
            )}
            {settings.cpu != null && <Row label={'vCPU'}>{settings.cpu}</Row>}
            {settings.memory != null && (
                <Row label={'Memory'}>{mib(settings.memory)}</Row>
            )}
            {settings.disk != null && (
                <Row label={'Primary disk'}>{mib(settings.disk)}</Row>
            )}
            {settings.disks?.map((disk, index) => (
                <Row key={index} label={`Extra disk ${index + 1}`}>
                    {byteSize(disk.size * GIBIBYTE, iec).toString()} on{' '}
                    {storageName(disk.storageId)}
                </Row>
            ))}
            {settings.bandwidth != null && (
                <Row label={'Bandwidth'}>
                    {unlimited(settings.bandwidth) ?? mib(settings.bandwidth)}
                </Row>
            )}
            {settings.speedLimit != null && (
                <Row label={'Speed limit'}>{settings.speedLimit} MB/s</Row>
            )}
            {settings.backupCount != null && (
                <Row label={'Backup count'}>
                    {unlimited(settings.backupCount) ?? settings.backupCount}
                </Row>
            )}
            {settings.backupSize != null && (
                <Row label={'Backup storage'}>
                    {unlimited(settings.backupSize) ?? mib(settings.backupSize)}
                </Row>
            )}
            {settings.networkInterfaceId != null && (
                <Row label={'Network interface'}>{interfaceName}</Row>
            )}
            {settings.vlanTag != null && (
                <Row label={'VLAN'}>{settings.vlanTag}</Row>
            )}
            {settings.addressesIpv4Count != null && (
                <Row label={'IPv4 addresses'}>
                    {settings.addressesIpv4Count}
                </Row>
            )}
            {settings.addressesIpv6Count != null && (
                <Row label={'IPv6 addresses'}>
                    {settings.addressesIpv6Count}
                </Row>
            )}
            {settings.deferredOsSelection != null && (
                <Row label={'Owner picks the OS'}>
                    {settings.deferredOsSelection ? 'Yes' : 'No'}
                </Row>
            )}
            {settings.shouldCreateVm != null && (
                <Row label={'Create the VM'}>
                    {settings.shouldCreateVm ? 'Yes' : 'No'}
                </Row>
            )}
            {settings.templateUuid != null && (
                <Row label={'Template'}>{templateName}</Row>
            )}
            {settings.startOnCompletion != null && (
                <Row label={'Start when built'}>
                    {settings.startOnCompletion ? 'Yes' : 'No'}
                </Row>
            )}
        </dl>
    )
}

const ShowPresetModal = () => {
    const {
        open,
        data: preset,
        close,
    } = useModal(useServerPresetsModalStore, 'show')

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {preset?.name}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        {preset?.description ??
                            'Applied to the create form as a starting point.'}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    {preset && <PresetSettings settings={preset.settings} />}
                </ResponsiveDialogBody>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ShowPresetModal
